<?php

namespace Tests\Feature;

use App\Models\EthicsPolicy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PolicyConfigTest extends TestCase
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

    public function test_authenticated_user_receives_seeded_active_ethics_policies(): void
    {
        $employee = $this->makeUser('employee', '202602001');
        Sanctum::actingAs($employee);

        $response = $this->getJson('/api/ethics-policies')
            ->assertOk()
            ->assertJsonStructure([
                'policies' => [
                    '*' => ['id', 'title', 'description', 'point_deduction', 'severity', 'is_active', 'sort_order'],
                ],
            ]);

        $this->assertCount(5, $response->json('policies'));
    }

    public function test_non_admin_cannot_manage_policy_config(): void
    {
        $employee = $this->makeUser('employee', '202602002');
        Sanctum::actingAs($employee);

        $this->getJson('/api/admin/policy-configs')->assertStatus(403);
        $this->postJson('/api/admin/policy-configs', [
            'title' => 'Unauthorized Rule',
            'description' => 'Should fail',
        ])->assertStatus(403);
    }

    public function test_admin_can_crud_batch_update_and_reset_policy_configs(): void
    {
        $admin = $this->makeUser('admin', '1000000090');
        $employee = $this->makeUser('employee', '202602003');
        Sanctum::actingAs($admin);

        // Create new rule with custom point_deduction (dynamic, not static 1)
        $createRes = $this->postJson('/api/admin/policy-configs', [
            'title' => 'Berbagi Akun Login',
            'description' => 'Dilarang meminjamkan akun kepada orang lain.',
            'point_deduction' => 5,
            'severity' => 'high',
        ])->assertStatus(201)
            ->assertJsonPath('policy.point_deduction', 5)
            ->assertJsonPath('policy.severity', 'high');

        $newId = $createRes->json('policy.id');
        $this->assertSame(6, EthicsPolicy::count());

        // Batch update with modified point_deduction
        $this->putJson('/api/admin/policy-configs/batch', [
            'policies' => [
                [
                    'id' => $newId,
                    'title' => 'Berbagi Kredensial Akun',
                    'description' => 'Dilarang keras membagikan password atau OTP.',
                    'point_deduction' => 8,
                    'severity' => 'high',
                    'sort_order' => 1,
                ],
            ],
        ])->assertOk();

        $this->assertSame(8, EthicsPolicy::find($newId)->point_deduction);

        // Newly created policy appears in employee login popup list
        Sanctum::actingAs($employee);
        $visibleIds = collect($this->getJson('/api/ethics-policies')->assertOk()->json('policies'))->pluck('id')->all();
        $this->assertContains($newId, $visibleIds);

        // Delete as admin
        Sanctum::actingAs($admin);
        $this->deleteJson("/api/admin/policy-configs/{$newId}")->assertOk();
        $this->assertSame(5, EthicsPolicy::count());
    }
}

