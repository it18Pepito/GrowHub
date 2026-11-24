<?php

namespace Tests\Feature;

use App\Mail\CommentViolationMail;
use App\Models\AppNotification;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Content;
use App\Models\EthicsPolicy;
use App\Models\PointTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CommentViolationModerationTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role = 'employee', string $nip = '202603001', int $points = 10): User
    {
        $user = User::create([
            'nip' => $nip,
            'name' => ucfirst($role).' '.$nip,
            'email' => "{$role}.{$nip}@growhub.co.id",
            'password' => Hash::make($nip),
            'role' => $role,
            'points' => $points,
            'last_activity_date' => today(),
            'is_active' => true,
            'must_change_password' => false,
        ]);

        PointTransaction::create([
            'user_id' => $user->id,
            'points' => 2,
            'action' => 'daily_login',
            'description' => 'Aktivitas harian',
        ]);

        return $user;
    }

    private function makePublishedContent(User $author): Content
    {
        $category = Category::firstOrCreate(
            ['slug' => 'etika-diskusi'],
            ['name' => 'Etika Diskusi']
        );

        return Content::create([
            'user_id' => $author->id,
            'category_id' => $category->id,
            'title' => 'Panduan Pelayanan Ramah di Toko',
            'body' => 'Materi standar pelayanan pelanggan GrowHub.',
            'status' => Content::STATUS_PUBLISHED,
            'published_at' => now(),
            'comments_count' => 0,
        ]);
    }

    public function test_admin_deleting_another_users_comment_requires_valid_ethics_policy_selection(): void
    {
        Mail::fake();

        $admin = $this->makeUser('admin', '1000000091', 50);
        $author = $this->makeUser('employee', '202603001', 20);
        $commenter = $this->makeUser('employee', '202603002', 10);

        $content = $this->makePublishedContent($author);
        $comment = Comment::create([
            'content_id' => $content->id,
            'user_id' => $commenter->id,
            'body' => 'Komentar tidak pantas di forum.',
        ]);
        $content->update(['comments_count' => 1]);

        Sanctum::actingAs($admin);

        // Missing ethics_policy_id (even if a free-text reason field is passed) must fail validation.
        $this->deleteJson("/api/comments/{$comment->id}", [
            'reason' => 'Teks bebas tidak boleh diterima',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['ethics_policy_id']);

        // Invalid ethics_policy_id must fail validation.
        $this->deleteJson("/api/comments/{$comment->id}", [
            'ethics_policy_id' => 999999,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['ethics_policy_id']);

        $this->assertDatabaseHas('comments', ['id' => $comment->id]);
        $this->assertSame(10, $commenter->fresh()->points);
        Mail::assertNothingSent();
    }

    public function test_admin_deleting_other_user_comment_deducts_point_logs_notification_and_sends_email(): void
    {
        Mail::fake();

        $admin = $this->makeUser('admin', '1000000092', 50);
        $author = $this->makeUser('employee', '202603003', 20);
        $commenter = $this->makeUser('employee', '202603004', 10);

        $content = $this->makePublishedContent($author);
        $comment = Comment::create([
            'content_id' => $content->id,
            'user_id' => $commenter->id,
            'body' => 'Komentar mengandung unsur penghinaan.',
        ]);
        $content->update(['comments_count' => 1]);

        $policy = EthicsPolicy::query()->orderBy('sort_order')->firstOrFail();
        $policy->update(['point_deduction' => 3]);

        Sanctum::actingAs($admin);

        $response = $this->deleteJson("/api/comments/{$comment->id}", [
            'ethics_policy_id' => $policy->id,
        ]);

        $response->assertOk()
            ->assertJson([
                'comments_count' => 0,
                'points_deducted' => 3,
                'email_sent' => true,
                'violation_reason' => $policy->title,
            ]);

        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
        $this->assertSame(0, $content->fresh()->comments_count);
        $this->assertSame(7, $commenter->fresh()->points);

        $this->assertDatabaseHas('point_transactions', [
            'user_id' => $commenter->id,
            'points' => -3,
            'action' => 'policy_violation',
            'description' => "Pelanggaran etika komentar: {$policy->title}",
        ]);

        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $commenter->id,
            'actor_id' => $admin->id,
            'type' => 'policy_violation',
            'content_id' => $content->id,
        ]);

        Mail::assertSent(CommentViolationMail::class, function (CommentViolationMail $mail) use ($commenter, $policy) {
            $html = $mail->render();

            return $mail->hasTo($commenter->email)
                && $mail->policy->id === $policy->id
                && $mail->pointsDeducted === 3
                && $mail->remainingPoints === 7
                && str_contains($html, e($policy->title))
                && str_contains($html, 'Komentar mengandung unsur penghinaan.')
                && str_contains($html, '-3 Poin');
        });
    }

    public function test_point_deduction_clamps_user_points_at_zero_and_supports_nested_reply_moderation(): void
    {
        Mail::fake();

        $admin = $this->makeUser('admin', '1000000093', 50);
        $author = $this->makeUser('employee', '202603005', 20);
        $replier = $this->makeUser('employee', '202603006', 0);

        $content = $this->makePublishedContent($author);
        $parent = Comment::create([
            'content_id' => $content->id,
            'user_id' => $author->id,
            'body' => 'Bagaimana menurut rekan-rekan?',
        ]);
        $reply = Comment::create([
            'content_id' => $content->id,
            'user_id' => $replier->id,
            'parent_id' => $parent->id,
            'body' => 'Balasan mengandung spam promosi.',
        ]);
        $content->update(['comments_count' => 2]);

        $policy = EthicsPolicy::query()->orderBy('sort_order')->firstOrFail();
        $policy->update(['point_deduction' => 1]);

        Sanctum::actingAs($admin);

        $this->deleteJson("/api/comments/{$reply->id}", [
            'ethics_policy_id' => $policy->id,
        ])
            ->assertOk()
            ->assertJson([
                'comments_count' => 1,
                'points_deducted' => 1,
                'email_sent' => true,
            ]);

        $this->assertDatabaseMissing('comments', ['id' => $reply->id]);
        $this->assertDatabaseHas('comments', ['id' => $parent->id]);
        $this->assertSame(0, $replier->fresh()->points);

        $tx = PointTransaction::where('user_id', $replier->id)->where('action', 'policy_violation')->first();
        $this->assertNotNull($tx);
        $this->assertSame(-1, $tx->points);

        $notif = AppNotification::where('user_id', $replier->id)->where('type', 'policy_violation')->first();
        $this->assertNotNull($notif);

        Mail::assertSent(CommentViolationMail::class, fn (CommentViolationMail $mail) =>
            $mail->hasTo($replier->email) && $mail->remainingPoints === 0
        );
    }

    public function test_user_or_admin_deleting_own_comment_does_not_deduct_points_or_send_email(): void
    {
        Mail::fake();

        $admin = $this->makeUser('admin', '1000000094', 50);
        $employee = $this->makeUser('employee', '202603007', 15);
        $content = $this->makePublishedContent($employee);

        $empComment = Comment::create([
            'content_id' => $content->id,
            'user_id' => $employee->id,
            'body' => 'Komentar pribadi karyawan.',
        ]);
        $adminComment = Comment::create([
            'content_id' => $content->id,
            'user_id' => $admin->id,
            'body' => 'Komentar pribadi admin.',
        ]);
        $content->update(['comments_count' => 2]);

        Sanctum::actingAs($employee);
        $this->deleteJson("/api/comments/{$empComment->id}")
            ->assertOk()
            ->assertJson([
                'comments_count' => 1,
                'points_deducted' => 0,
                'email_sent' => false,
            ]);

        Sanctum::actingAs($admin);
        $this->deleteJson("/api/comments/{$adminComment->id}")
            ->assertOk()
            ->assertJson([
                'comments_count' => 0,
                'points_deducted' => 0,
                'email_sent' => false,
            ]);

        $this->assertSame(15, $employee->fresh()->points);
        $this->assertSame(50, $admin->fresh()->points);
        Mail::assertNothingSent();
    }

    public function test_non_admin_cannot_delete_another_users_comment(): void
    {
        Mail::fake();

        $owner = $this->makeUser('employee', '202603008', 25);
        $other = $this->makeUser('employee', '202603009', 25);
        $content = $this->makePublishedContent($owner);
        $comment = Comment::create([
            'content_id' => $content->id,
            'user_id' => $owner->id,
            'body' => 'Komentar yang tidak boleh dihapus user lain.',
        ]);
        $content->update(['comments_count' => 1]);

        $policy = EthicsPolicy::query()->orderBy('sort_order')->firstOrFail();
        $policy->update(['point_deduction' => 1]);

        Sanctum::actingAs($other);
        $this->deleteJson("/api/comments/{$comment->id}", [
            'ethics_policy_id' => $policy->id,
        ])->assertStatus(403);

        $this->assertDatabaseHas('comments', ['id' => $comment->id]);
        $this->assertSame(25, $owner->fresh()->points);
        Mail::assertNothingSent();
    }
}
