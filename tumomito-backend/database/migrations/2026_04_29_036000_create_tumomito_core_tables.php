<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * BD original del proyecto vivía fuera del esqueleto de Laravel (sin migraciones base).
     * En Postgres nuevo (ej. Supabase) las migraciones posteriores no creaban estas tablas.
     */
    public function up(): void
    {
        if (! Schema::hasTable('categorias')) {
            Schema::create('categorias', function (Blueprint $table): void {
                $table->increments('id');
                $table->string('nombre', 255);
            });
        }

        if (! Schema::hasTable('usuarios')) {
            Schema::create('usuarios', function (Blueprint $table): void {
                $table->increments('id');
                $table->string('nombre', 255);
                $table->string('email', 255)->unique();
                $table->string('contrasena', 255);
                $table->text('direccion')->nullable();
                $table->boolean('es_mayorista')->default(false);
                $table->string('rol', 30)->default('cliente');
            });
        }

        if (! Schema::hasTable('productos')) {
            Schema::create('productos', function (Blueprint $table): void {
                $table->increments('id');
                $table->string('codigo', 100)->nullable();
                $table->string('nombre', 255);
                $table->decimal('precio', 10, 2)->default(0);
                $table->decimal('precio_mayorista', 10, 2)->nullable();
                $table->integer('stock')->default(0);
                $table->integer('stock_minimo')->default(0);
                $table->integer('stock_maximo')->default(0);
                $table->string('metodo_valoracion', 10)->default('PEPS');
                $table->unsignedInteger('categoria_id')->nullable();
                $table->foreign('categoria_id')->references('id')->on('categorias')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('pedidos')) {
            Schema::create('pedidos', function (Blueprint $table): void {
                $table->increments('id');
                $table->unsignedInteger('usuario_id');
                $table->dateTime('fecha')->nullable();
                $table->string('estado', 50)->default('pendiente');
                $table->string('canal_venta', 30)->default('web');
                $table->string('estado_pago', 30)->default('pagado');
                $table->string('estado_logistico', 30)->default('pendiente');
                $table->decimal('total', 14, 2)->default(0);
                $table->foreign('usuario_id')->references('id')->on('usuarios')->restrictOnDelete();
            });
        }

        if (! Schema::hasTable('detalle_pedido')) {
            Schema::create('detalle_pedido', function (Blueprint $table): void {
                $table->increments('id');
                $table->unsignedInteger('pedido_id');
                $table->integer('producto_id');
                $table->integer('cantidad');
                $table->decimal('precio_unitario', 10, 2);
                $table->decimal('subtotal', 14, 2)->default(0);
                $table->foreign('pedido_id')->references('id')->on('pedidos')->cascadeOnDelete();
                $table->foreign('producto_id')->references('id')->on('productos')->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('detalle_pedido');
        Schema::dropIfExists('pedidos');
        Schema::dropIfExists('productos');
        Schema::dropIfExists('usuarios');
        Schema::dropIfExists('categorias');
    }
};
