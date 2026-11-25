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

        foreach (['pool', 'seat'] as $type) {
            $this->get(route('reports.license-audit.export', $type))->assertForbidden();
        }
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
            ->assertSee($auditor->display_name)
            ->assertSee(route('reports.license-audit.export', 'pool'))
            ->assertSee(route('reports.license-audit.export', 'seat'))
            ->assertSee('id="licensePoolAuditReport"', false)
            ->assertSee('id="licenseSeatAuditReport"', false)
            ->assertSee('data-show-export="true"', false);
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

        foreach (['pool', 'seat'] as $type) {
            $csv = $this->get(route('reports.license-audit.export', $type))
                ->assertOk()
                ->streamedContent();

            $this->assertStringContainsString(ucfirst($type).' Visible license', $csv);
            $this->assertStringNotContainsString('Hidden license', $csv);
        }
    }

    public function test_csv_exports_all_audits_including_deleted_licenses_and_escapes_formulas(): void
    {
        config()->set('app.escape_formulas', true);
        $license = License::factory()->create(['name' => 'Export license', 'seats' => 1]);
        $seat = $license->licenseseats()->first();
        $auditor = User::factory()->create();

        LicensePoolAudit::factory()->count(501)->create([
            'license_id' => $license->id,
            'audited_by' => $auditor->id,
            'recorded_seats' => 1,
            'verified_seats' => 2,
            'notes' => '=2+2',
        ]);
        for ($i = 0; $i < 501; $i++) {
            LicenseSeatAudit::create([
                'license_id' => $license->id,
                'license_seat_id' => $seat->id,
                'audited_by' => $auditor->id,
                'assigned_to_name' => 'Audited user',
                'in_use' => true,
                'user_confirmed' => false,
                'notes' => '=2+2',
            ]);
        }
        $license->delete();

        $this->actingAs(User::factory()->canViewReports()->create());
        foreach (['pool', 'seat'] as $type) {
            $csv = $this->get(route('reports.license-audit.export', $type))
                ->assertOk()
                ->assertHeader('content-type', 'text/csv; charset=UTF-8')
                ->streamedContent();

            $lines = explode("\r\n", trim($csv));
            $this->assertCount(502, $lines);
            $this->assertStringContainsString('Export license', $csv);
            $this->assertStringContainsString('`=2+2', $csv);
            $this->assertStringContainsString($auditor->display_name, $csv);

            $row = str_getcsv($lines[1], ',', '"', '\\');
            if ($type === 'pool') {
                $this->assertSame(['1', '2'], array_slice($row, 3, 2));
            } else {
                $this->assertSame('Audited user', $row[3]);
                $this->assertSame([trans('general.yes'), trans('general.no')], array_slice($row, 5, 2));
            }
        }
    }
}
