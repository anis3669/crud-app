<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('
            ALTER TABLE products
            ADD CONSTRAINT products_price_non_negative
            CHECK (price >= 0)
        ');

        DB::statement('
            ALTER TABLE products
            ADD CONSTRAINT products_quantity_non_negative
            CHECK (quantity >= 0)
        ');
    }

    public function down(): void
    {
        DB::statement('
            ALTER TABLE products
            DROP CONSTRAINT products_price_non_negative
        ');

        DB::statement('
            ALTER TABLE products
            DROP CONSTRAINT products_quantity_non_negative
        ');
    }
};
