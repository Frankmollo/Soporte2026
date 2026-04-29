<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('usuarios')) {
            return;
        }

        Schema::table('usuarios', function (Blueprint $table): void {
            if (!Schema::hasColumn('usuarios', 'rol')) {
                $table->string('rol', 30)->default('cliente')->after('es_mayorista');
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('usuarios') && Schema::hasColumn('usuarios', 'rol')) {
            Schema::table('usuarios', function (Blueprint $table): void {
                $table->dropColumn('rol');
            });
        }
    }
};

