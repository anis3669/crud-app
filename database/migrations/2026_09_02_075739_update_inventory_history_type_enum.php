<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_histories', function (Blueprint $table) {
            $table->enum('type', [
                'stock_in',
                'stock_out',
                'sale',
                'adjustment',
            ])->change();
        });
    }

    public function down(): void
    {
        Schema::table('inventory_histories', function (Blueprint $table) {
            $table->enum('type', [
                'stock_in',
                'stock_out',
                'adjustment',
            ])->change();
        });
    }
};
