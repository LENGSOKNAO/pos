<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignUuid('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('request_type'); // refund, discount, price_override, stock_adjustment, purchase_order, expense, credit, supplier_payment, cash_withdrawal, invoice_cancel
            $table->uuid('reference_id');
            $table->foreignUuid('requested_by')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('pending'); // pending, approved, rejected, cancelled
            $table->text('reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->timestamp('approved_at')->nullable();

            $table->index(['company_id', 'status']);
            $table->index(['branch_id', 'status']);
            $table->index(['request_type']);
            $table->index(['reference_id']);
            $table->index(['requested_by']);
            $table->index(['approved_by']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_requests');
    }
};
