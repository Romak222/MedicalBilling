<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_return_items', function (Blueprint $table) {
            $table->foreignId('prescription_item_id')->nullable()->after('product_batch_id')->constrained('prescription_items')->nullOnDelete();
            $table->index(['sales_invoice_item_id', 'prescription_item_id']);
        });
    }

    public function down(): void
    {
        Schema::table('sales_return_items', function (Blueprint $table) {
            $table->dropIndex(['sales_invoice_item_id', 'prescription_item_id']);
            $table->dropConstrainedForeignId('prescription_item_id');
        });
    }
};
