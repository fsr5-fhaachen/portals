<?php

namespace Tests\Feature\AuditLog;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use OwenIt\Auditing\Models\Audit;
use Tests\TestCase;

class TutorLoginAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedBasics();
        $this->enableModelAuditing();
    }

    /**
     * Assert that exactly one audit with the given event exists for the user.
     */
    private function assertLoginAudited(User $user, string $event): void
    {
        $audit = Audit::where('event', $event)->sole();
        $this->assertSame(User::class, $audit->auditable_type);
        $this->assertSame($user->id, (int) $audit->auditable_id);
        $this->assertSame($user->id, (int) $audit->user_id);
    }

    public function test_successful_tutor_login_is_audited(): void
    {
        $tutor = $this->createUserWithRoles('tutor');

        $this->actingAs($tutor)
            ->post(route('dashboard.loginTutor'), ['password' => 'tutor-secret'])
            ->assertRedirect(route('dashboard.index'))
            ->assertSessionHas('tutor', true);

        $this->assertLoginAudited($tutor, 'tutorLogin');
    }

    public function test_failed_tutor_login_is_audited(): void
    {
        $tutor = $this->createUserWithRoles('tutor');

        $this->actingAs($tutor)
            ->from(route('dashboard.tutor.login'))
            ->post(route('dashboard.loginTutor'), ['password' => 'wrong'])
            ->assertRedirect(route('dashboard.tutor.login'))
            ->assertSessionHas('error')
            ->assertSessionMissing('tutor');

        $this->assertLoginAudited($tutor, 'tutorLoginFailed');
    }

    public function test_admin_login_is_audited(): void
    {
        $admin = $this->createUserWithRoles('admin');

        $this->actingAs($admin)
            ->post(route('dashboard.loginTutor'), ['password' => 'admin-secret'])
            ->assertSessionHas('tutor', true);

        $this->assertLoginAudited($admin, 'adminLogin');
    }

    public function test_admin_cannot_login_with_the_tutor_password(): void
    {
        $admin = $this->createUserWithRoles('admin');

        $this->actingAs($admin)
            ->post(route('dashboard.loginTutor'), ['password' => 'tutor-secret'])
            ->assertSessionMissing('tutor');

        $this->assertLoginAudited($admin, 'adminLoginFailed');
    }

    public function test_login_fails_when_no_password_is_configured(): void
    {
        config(['app.tutor_password' => '']);
        $tutor = $this->createUserWithRoles('tutor');

        $this->actingAs($tutor)
            ->post(route('dashboard.loginTutor'), ['password' => ''])
            ->assertSessionMissing('tutor');

        $this->assertLoginAudited($tutor, 'tutorLoginFailed');
    }

    public function test_user_without_tutor_role_is_redirected_instead_of_crashing(): void
    {
        $special = $this->createUserWithRoles('special');

        $this->actingAs($special)
            ->from(route('dashboard.tutor.login'))
            ->post(route('dashboard.loginTutor'), ['password' => 'tutor-secret'])
            ->assertRedirect(route('dashboard.tutor.login'))
            ->assertSessionHas('error')
            ->assertSessionMissing('tutor');

        $this->assertFalse(Audit::where('event', 'like', '%Login%')->exists());
    }
}
