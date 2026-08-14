<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctors', function (Blueprint $table) {
            $table->id();
            $table->string('name', 180)->index();
            $table->string('registration_number', 120)->nullable()->unique();
            $table->string('specialization', 180)->nullable()->index();
            $table->string('clinic_name', 180)->nullable()->index();
            $table->string('phone', 40)->nullable()->index();
            $table->string('alternate_phone', 40)->nullable();
            $table->string('email')->nullable();
            $table->string('address_line_1', 200)->nullable();
            $table->string('address_line_2', 200)->nullable();
            $table->string('city', 120)->nullable()->index();
            $table->string('state', 120)->nullable()->index();
            $table->string('postal_code', 20)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['clinic_name', 'is_active']);
        });

        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();
            $table->string('prescription_number', 80)->unique();
            $table->foreignId('patient_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('doctor_id')->nullable()->constrained('doctors')->nullOnDelete();
            $table->string('patient_name_snapshot', 180)->nullable();
            $table->string('patient_phone_snapshot', 40)->nullable();
            $table->string('doctor_name_snapshot', 180)->nullable();
            $table->date('prescription_date')->index();
            $table->date('valid_until')->nullable()->index();
            $table->string('status', 30)->default('open')->index();
            $table->string('attachment_path')->nullable();
            $table->string('attachment_original_name', 255)->nullable();
            $table->string('attachment_mime_type', 120)->nullable();
            $table->text('notes')->nullable();
            $table->text('pharmacist_notes')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['patient_id', 'status']);
            $table->index(['doctor_id', 'status']);
            $table->index(['is_active', 'prescription_date']);
        });

        Schema::create('prescription_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prescription_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('medicine_name_snapshot', 180);
            $table->string('unit_name_snapshot', 80)->nullable();
            $table->string('dosage_instructions', 180)->nullable();
            $table->decimal('quantity_prescribed', 18, 6)->default(0);
            $table->decimal('quantity_dispensed', 18, 6)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['prescription_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescription_items');
        Schema::dropIfExists('prescriptions');
        Schema::dropIfExists('doctors');
    }
};
