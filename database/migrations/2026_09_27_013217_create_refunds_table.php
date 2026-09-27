<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refunds', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('sales_return_id')->constrained('sales_returns')->cascadeOnDelete();
            $table->foreignUuid('payment_method_id')->constrained('payment_methods')->cascadeOnDelete();
            $table->decimal('amount', 15, 4);
            $table->string('reference_number')->nullable();
            $table->foreignUuid('refunded_by')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('refunded_at')->useCurrent();
            $table->string('status')->default('pending'); // pending, approved, completed, rejected, cancelled
            $table->timestamps();
            $table->softDeletes();

            $table->index(['sales_return_id']);
            $table->index(['payment_method_id']);
            $table->index(['refunded_by']);
            $table->index(['approved_by']);
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refunds');
    }
};
