<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignUuid('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->decimal('amount', 15, 4);
            $table->foreignUuid('payment_method_id')->constrained('payment_methods')->cascadeOnDelete();
            $table->timestamp('payment_date')->useCurrent();
            $table->string('reference_number')->nullable();
            $table->foreignUuid('received_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['customer_id', 'payment_date']);
            $table->index(['invoice_id']);
            $table->index(['payment_method_id']);
            $table->index(['reference_number']);
            $table->index(['received_by']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_payments');
    }
};
