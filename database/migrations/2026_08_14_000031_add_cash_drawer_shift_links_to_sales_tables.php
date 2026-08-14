<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->foreignId('cash_drawer_shift_id')
                ->nullable()
                ->after('payment_method')
                ->constrained('cash_drawer_shifts')
                ->nullOnDelete();

            $table->index(['cash_drawer_shift_id', 'payment_method', 'status']);
        });

        Schema::table('sales_returns', function (Blueprint $table) {
            $table->foreignId('cash_drawer_shift_id')
                ->nullable()
                ->after('refund_method')
                ->constrained('cash_drawer_shifts')
                ->nullOnDelete();

            $table->index(['cash_drawer_shift_id', 'refund_method', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('sales_returns', function (Blueprint $table) {
            $table->dropForeign(['cash_drawer_shift_id']);
            $table->dropIndex(['cash_drawer_shift_id', 'refund_method', 'status']);
            $table->dropColumn('cash_drawer_shift_id');
        });

        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->dropForeign(['cash_drawer_shift_id']);
            $table->dropIndex(['cash_drawer_shift_id', 'payment_method', 'status']);
            $table->dropColumn('cash_drawer_shift_id');
        });
    }
};
