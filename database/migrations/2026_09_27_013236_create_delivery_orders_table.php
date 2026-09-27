<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('sales_order_id')->constrained('sales_orders')->cascadeOnDelete();
            $table->foreignUuid('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignUuid('delivery_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->text('address');
            $table->decimal('delivery_fee', 15, 4)->default(0);
            $table->string('status')->default('pending'); // pending, assigned, in_transit, delivered, failed, cancelled
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['sales_order_id']);
            $table->index(['customer_id']);
            $table->index(['delivery_employee_id', 'status']);
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_orders');
    }
};
