<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignUuid('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignUuid('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('order_number')->unique();
            $table->timestamp('order_date')->useCurrent();
            $table->string('order_type')->default('pos'); // pos, online, wholesale, quotation
            $table->decimal('subtotal', 15, 4)->default(0);
            $table->decimal('discount', 15, 4)->default(0);
            $table->decimal('tax', 15, 4)->default(0);
            $table->decimal('total', 15, 4)->default(0);
            $table->string('status')->default('draft'); // draft, confirmed, processing, completed, cancelled, returned
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'status']);
            $table->index(['branch_id', 'status']);
            $table->index(['customer_id']);
            $table->index(['employee_id']);
            $table->index(['order_number']);
            $table->index(['order_date']);
            $table->index(['order_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_orders');
    }
};
