<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('reference_type')->nullable(); // invoice, payment, expense, purchase, etc.
            $table->uuid('reference_id')->nullable();
            $table->timestamp('entry_date')->useCurrent();
            $table->text('description')->nullable();
            $table->string('status')->default('draft'); // draft, posted, reversed
            $table->foreignUuid('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'entry_date']);
            $table->index(['reference_type', 'reference_id']);
            $table->index(['status']);
            $table->index(['created_by']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entries');
    }
};
