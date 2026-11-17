<?php

namespace App\Http\Controllers\Licenses;

use App\Http\Controllers\Controller;
use App\Models\License;
use App\Models\LicenseSeat;
use App\Models\LicenseSeatAudit;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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

        return view('licenses.audit-seats', compact('license', 'seats', 'auditsBySeat'));
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
