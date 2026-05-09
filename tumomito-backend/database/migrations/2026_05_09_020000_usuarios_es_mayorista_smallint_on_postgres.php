<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Con pooler + PDO::ATTR_EMULATE_PREPARES=true Laravel puede mandar 0 entero a columnas boolean;
     * Postgres rechaza (42804). SMALLINT acepta 0/1 y el cast boolean del modelo sigue funcionando.
     */
    public function up(): void
    {
        if (! Schema::hasTable('usuarios') || ! Schema::hasColumn('usuarios', 'es_mayorista')) {
            return;
        }

        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE usuarios ALTER COLUMN es_mayorista DROP DEFAULT');
        DB::statement(
            'ALTER TABLE usuarios ALTER COLUMN es_mayorista TYPE SMALLINT USING (CASE WHEN es_mayorista THEN 1 ELSE 0 END)'
        );
        DB::statement('ALTER TABLE usuarios ALTER COLUMN es_mayorista SET DEFAULT 0');
    }

    public function down(): void
    {
        if (! Schema::hasTable('usuarios') || ! Schema::hasColumn('usuarios', 'es_mayorista')) {
            return;
        }

        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE usuarios ALTER COLUMN es_mayorista DROP DEFAULT');
        DB::statement(
            'ALTER TABLE usuarios ALTER COLUMN es_mayorista TYPE BOOLEAN USING (es_mayorista <> 0)'
        );
        DB::statement('ALTER TABLE usuarios ALTER COLUMN es_mayorista SET DEFAULT FALSE');
    }
};
