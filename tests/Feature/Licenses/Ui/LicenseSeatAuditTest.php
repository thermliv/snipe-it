<?php

namespace Tests\Feature\Licenses\Ui;

use App\Models\License;
use App\Models\LicenseSeat;
use App\Models\User;
use Tests\TestCase;

class LicenseSeatAuditTest extends TestCase
{
    public function test_page_renders(): void
    {
        $license = License::factory()->create();
        $user = User::factory()->create();
        $seat = LicenseSeat::factory()->create([
            'license_id' => $license->id,
            'assigned_to' => $user->id,
        ]);

        $this->actingAs(User::factory()->editLicenses()->create())
            ->get(route('licenses.seat-audits.index', $license))
            ->assertOk()
            ->assertSee($user->display_name)
            ->assertSee(route('licenses.seat-audits.store', [$license, $seat]));
    }

    public function test_requires_permission(): void
    {
        $license = License::factory()->create();

        $this->actingAs(User::factory()->viewLicenses()->create())
            ->get(route('licenses.seat-audits.index', $license))
            ->assertForbidden();
    }

    public function test_seat_audit_records_findings_without_changing_assignment(): void
    {
        $license = License::factory()->create();
        $assignedUser = User::factory()->create();
        $seat = LicenseSeat::factory()->create([
            'license_id' => $license->id,
            'assigned_to' => $assignedUser->id,
        ]);
        $auditor = User::factory()->editLicenses()->create();
        $otherUser = User::factory()->create();

        $this->actingAs($auditor)
            ->post(route('licenses.seat-audits.store', [$license, $seat]), [
                'in_use' => '1',
                'user_confirmed' => '0',
                'notes' => 'Assigned user could not confirm the software is in use.',
                'assigned_to' => $otherUser->id,
            ])
            ->assertRedirect(route('licenses.seat-audits.index', $license));

        $this->assertDatabaseHas('license_seat_audits', [
            'license_id' => $license->id,
            'license_seat_id' => $seat->id,
            'assigned_to_id' => $assignedUser->id,
            'assigned_to_name' => $assignedUser->display_name,
            'audited_by' => $auditor->id,
            'in_use' => true,
            'user_confirmed' => false,
            'notes' => 'Assigned user could not confirm the software is in use.',
        ]);

        $this->assertSame($assignedUser->id, $seat->refresh()->assigned_to);

        $this->actingAs($auditor)
            ->post(route('licenses.seat-audits.store', [$license, $seat]), [
                'in_use' => '0',
                'user_confirmed' => '1',
            ])
            ->assertRedirect(route('licenses.seat-audits.index', $license));

        $this->assertDatabaseCount('license_seat_audits', 2);
    }

    public function test_seat_audit_requires_an_active_use_finding(): void
    {
        $license = License::factory()->create();
        $seat = LicenseSeat::factory()->assignedToUser()->create(['license_id' => $license->id]);

        $this->actingAs(User::factory()->editLicenses()->create())
            ->post(route('licenses.seat-audits.store', [$license, $seat]), [
                'user_confirmed' => '0',
            ])
            ->assertSessionHasErrors('in_use');

        $this->assertDatabaseCount('license_seat_audits', 0);
    }

    public function test_seat_audit_rejects_a_seat_from_another_license(): void
    {
        $license = License::factory()->create();
        $otherSeat = LicenseSeat::factory()->assignedToUser()->create();

        $this->actingAs(User::factory()->editLicenses()->create())
            ->post(route('licenses.seat-audits.store', [$license, $otherSeat]), [
                'in_use' => '1',
                'user_confirmed' => '1',
            ])
            ->assertNotFound();

        $this->assertDatabaseCount('license_seat_audits', 0);
    }
}
