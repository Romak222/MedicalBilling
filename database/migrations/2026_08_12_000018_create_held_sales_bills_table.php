<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('held_sales_bills', function (Blueprint $table) {
            $table->id();
            $table->string('hold_number', 80)->unique();
            $table->string('customer_name', 180)->nullable();
            $table->string('customer_phone', 40)->nullable();
            $table->json('payload');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('held_sales_bills');
    }
};
