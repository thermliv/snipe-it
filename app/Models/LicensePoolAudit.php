<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LicensePoolAudit extends SnipeModel
{
    use HasFactory;

    protected $fillable = [
        'license_id',
        'audited_by',
        'recorded_seats',
        'verified_seats',
        'pool_audit_revision',
        'notes',
    ];

    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class)->withTrashed();
    }

    public function auditor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'audited_by')->withTrashed();
    }
}
