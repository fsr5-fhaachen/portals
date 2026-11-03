<?php

namespace Tests\Feature\AuditLog;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use OwenIt\Auditing\Models\Audit;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleChangeAuditTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedBasics();
        $this->admin = $this->createUserWithRoles('admin');
        $this->enableModelAuditing();
    }

    /**
     * Submit the edit user form with the given roles.
     *
     * @param  list<string>  $roleNames
     */
    private function editRoles(User $user, array $roleNames): void
    {
        $this->actingAsTutor($this->admin)
            ->post(route('dashboard.admin.editUser', ['user' => $user->id]), [
                'firstname' => $user->firstname,
                'lastname' => $user->lastname,
                'email' => $user->email,
                'email_confirm' => $user->email,
                'course_id' => $user->course_id,
                'role_id' => Role::whereIn('name', $roleNames)->pluck('id')->all(),
                'is_disabled' => false,
            ])
            ->assertSessionHas('success');
    }

    public function test_role_change_is_audited(): void
    {
        $user = $this->createUserWithRoles('tutor');

        $this->editRoles($user, ['tutor', 'esa']);

        $audit = Audit::where('event', 'rolesUpdated')->sole();
        $this->assertSame(User::class, $audit->auditable_type);
        $this->assertSame($user->id, (int) $audit->auditable_id);
        $this->assertSame($this->admin->id, (int) $audit->user_id);
        $this->assertSame(['roles' => ['tutor']], $audit->old_values);
        $this->assertSame(['roles' => ['esa', 'tutor']], $audit->new_values);
    }

    public function test_unchanged_roles_are_not_audited(): void
    {
        $user = $this->createUserWithRoles('tutor', 'esa');

        $this->editRoles($user, ['esa', 'tutor']);

        $this->assertFalse(Audit::where('event', 'rolesUpdated')->exists());
    }

    public function test_roles_of_super_admins_are_not_changed_or_audited(): void
    {
        $superAdmin = $this->createUserWithRoles('super admin');

        $this->editRoles($superAdmin, ['tutor']);

        $this->assertTrue($superAdmin->fresh()->hasRole('super admin'));
        $this->assertFalse(Audit::where('event', 'rolesUpdated')->exists());
    }
}
