<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('licenses', function (Blueprint $table): void {
            $table->string('pool_audit_revision', 36)->default('0');
        });

        Schema::create('license_pool_audits', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('license_id')->index();
            $table->integer('audited_by')->nullable();
            $table->integer('recorded_seats');
            $table->integer('verified_seats');
            $table->string('pool_audit_revision', 36);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('license_pool_audits');

        Schema::table('licenses', function (Blueprint $table): void {
            $table->dropColumn('pool_audit_revision');
        });
    }
};
