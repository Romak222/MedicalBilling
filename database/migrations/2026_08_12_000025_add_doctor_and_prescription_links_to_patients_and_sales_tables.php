<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->foreignId('primary_doctor_id')->nullable()->after('customer_id')->constrained('doctors')->nullOnDelete();
            $table->index(['primary_doctor_id', 'is_active']);
        });

        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->foreignId('doctor_id')->nullable()->after('patient_id')->constrained('doctors')->nullOnDelete();
            $table->foreignId('prescription_id')->nullable()->after('doctor_id')->constrained('prescriptions')->nullOnDelete();
            $table->string('doctor_name', 180)->nullable()->after('patient_phone');
            $table->string('prescription_number', 80)->nullable()->after('doctor_name');
            $table->index(['doctor_id', 'status']);
            $table->index(['prescription_id', 'status']);
        });

        Schema::table('sales_invoice_items', function (Blueprint $table) {
            $table->foreignId('prescription_item_id')->nullable()->after('product_batch_id')->constrained('prescription_items')->nullOnDelete();
            $table->index(['sales_invoice_id', 'prescription_item_id']);
        });
    }

    public function down(): void
    {
        Schema::table('sales_invoice_items', function (Blueprint $table) {
            $table->dropIndex(['sales_invoice_id', 'prescription_item_id']);
            $table->dropConstrainedForeignId('prescription_item_id');
        });

        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->dropIndex(['doctor_id', 'status']);
            $table->dropIndex(['prescription_id', 'status']);
            $table->dropColumn(['doctor_name', 'prescription_number']);
            $table->dropConstrainedForeignId('prescription_id');
            $table->dropConstrainedForeignId('doctor_id');
        });

        Schema::table('patients', function (Blueprint $table) {
            $table->dropIndex(['primary_doctor_id', 'is_active']);
            $table->dropConstrainedForeignId('primary_doctor_id');
        });
    }
};
