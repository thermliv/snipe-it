<?php

namespace Database\Factories;

use App\Models\License;
use App\Models\LicensePoolAudit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LicensePoolAudit>
 */
class LicensePoolAuditFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'license_id' => License::factory(),
            'audited_by' => User::factory(),
            'recorded_seats' => 1,
            'verified_seats' => 1,
            'pool_audit_revision' => '0',
            'notes' => null,
        ];
    }
}
