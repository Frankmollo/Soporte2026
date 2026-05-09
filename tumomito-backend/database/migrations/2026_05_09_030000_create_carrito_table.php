<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('carrito')) {
            return;
        }

        if (! Schema::hasTable('usuarios') || ! Schema::hasTable('productos')) {
            return;
        }

        Schema::create('carrito', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('usuario_id');
            $table->integer('producto_id');
            $table->integer('cantidad')->default(1);

            $table->foreign('usuario_id')->references('id')->on('usuarios')->cascadeOnDelete();
            $table->foreign('producto_id')->references('id')->on('productos')->cascadeOnDelete();
            $table->unique(['usuario_id', 'producto_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carrito');
    }
};
