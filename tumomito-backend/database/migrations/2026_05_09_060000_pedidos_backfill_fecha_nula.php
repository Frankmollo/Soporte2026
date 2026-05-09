<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pedidos sin `fecha` quedaban fuera del BI (filtro whereBetween).
     */
    public function up(): void
    {
        if (! Schema::hasTable('pedidos') || ! Schema::hasColumn('pedidos', 'fecha')) {
            return;
        }

        DB::table('pedidos')->whereNull('fecha')->update(['fecha' => now()]);
    }

    public function down(): void
    {
        //
    }
};
