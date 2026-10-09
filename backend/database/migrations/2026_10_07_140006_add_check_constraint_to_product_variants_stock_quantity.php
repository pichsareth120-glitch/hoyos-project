<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE product_variants ADD CONSTRAINT product_variants_stock_quantity_check CHECK (stock_quantity >= 0)');
        } elseif ($driver === 'mysql') {
            DB::statement('ALTER TABLE product_variants ADD CONSTRAINT product_variants_stock_quantity_check CHECK (stock_quantity >= 0)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE product_variants DROP CONSTRAINT IF EXISTS product_variants_stock_quantity_check');
        } elseif ($driver === 'mysql') {
            DB::statement('ALTER TABLE product_variants DROP CHECK product_variants_stock_quantity_check');
        }
    }
};
