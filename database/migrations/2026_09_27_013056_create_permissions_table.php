<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique(); // e.g., 'products.view', 'products.create', 'sales.approve_discount'
            $table->string('name');
            $table->string('module'); // products, sales, inventory, purchasing, etc.
            $table->timestamps();

            $table->index(['module']);
            $table->index(['code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
