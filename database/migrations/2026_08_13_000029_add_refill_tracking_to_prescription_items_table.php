<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prescription_items', function (Blueprint $table): void {
            $table->unsignedSmallInteger('refill_interval_days')->nullable()->after('quantity_dispensed');
            $table->unsignedSmallInteger('refill_reminder_days')->default(0)->after('refill_interval_days');
            $table->date('last_dispensed_on')->nullable()->after('refill_reminder_days');
            $table->date('next_refill_due_on')->nullable()->after('last_dispensed_on');
        });
    }

    public function down(): void
    {
        Schema::table('prescription_items', function (Blueprint $table): void {
            $table->dropColumn([
                'refill_interval_days',
                'refill_reminder_days',
                'last_dispensed_on',
                'next_refill_due_on',
            ]);
        });
    }
};
