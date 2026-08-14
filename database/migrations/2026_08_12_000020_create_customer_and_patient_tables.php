<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 180)->index();
            $table->string('code', 80)->nullable()->unique();
            $table->string('phone', 40)->nullable()->index();
            $table->string('email')->nullable();
            $table->string('gstin', 30)->nullable()->index();
            $table->string('address_line_1', 200)->nullable();
            $table->string('address_line_2', 200)->nullable();
            $table->string('city', 120)->nullable()->index();
            $table->string('state', 120)->nullable()->index();
            $table->string('postal_code', 20)->nullable();
            $table->decimal('opening_balance', 14, 2)->default(0);
            $table->decimal('credit_limit', 14, 2)->nullable();
            $table->decimal('outstanding_balance', 14, 2)->default(0);
            $table->unsignedInteger('loyalty_points')->default(0);
            $table->boolean('reminder_consent')->default(false);
            $table->boolean('whatsapp_consent')->default(false);
            $table->boolean('sms_consent')->default(false);
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('full_name', 180)->index();
            $table->string('patient_code', 80)->nullable()->unique();
            $table->string('phone', 40)->nullable()->index();
            $table->string('email')->nullable();
            $table->date('date_of_birth')->nullable()->index();
            $table->string('gender', 30)->nullable()->index();
            $table->string('primary_doctor_name', 180)->nullable()->index();
            $table->string('address_line_1', 200)->nullable();
            $table->string('address_line_2', 200)->nullable();
            $table->string('city', 120)->nullable()->index();
            $table->string('state', 120)->nullable()->index();
            $table->string('postal_code', 20)->nullable();
            $table->text('allergies')->nullable();
            $table->text('medical_notes')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('reminder_consent')->default(false);
            $table->boolean('whatsapp_consent')->default(false);
            $table->boolean('sms_consent')->default(false);
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['customer_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patients');
        Schema::dropIfExists('customers');
    }
};
