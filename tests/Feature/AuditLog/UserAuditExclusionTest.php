<?php

namespace Tests\Feature\AuditLog;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use OwenIt\Auditing\Models\Audit;
use Tests\TestCase;

class UserAuditExclusionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedBasics();
        $this->user = $this->createUserWithRoles();
        $this->enableModelAuditing();
    }

    public function test_pin_change_is_not_audited(): void
    {
        $this->user->pin = 1234;
        $this->user->save();

        $this->assertFalse(Audit::where('event', 'updated')->exists());
    }

    public function test_remember_token_change_is_not_audited(): void
    {
        $this->user->setRememberToken('new-token');
        $this->user->save();

        $this->assertFalse(Audit::where('event', 'updated')->exists());
    }

    public function test_excluded_fields_are_left_out_of_other_changes(): void
    {
        $this->user->pin = 1234;
        $this->user->firstname = 'Neuer';
        $this->user->save();

        $audit = Audit::where('event', 'updated')->sole();
        $this->assertSame(['firstname' => 'Neuer'], $audit->new_values);
    }

    public function test_remember_token_is_hidden_from_serialization(): void
    {
        $this->user->setRememberToken('secret-token');

        $this->assertArrayNotHasKey('remember_token', $this->user->toArray());
    }
}
