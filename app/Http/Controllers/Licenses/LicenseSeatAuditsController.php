<?php

namespace App\Http\Controllers\Licenses;

use App\Http\Controllers\Controller;
use App\Models\License;
use App\Models\LicensePoolAudit;
use App\Models\LicenseSeat;
use App\Models\LicenseSeatAudit;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LicenseSeatAuditsController extends Controller
{
    public function index(License $license): View
    {
        $this->authorize('update', $license);

        $seats = $license->licenseseats()
            ->with('user')
            ->whereNotNull('assigned_to')
            ->orderBy('id')
            ->paginate(50);

        $auditsBySeat = $license->seatAudits()
            ->with('auditor')
            ->whereIn('license_seat_id', $seats->pluck('id'))
            ->orderByDesc('id')
            ->get()
            ->groupBy('license_seat_id');

        $latestPoolAudit = $license->poolAudits()->with('auditor')->latest('id')->first();

        return view('licenses.audit-seats', compact('license', 'seats', 'auditsBySeat', 'latestPoolAudit'));
    }

    public function storePool(Request $request, License $license): RedirectResponse
    {
        $this->authorize('update', $license);

        $validated = $request->validate([
            'verified_seats' => ['required', 'integer', 'min:0', 'max:2147483647'],
            'pool_audit_revision' => ['required', 'string', 'max:36'],
            'notes' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($license, $request, $validated): void {
            $currentLicense = License::whereKey($license->id)->lockForUpdate()->firstOrFail();

            if ($currentLicense->pool_audit_revision !== $validated['pool_audit_revision']) {
                throw ValidationException::withMessages([
                    'pool_audit_revision' => trans('admin/licenses/general.pool_audit.stale_form'),
                ]);
            }

            LicensePoolAudit::create([
                'license_id' => $currentLicense->id,
                'audited_by' => $request->user()->id,
                'recorded_seats' => $currentLicense->seats,
                'verified_seats' => $validated['verified_seats'],
                'pool_audit_revision' => $currentLicense->pool_audit_revision,
                'notes' => $validated['notes'] ?? null,
            ]);
        });

        return redirect()->route('licenses.seat-audits.index', $license)
            ->with('success', trans('admin/licenses/general.pool_audit.success'));
    }

    public function store(Request $request, License $license, LicenseSeat $licenseSeat): RedirectResponse
    {
        $this->authorize('update', $license);

        abort_unless(
            (int) $licenseSeat->license_id === (int) $license->id && $licenseSeat->assigned_to !== null,
            404,
        );

        $validated = $request->validate([
            'in_use' => ['required', 'boolean'],
            'user_confirmed' => ['required', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        $licenseSeat->loadMissing('user');

        LicenseSeatAudit::create([
            'license_id' => $license->id,
            'license_seat_id' => $licenseSeat->id,
            'assigned_to_id' => $licenseSeat->assigned_to,
            'assigned_to_name' => $licenseSeat->user?->display_name,
            'audited_by' => $request->user()->id,
            'in_use' => $validated['in_use'],
            'user_confirmed' => $validated['user_confirmed'],
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('licenses.seat-audits.index', $license)
            ->with('success', trans('admin/licenses/general.seat_audit.success'));
    }
}
