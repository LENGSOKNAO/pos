<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('promotion_id')->constrained('promotions')->cascadeOnDelete();
            $table->string('code')->unique();
            $table->integer('usage_limit')->default(1);
            $table->integer('used_count')->default(0);
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['promotion_id']);
            $table->index(['code']);
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
