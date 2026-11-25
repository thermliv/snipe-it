<?php

namespace Tests\Feature\Reporting;

use App\Models\Company;
use App\Models\License;
use App\Models\LicensePoolAudit;
use App\Models\LicenseSeatAudit;
use App\Models\User;
use Tests\TestCase;

class LicenseAuditReportTest extends TestCase
{
    public function test_page_requires_permission(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('reports.license-audit'))
            ->assertForbidden();
    }

    public function test_page_renders_pool_and_seat_audits_including_deleted_licenses(): void
    {
        $license = License::factory()->create(['name' => 'Audited license', 'seats' => 3]);
        $auditor = User::factory()->create();
        LicensePoolAudit::factory()->create([
            'license_id' => $license->id,
            'audited_by' => $auditor->id,
            'recorded_seats' => 3,
            'verified_seats' => 5,
            'notes' => 'Pool finding',
        ]);
        LicenseSeatAudit::create([
            'license_id' => $license->id,
            'license_seat_id' => $license->licenseseats()->first()->id,
            'assigned_to_name' => 'Seat user snapshot',
            'audited_by' => $auditor->id,
            'in_use' => true,
            'user_confirmed' => false,
            'notes' => 'Seat finding',
        ]);
        $license->delete();

        $this->actingAs(User::factory()->canViewReports()->create())
            ->get(route('reports.license-audit'))
            ->assertOk()
            ->assertSee('Audited license')
            ->assertSee('Pool finding')
            ->assertSee('Seat finding')
            ->assertSee('Seat user snapshot')
            ->assertSee($auditor->display_name);
    }

    public function test_report_scopes_both_audit_types_to_visible_companies(): void
    {
        $this->settings->enableMultipleFullCompanySupport();
        $this->actingAs(User::factory()->superuser()->create());
        [$companyA, $companyB] = Company::factory()->count(2)->create();
        $licenseA = License::factory()->create(['company_id' => $companyA->id, 'name' => 'Visible license']);
        $licenseB = License::factory()->create(['company_id' => $companyB->id, 'name' => 'Hidden license']);
        foreach ([$licenseA, $licenseB] as $license) {
            LicensePoolAudit::factory()->create(['license_id' => $license->id, 'notes' => 'Pool '.$license->name]);
            LicenseSeatAudit::create([
                'license_id' => $license->id,
                'license_seat_id' => $license->licenseseats()->first()->id,
                'audited_by' => null,
                'in_use' => false,
                'user_confirmed' => false,
                'notes' => 'Seat '.$license->name,
            ]);
        }

        $this->actingAs(User::factory()->canViewReports()->forCompany($companyA)->create())
            ->get(route('reports.license-audit'))
            ->assertOk()
            ->assertSee('Pool Visible license')
            ->assertSee('Seat Visible license')
            ->assertDontSee('Hidden license');
    }
}
