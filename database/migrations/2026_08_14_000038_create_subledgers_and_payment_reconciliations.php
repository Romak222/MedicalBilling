<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_ledger_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->date('entry_date')->index();
            $table->string('entry_type', 40)->index();
            $table->string('status', 20)->default('posted')->index();
            $table->string('source_type', 180)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->decimal('debit', 14, 2)->default(0);
            $table->decimal('credit', 14, 2)->default(0);
            $table->string('description', 255);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['source_type', 'source_id', 'entry_type']);
            $table->index(['customer_id', 'entry_date']);
            $table->index(['source_type', 'source_id']);
        });

        Schema::create('supplier_ledger_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->date('entry_date')->index();
            $table->string('entry_type', 40)->index();
            $table->string('status', 20)->default('posted')->index();
            $table->string('source_type', 180)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->decimal('debit', 14, 2)->default(0);
            $table->decimal('credit', 14, 2)->default(0);
            $table->string('description', 255);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['source_type', 'source_id', 'entry_type']);
            $table->index(['supplier_id', 'entry_date']);
            $table->index(['source_type', 'source_id']);
        });

        Schema::create('payment_reconciliations', function (Blueprint $table): void {
            $table->id();
            $table->string('reconciliation_number', 80)->unique();
            $table->string('payment_method', 30)->index();
            $table->date('period_from');
            $table->date('period_to');
            $table->date('settlement_date')->index();
            $table->string('settlement_reference', 160)->unique();
            $table->decimal('expected_amount', 14, 2)->default(0);
            $table->decimal('settled_amount', 14, 2)->default(0);
            $table->decimal('fee_amount', 14, 2)->default(0);
            $table->string('status', 30)->default('pending')->index();
            $table->foreignId('journal_entry_id')->nullable()->constrained()->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('reconciled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['payment_method', 'period_from', 'period_to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_reconciliations');
        Schema::dropIfExists('supplier_ledger_entries');
        Schema::dropIfExists('customer_ledger_entries');
    }
};
