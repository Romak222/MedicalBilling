<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('controlled_medicine_register_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_batch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('patient_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('doctor_id')->nullable()->constrained('doctors')->nullOnDelete();
            $table->foreignId('prescription_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('prescription_item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sales_invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sales_invoice_item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sales_return_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sales_return_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('entry_type', 40)->index();
            $table->date('event_date')->index();
            $table->decimal('quantity_effect', 18, 6)->default(0);
            $table->string('product_name_snapshot', 180);
            $table->string('batch_number_snapshot', 120)->nullable();
            $table->string('patient_name_snapshot', 180)->nullable();
            $table->string('doctor_name_snapshot', 180)->nullable();
            $table->string('prescription_number_snapshot', 80)->nullable();
            $table->string('invoice_number_snapshot', 80)->nullable();
            $table->string('return_number_snapshot', 80)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['product_id', 'event_date']);
            $table->index(['patient_id', 'event_date']);
            $table->index(['doctor_id', 'event_date']);
            $table->index(['prescription_id', 'event_date']);
            $table->unique(['sales_invoice_item_id', 'entry_type']);
            $table->unique(['sales_return_item_id', 'entry_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('controlled_medicine_register_entries');
    }
};
