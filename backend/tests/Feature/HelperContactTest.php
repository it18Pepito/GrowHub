<?php

namespace Tests\Feature;

use App\Models\HelperContact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class HelperContactTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role = 'employee', string $nip = '202604001'): User
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

    public function test_public_and_unauthenticated_users_can_fetch_active_helpers(): void
    {
        $response = $this->getJson('/api/support/helpers')
            ->assertOk()
            ->assertJsonStructure([
                'helpers' => [
                    '*' => ['id', 'name', 'role_or_title', 'channel', 'contact_value', 'available_hours', 'description', 'is_active', 'sort_order'],
                ],
            ]);

        $this->assertCount(3, $response->json('helpers'));

        // Alias endpoint also works
        $this->getJson('/api/helper-contacts')->assertOk();
    }

    public function test_non_admin_cannot_access_admin_helper_contact_management(): void
    {
        $employee = $this->makeUser('employee', '202604002');
        Sanctum::actingAs($employee);

        $this->getJson('/api/admin/helper-contacts')->assertStatus(403);
        $this->postJson('/api/admin/helper-contacts', [
            'name' => 'Support Test',
            'channel' => 'whatsapp',
            'contact_value' => '0812345678',
        ])->assertStatus(403);
    }

    public function test_admin_can_crud_toggle_and_reset_helper_contacts(): void
    {
        $admin = $this->makeUser('admin', '1000000095');
        Sanctum::actingAs($admin);

        // 1. Get index as admin
        $res = $this->getJson('/api/admin/helper-contacts')
            ->assertOk()
            ->assertJsonStructure([
                'helpers',
                'stats' => ['total_helpers', 'active_helpers', 'inactive_helpers'],
            ]);
        $this->assertSame(3, $res->json('stats.total_helpers'));

        // 2. Create new contact
        $createRes = $this->postJson('/api/admin/helper-contacts', [
            'name' => 'Tim Support Cabang Surabaya',
            'role_or_title' => 'Regional PIC HC',
            'channel' => 'whatsapp',
            'contact_value' => '+62 813-9988-7766',
            'available_hours' => 'Senin - Sabtu, 08:00 - 18:00 WIB',
            'description' => 'Bantuan khusus regional Jawa Timur.',
            'is_active' => true,
            'sort_order' => 4,
        ])->assertStatus(201)
            ->assertJsonPath('contact.name', 'Tim Support Cabang Surabaya')
            ->assertJsonPath('stats.total_helpers', 4);

        $newId = $createRes->json('contact.id');

        // 3. Update contact
        $this->putJson("/api/admin/helper-contacts/{$newId}", [
            'name' => 'Tim Support Jawa Timur',
            'channel' => 'whatsapp',
            'contact_value' => '+62 813-9988-0000',
            'is_active' => true,
        ])->assertOk()
            ->assertJsonPath('contact.name', 'Tim Support Jawa Timur');

        // 4. Toggle active/inactive
        $this->postJson("/api/admin/helper-contacts/{$newId}/toggle")
            ->assertOk()
            ->assertJsonPath('contact.is_active', false);

        // 5. Delete contact
        $this->deleteJson("/api/admin/helper-contacts/{$newId}")
            ->assertOk()
            ->assertJsonPath('stats.total_helpers', 3);

        // 6. Reset defaults
        $this->postJson('/api/admin/helper-contacts/reset-defaults')
            ->assertOk()
            ->assertJsonPath('stats.total_helpers', 3);
    }
}
