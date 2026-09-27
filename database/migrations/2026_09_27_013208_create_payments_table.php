<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignUuid('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignUuid('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->uuid('payment_method_id');
            $table->decimal('amount', 15, 4);
            $table->string('reference_number')->nullable();
            $table->timestamp('payment_date')->useCurrent();
            $table->string('status')->default('completed'); // pending, completed, failed, refunded, cancelled
            $table->foreignUuid('received_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'payment_date']);
            $table->index(['customer_id']);
            $table->index(['invoice_id']);
            $table->index(['payment_method_id']);
            $table->index(['reference_number']);
            $table->index(['status']);
            $table->index(['received_by']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
