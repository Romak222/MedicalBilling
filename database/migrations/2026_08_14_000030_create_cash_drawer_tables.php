<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_drawer_shifts', function (Blueprint $table) {
            $table->id();
            $table->string('shift_number', 80)->unique();
            $table->string('status', 30)->default('open')->index();
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->decimal('opening_float', 14, 2)->default(0);
            $table->decimal('cash_sales_amount', 14, 2)->default(0);
            $table->decimal('cash_refunds_amount', 14, 2)->default(0);
            $table->decimal('cash_in_amount', 14, 2)->default(0);
            $table->decimal('cash_out_amount', 14, 2)->default(0);
            $table->decimal('expected_closing_cash', 14, 2)->nullable();
            $table->decimal('counted_closing_cash', 14, 2)->nullable();
            $table->decimal('variance_amount', 14, 2)->nullable();
            $table->text('opening_notes')->nullable();
            $table->text('closing_notes')->nullable();
            $table->foreignId('opened_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['opened_at', 'status']);
        });

        Schema::create('cash_drawer_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cash_drawer_shift_id')->constrained()->cascadeOnDelete();
            $table->string('entry_type', 30);
            $table->decimal('amount', 14, 2);
            $table->string('reason', 180);
            $table->nullableMorphs('reference');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['cash_drawer_shift_id', 'entry_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_drawer_entries');
        Schema::dropIfExists('cash_drawer_shifts');
    }
};
