<?php

namespace Tests\Feature;

use App\Mail\TestSmtpMail;
use App\Models\MailSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MailSettingTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role = 'employee', string $nip = '202602001'): User
    {
        return User::create([
            'nip' => $nip,
            'name' => ucfirst($role).' User',
            'email' => "{$role}.{$nip}@growhub.co.id",
            'password' => Hash::make($nip),
            'role' => $role,
            'is_active' => true,
            'must_change_password' => false,
        ]);
    }

    public function test_non_admin_cannot_access_or_update_mail_settings(): void
    {
        $employee = $this->makeUser('employee', '202602001');
        Sanctum::actingAs($employee);

        $this->getJson('/api/admin/mail-settings')->assertStatus(403);

        $this->putJson('/api/admin/mail-settings', [
            'mail_mailer' => 'smtp',
            'mail_host' => 'smtp.test.com',
            'mail_port' => 587,
            'mail_from_address' => 'noreply@test.com',
            'mail_from_name' => 'GrowHub Test',
        ])->assertStatus(403);

        $this->postJson('/api/admin/mail-settings/test', [
            'recipient_email' => 'test@test.com',
        ])->assertStatus(403);
    }

    public function test_admin_can_retrieve_mail_settings(): void
    {
        $admin = $this->makeUser('admin', '202602099');
        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/admin/mail-settings')
            ->assertOk()
            ->assertJsonStructure([
                'setting' => [
                    'mail_mailer',
                    'mail_scheme',
                    'mail_host',
                    'mail_port',
                    'mail_username',
                    'has_password',
                    'mail_from_address',
                    'mail_from_name',
                ],
                'env_snapshot',
            ]);

        $this->assertNotEmpty($response->json('setting.mail_host'));
    }

    public function test_admin_can_update_mail_settings(): void
    {
        $admin = $this->makeUser('admin', '202602099');
        Sanctum::actingAs($admin);

        $payload = [
            'mail_mailer' => 'smtp',
            'mail_scheme' => 'tls',
            'mail_host' => 'smtp.custom-domain.com',
            'mail_port' => 587,
            'mail_username' => 'custom-user@domain.com',
            'mail_password' => 'supersecretpass123',
            'mail_from_address' => 'system@custom-domain.com',
            'mail_from_name' => 'GrowHub Corporate Notifications',
        ];

        $response = $this->putJson('/api/admin/mail-settings', $payload)
            ->assertOk()
            ->assertJson([
                'setting' => [
                    'mail_mailer' => 'smtp',
                    'mail_scheme' => 'tls',
                    'mail_host' => 'smtp.custom-domain.com',
                    'mail_port' => 587,
                    'mail_username' => 'custom-user@domain.com',
                    'has_password' => true,
                    'mail_from_address' => 'system@custom-domain.com',
                    'mail_from_name' => 'GrowHub Corporate Notifications',
                ],
            ]);

        $setting = MailSetting::first();
        $this->assertNotNull($setting);
        $this->assertEquals('smtp.custom-domain.com', $setting->mail_host);
        $this->assertEquals(587, $setting->mail_port);
        $this->assertEquals('supersecretpass123', $setting->mail_password);
    }

    public function test_admin_can_send_test_email(): void
    {
        Mail::fake();

        $admin = $this->makeUser('admin', '202602099');
        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/admin/mail-settings/test', [
            'recipient_email' => 'employee.target@growhub.co.id',
        ])->assertOk();

        $response->assertJson([
            'success' => true,
        ]);

        Mail::assertSent(TestSmtpMail::class, function ($mail) {
            return $mail->hasTo('employee.target@growhub.co.id');
        });
    }
}
