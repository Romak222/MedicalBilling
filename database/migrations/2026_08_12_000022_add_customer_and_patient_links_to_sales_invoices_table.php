<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->foreignId('customer_id')->nullable()->after('invoice_date')->constrained()->nullOnDelete();
            $table->foreignId('patient_id')->nullable()->after('customer_id')->constrained()->nullOnDelete();
            $table->string('patient_name', 180)->nullable()->after('customer_phone');
            $table->string('patient_phone', 40)->nullable()->after('patient_name');

            $table->index(['customer_id', 'status']);
            $table->index(['patient_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->dropIndex(['customer_id', 'status']);
            $table->dropIndex(['patient_id', 'status']);
            $table->dropConstrainedForeignId('patient_id');
            $table->dropConstrainedForeignId('customer_id');
            $table->dropColumn(['patient_name', 'patient_phone']);
        });
    }
};
