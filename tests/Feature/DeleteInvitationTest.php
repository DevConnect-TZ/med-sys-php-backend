<?php

namespace Tests\Feature;

use App\Models\Invitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DeleteInvitationTest extends TestCase
{
    use RefreshDatabase;

    protected function migrateFreshUsing(): array
    {
        return ['--path' => [
            'database/migrations/0001_01_01_000000_create_users_table.php',
            'database/migrations/2025_01_01_000011_create_invitations_table.php',
        ]];
    }

    private function invitation(User $admin, array $attributes = []): Invitation
    {
        return Invitation::create(array_merge([
            'email' => 'invited@example.com',
            'role' => 'doctor',
            'token' => Str::random(32),
            'status' => 'pending',
            'expires_at' => now()->addDays(7),
            'created_by' => $admin->id,
        ], $attributes));
    }

    public function test_admin_can_delete_pending_invitation_and_send_a_new_one(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $invitation = $this->invitation($admin);
        Sanctum::actingAs($admin);

        $this->deleteJson("/api/invitations/{$invitation->id}")->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseMissing('invitations', ['id' => $invitation->id]);
        $this->getJson("/api/invitations/{$invitation->token}/validate")->assertNotFound();
        $this->postJson("/api/invitations/{$invitation->token}/accept", [
            'name' => 'Invited Person',
            'password' => 'ExamplePass123!@',
        ])->assertNotFound();
        $this->assertDatabaseMissing('users', ['email' => $invitation->email]);

        Mail::fake();
        $this->postJson('/api/invitations', ['email' => $invitation->email, 'role' => 'doctor'])->assertCreated();
        $this->assertDatabaseHas('invitations', ['email' => $invitation->email, 'status' => 'pending']);
    }

    public function test_deleting_an_accepted_invitation_preserves_the_user_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'doctor']);
        $invitation = $this->invitation($admin, ['email' => $user->email, 'status' => 'accepted', 'used_at' => now()]);
        Sanctum::actingAs($admin);

        $this->deleteJson("/api/invitations/{$invitation->id}")->assertOk();
        $this->assertDatabaseMissing('invitations', ['id' => $invitation->id]);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'is_active' => true]);
    }

    public function test_admin_can_delete_an_expired_invitation(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $invitation = $this->invitation($admin, ['status' => 'expired', 'expires_at' => now()->subDay()]);
        Sanctum::actingAs($admin);

        $this->deleteJson("/api/invitations/{$invitation->id}")->assertOk();
        $this->assertDatabaseMissing('invitations', ['id' => $invitation->id]);
    }

    public function test_all_non_admin_roles_are_forbidden_from_deleting_invitations(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $invitation = $this->invitation($admin);
        foreach (['doctor', 'nurse', 'receptionist', 'cashier', 'lab_technician', 'pharmacist'] as $role) {
            Sanctum::actingAs(User::factory()->create(['role' => $role]));
            $this->deleteJson("/api/invitations/{$invitation->id}")->assertForbidden();
        }
        $this->assertDatabaseHas('invitations', ['id' => $invitation->id]);
    }

    public function test_unauthenticated_requests_cannot_delete_invitations(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $invitation = $this->invitation($admin);

        $this->deleteJson("/api/invitations/{$invitation->id}")->assertUnauthorized();
        $this->assertDatabaseHas('invitations', ['id' => $invitation->id]);
    }

    public function test_missing_or_previously_deleted_invitations_return_not_found(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $invitation = $this->invitation($admin);
        Sanctum::actingAs($admin);

        $this->deleteJson("/api/invitations/{$invitation->id}")->assertOk();
        $this->deleteJson("/api/invitations/{$invitation->id}")->assertNotFound();
    }
}
