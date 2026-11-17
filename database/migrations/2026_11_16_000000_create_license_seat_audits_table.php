<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('license_seat_audits', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('license_id')->index();
            $table->integer('license_seat_id')->index();
            $table->integer('assigned_to_id')->nullable();
            $table->string('assigned_to_name')->nullable();
            $table->integer('audited_by')->nullable();
            $table->boolean('in_use');
            $table->boolean('user_confirmed');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('license_seat_audits');
    }
};
