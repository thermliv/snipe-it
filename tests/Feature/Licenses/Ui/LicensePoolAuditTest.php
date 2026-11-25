<?php

namespace Tests\Feature\Licenses\Ui;

use App\Models\Company;
use App\Models\License;
use App\Models\User;
use Tests\TestCase;

class LicensePoolAuditTest extends TestCase
{
    public function test_unassigned_pool_can_be_audited_without_changing_seats(): void
    {
        $license = License::factory()->create(['seats' => 100]);
        $auditor = User::factory()->editLicenses()->create();

        $this->actingAs($auditor)
            ->get(route('licenses.seat-audits.index', $license))
            ->assertOk()
            ->assertSee(route('licenses.pool-audits.store', $license))
            ->assertSee(trans('admin/licenses/general.pool_audit.not_audited'));

        $this->post(route('licenses.pool-audits.store', $license), [
            'verified_seats' => 95,
            'pool_audit_revision' => '0',
            'notes' => 'Contract has fewer seats.',
        ])->assertRedirect(route('licenses.seat-audits.index', $license));

        $this->assertDatabaseHas('license_pool_audits', [
            'license_id' => $license->id,
            'audited_by' => $auditor->id,
            'recorded_seats' => 100,
            'verified_seats' => 95,
            'notes' => 'Contract has fewer seats.',
        ]);
        $this->assertSame(100, (int) $license->refresh()->seats);
        $this->assertSame(100, $license->licenseseats()->count());
        $this->get(route('licenses.seat-audits.index', $license))
            ->assertSee(trans('admin/licenses/general.pool_audit.discrepancy'));

        $this->post(route('licenses.pool-audits.store', $license), [
            'verified_seats' => 100,
            'pool_audit_revision' => '0',
        ])->assertRedirect();
        $this->get(route('licenses.seat-audits.index', $license))
            ->assertSee(trans('admin/licenses/general.pool_audit.verified'));
        $this->assertDatabaseCount('license_pool_audits', 2);
    }

    public function test_capacity_changes_invalidate_an_audit_even_if_reverted(): void
    {
        $license = License::factory()->create(['seats' => 3]);
        $this->actingAs(User::factory()->editLicenses()->create());
        $this->post(route('licenses.pool-audits.store', $license), [
            'verified_seats' => 3,
            'pool_audit_revision' => '0',
        ])->assertRedirect();

        $license->seats = 4;
        $this->assertTrue($license->save());
        $license->refresh();
        $license->seats = 3;
        $this->assertTrue($license->save());

        $this->get(route('licenses.seat-audits.index', $license))
            ->assertSee(trans('admin/licenses/general.pool_audit.stale'));
        $this->from(route('licenses.seat-audits.index', $license))
            ->followingRedirects()
            ->post(route('licenses.pool-audits.store', $license), [
                'verified_seats' => 3,
                'pool_audit_revision' => '0',
            ])->assertOk()
            ->assertSee(trans('admin/licenses/general.pool_audit.stale_form'));
        $this->assertDatabaseCount('license_pool_audits', 1);
        $this->post(route('licenses.pool-audits.store', $license), [
            'verified_seats' => 3,
            'pool_audit_revision' => $license->refresh()->pool_audit_revision,
        ])->assertSessionHasNoErrors();
        $this->get(route('licenses.seat-audits.index', $license))
            ->assertSee(trans('admin/licenses/general.pool_audit.verified'));
    }

    public function test_requires_permission_and_valid_capacity(): void
    {
        $license = License::factory()->create();
        $this->actingAs(User::factory()->viewLicenses()->create())
            ->post(route('licenses.pool-audits.store', $license), [
                'verified_seats' => 3,
                'pool_audit_revision' => '0',
            ])->assertForbidden();

        $this->actingAs(User::factory()->editLicenses()->create())
            ->post(route('licenses.pool-audits.store', $license), [
                'verified_seats' => -1,
                'pool_audit_revision' => '0',
            ])->assertSessionHasErrors('verified_seats');
        $this->assertDatabaseCount('license_pool_audits', 0);
    }

    public function test_unrelated_license_edits_do_not_invalidate_pool_audit(): void
    {
        $license = License::factory()->create(['seats' => 2]);
        $this->actingAs(User::factory()->editLicenses()->create());
        $this->post(route('licenses.pool-audits.store', $license), [
            'verified_seats' => 2,
            'pool_audit_revision' => '0',
        ])->assertRedirect();

        $license->name = 'Renamed license';
        $this->assertTrue($license->save());

        $this->get(route('licenses.seat-audits.index', $license))
            ->assertSee(trans('admin/licenses/general.pool_audit.verified'));
    }

    public function test_cross_company_pool_audit_is_rejected(): void
    {
        $this->settings->enableMultipleFullCompanySupport();
        $this->actingAs(User::factory()->superuser()->create());
        [$companyA, $companyB] = Company::factory()->count(2)->create();
        $allowedLicense = License::factory()->create(['company_id' => $companyA->id]);
        $license = License::factory()->create(['company_id' => $companyB->id]);

        $this->actingAs(User::factory()->editLicenses()->forCompany($companyA)->create())
            ->post(route('licenses.pool-audits.store', $license), [
                'verified_seats' => 2,
                'pool_audit_revision' => '0',
            ]);

        $this->assertDatabaseCount('license_pool_audits', 0);

        $this->post(route('licenses.pool-audits.store', $allowedLicense), [
            'verified_seats' => 2,
            'pool_audit_revision' => '0',
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('license_pool_audits', ['license_id' => $allowedLicense->id]);
    }
}
