<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('register_id')->constrained('cash_registers')->cascadeOnDelete();
            $table->foreignUuid('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->decimal('opening_cash', 15, 4)->default(0);
            $table->decimal('closing_cash', 15, 4)->nullable();
            $table->decimal('expected_cash', 15, 4)->nullable();
            $table->decimal('difference', 15, 4)->nullable();
            $table->timestamp('opened_at')->useCurrent();
            $table->timestamp('closed_at')->nullable();
            $table->string('status')->default('open'); // open, closed, reconciled
            $table->timestamps();
            $table->softDeletes();

            $table->index(['register_id', 'status']);
            $table->index(['employee_id']);
            $table->index(['opened_at']);
            $table->index(['closed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_sessions');
    }
};
