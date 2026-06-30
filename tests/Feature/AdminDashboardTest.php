<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_roles_are_forbidden_from_the_admin_dashboard(): void
    {
        $manager = User::factory()->create(['role' => 'product_manager']);

        $response = $this->actingAs($manager)->get('/admin');

        $response->assertForbidden();
    }

    public function test_admin_dashboard_lists_user_accounts_and_audit_log(): void
    {
        $admin = User::factory()->create(['role' => 'system_admin']);
        $inspector = User::factory()->create(['role' => 'quality_inspector', 'name' => 'Listed Inspector']);

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertOk();
        $response->assertSee('Listed Inspector');
        $response->assertSee($inspector->email);
    }

    public function test_audit_log_paginates_at_ten_per_page(): void
    {
        $admin = User::factory()->create(['role' => 'system_admin']);

        AuditLog::factory()->count(15)->create(['action' => 'inspection.validated', 'user_id' => $admin->id]);

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertOk();
        $response->assertViewHas('auditLogs', fn ($auditLogs) => $auditLogs->count() === 10 && $auditLogs->total() === 15);
    }

    public function test_audit_log_can_be_filtered_by_action(): void
    {
        $admin = User::factory()->create(['role' => 'system_admin']);

        AuditLog::factory()->create(['action' => 'batch.created', 'user_id' => $admin->id]);
        AuditLog::factory()->create(['action' => 'user.updated', 'user_id' => $admin->id]);

        $response = $this->actingAs($admin)->get('/admin?action=batch.created');

        $response->assertOk();
        $response->assertViewHas('auditLogs', fn ($auditLogs) => $auditLogs->total() === 1);
    }

    public function test_audit_log_can_be_filtered_by_user(): void
    {
        $admin = User::factory()->create(['role' => 'system_admin']);
        $otherAdmin = User::factory()->create(['role' => 'system_admin']);

        AuditLog::factory()->create(['action' => 'batch.created', 'user_id' => $admin->id]);
        AuditLog::factory()->create(['action' => 'batch.created', 'user_id' => $otherAdmin->id]);

        $response = $this->actingAs($admin)->get('/admin?user_id='.$admin->id);

        $response->assertOk();
        $response->assertViewHas('auditLogs', fn ($auditLogs) => $auditLogs->total() === 1);
    }

    public function test_audit_log_rejects_an_invalid_date_filter(): void
    {
        $admin = User::factory()->create(['role' => 'system_admin']);

        $response = $this->actingAs($admin)->get('/admin?date_from=not-a-date');

        $response->assertSessionHasErrors('date_from');
    }

    public function test_admin_can_assign_any_role_when_creating_accounts(): void
    {
        $admin = User::factory()->create(['role' => 'system_admin']);

        $response = $this->actingAs($admin)->get('/admin/users/create');

        $response->assertOk();
        $response->assertViewHas('roles', [
            'quality_inspector',
            'product_manager',
            'system_admin',
            'shoe_constructor',
        ]);
    }

    public function test_admin_can_assign_a_shift_when_creating_a_constructor_account(): void
    {
        $admin = User::factory()->create(['role' => 'system_admin']);

        $response = $this->actingAs($admin)->post('/admin/users', [
            'name' => 'New Constructor',
            'email' => 'new.constructor@cpoint.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'shoe_constructor',
            'shift' => 'pm',
        ]);

        $response->assertRedirect(route('dashboard.admin'));

        $this->assertDatabaseHas('users', [
            'email' => 'new.constructor@cpoint.test',
            'role' => 'shoe_constructor',
            'shift' => 'pm',
        ]);
    }

    public function test_shift_is_discarded_for_roles_other_than_shoe_constructor(): void
    {
        $admin = User::factory()->create(['role' => 'system_admin']);

        $this->actingAs($admin)->post('/admin/users', [
            'name' => 'New Inspector',
            'email' => 'shifted.inspector@cpoint.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'quality_inspector',
            'shift' => 'am',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'shifted.inspector@cpoint.test',
            'role' => 'quality_inspector',
            'shift' => null,
        ]);
    }

    public function test_admin_can_create_a_system_admin_account(): void
    {
        $admin = User::factory()->create(['role' => 'system_admin']);

        $response = $this->actingAs($admin)->post('/admin/users', [
            'name' => 'New Admin',
            'email' => 'new.admin@cpoint.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'system_admin',
        ]);

        $response->assertRedirect(route('dashboard.admin'));

        $this->assertDatabaseHas('users', [
            'email' => 'new.admin@cpoint.test',
            'role' => 'system_admin',
        ]);
    }

    public function test_admin_can_edit_an_existing_account(): void
    {
        $admin = User::factory()->create(['role' => 'system_admin']);
        $target = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'old.email@cpoint.test',
            'role' => 'shoe_constructor',
        ]);

        $response = $this->actingAs($admin)->patch("/admin/users/{$target->id}", [
            'name' => 'Updated Name',
            'email' => 'updated.email@cpoint.test',
            'role' => 'quality_inspector',
        ]);

        $response->assertRedirect(route('dashboard.admin'));

        $target->refresh();
        $this->assertSame('Updated Name', $target->name);
        $this->assertSame('updated.email@cpoint.test', $target->email);
        $this->assertSame('quality_inspector', $target->role);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'user.updated',
            'user_id' => $admin->id,
        ]);
    }

    public function test_admin_can_reset_a_users_password(): void
    {
        $admin = User::factory()->create(['role' => 'system_admin']);
        $target = User::factory()->create(['role' => 'shoe_constructor']);
        $originalHash = $target->password;

        $this->actingAs($admin)->patch("/admin/users/{$target->id}", [
            'name' => $target->name,
            'email' => $target->email,
            'role' => $target->role,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

        $target->refresh();
        $this->assertNotSame($originalHash, $target->password);
        $this->assertTrue(Hash::check('new-password-123', $target->password));
    }

    public function test_editing_a_user_without_a_password_keeps_the_existing_password(): void
    {
        $admin = User::factory()->create(['role' => 'system_admin']);
        $target = User::factory()->create(['role' => 'shoe_constructor']);
        $originalHash = $target->password;

        $this->actingAs($admin)->patch("/admin/users/{$target->id}", [
            'name' => $target->name,
            'email' => $target->email,
            'role' => $target->role,
        ]);

        $target->refresh();
        $this->assertSame($originalHash, $target->password);
    }
}
