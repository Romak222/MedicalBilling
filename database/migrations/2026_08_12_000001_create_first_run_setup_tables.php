<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('legal_name')->nullable();
            $table->string('gstin', 20)->nullable();
            $table->string('pan', 20)->nullable();
            $table->string('drug_license_number')->nullable();
            $table->date('drug_license_valid_until')->nullable();
            $table->string('address_line_1')->nullable();
            $table->string('address_line_2')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
        });

        Schema::create('registered_pharmacists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('registration_number')->nullable();
            $table->string('council_name')->nullable();
            $table->date('license_valid_until')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->boolean('is_primary')->default(true);
            $table->timestamps();

            $table->index(['store_id', 'is_primary']);
        });

        Schema::create('application_settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->longText('value')->nullable();
            $table->string('type', 30)->default('string');
            $table->boolean('is_sensitive')->default(false);
            $table->timestamps();
        });

        Schema::create('first_run_setup_steps', function (Blueprint $table) {
            $table->id();
            $table->string('step_key')->unique();
            $table->string('label');
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('first_run_setup_steps');
        Schema::dropIfExists('application_settings');
        Schema::dropIfExists('registered_pharmacists');
        Schema::dropIfExists('stores');
    }
};
