<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('bank_account_id')->constrained('bank_accounts')->cascadeOnDelete();
            $table->string('transaction_type'); // deposit, withdrawal, transfer_in, transfer_out, fee, interest
            $table->string('reference_type')->nullable(); // payment, supplier_payment, expense, transfer
            $table->uuid('reference_id')->nullable();
            $table->decimal('amount', 15, 4);
            $table->decimal('balance_after', 15, 4);
            $table->timestamp('transaction_date')->useCurrent();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['bank_account_id', 'transaction_date']);
            $table->index(['reference_type', 'reference_id']);
            $table->index(['transaction_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_transactions');
    }
};
