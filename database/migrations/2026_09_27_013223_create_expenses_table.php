<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->uuid('category_id')->nullable();
            $table->foreignUuid('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('expense_number')->unique();
            $table->text('description');
            $table->decimal('amount', 15, 4);
            $table->foreignUuid('payment_method_id')->constrained('payment_methods')->cascadeOnDelete();
            $table->timestamp('expense_date')->useCurrent();
            $table->string('status')->default('draft'); // draft, pending_approval, approved, paid, rejected, cancelled
            $table->foreignUuid('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'status']);
            $table->index(['branch_id', 'status']);
            $table->index(['category_id']);
            $table->index(['employee_id']);
            $table->index(['expense_number']);
            $table->index(['expense_date']);
            $table->index(['created_by']);
            $table->index(['approved_by']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
