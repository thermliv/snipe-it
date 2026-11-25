<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LicenseSeatAudit extends SnipeModel
{
    protected $fillable = [
        'license_id',
        'license_seat_id',
        'assigned_to_id',
        'assigned_to_name',
        'audited_by',
        'in_use',
        'user_confirmed',
        'notes',
    ];

    protected $casts = [
        'in_use' => 'boolean',
        'user_confirmed' => 'boolean',
    ];

    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class)->withTrashed();
    }

    public function licenseSeat(): BelongsTo
    {
        return $this->belongsTo(LicenseSeat::class);
    }

    public function auditor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'audited_by')->withTrashed();
    }
}
