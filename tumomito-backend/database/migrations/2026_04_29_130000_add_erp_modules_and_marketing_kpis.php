<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('usuarios')) {
            Schema::table('usuarios', function (Blueprint $table): void {
                if (!Schema::hasColumn('usuarios', 'es_mayorista')) {
                    $table->boolean('es_mayorista')->default(false)->after('direccion');
                }
            });
        }

        if (Schema::hasTable('productos')) {
            Schema::table('productos', function (Blueprint $table): void {
                if (!Schema::hasColumn('productos', 'precio_mayorista')) {
                    $table->decimal('precio_mayorista', 10, 2)->nullable()->after('precio');
                }
                if (!Schema::hasColumn('productos', 'stock_minimo')) {
                    $table->integer('stock_minimo')->default(0)->after('stock');
                }
                if (!Schema::hasColumn('productos', 'stock_maximo')) {
                    $table->integer('stock_maximo')->default(0)->after('stock_minimo');
                }
            });
        }

        if (Schema::hasTable('pedidos')) {
            Schema::table('pedidos', function (Blueprint $table): void {
                if (!Schema::hasColumn('pedidos', 'canal_venta')) {
                    $table->string('canal_venta', 30)->default('web')->after('estado');
                }
                if (!Schema::hasColumn('pedidos', 'estado_pago')) {
                    $table->string('estado_pago', 30)->default('pagado')->after('canal_venta');
                }
                if (!Schema::hasColumn('pedidos', 'estado_logistico')) {
                    $table->string('estado_logistico', 30)->default('pendiente')->after('estado_pago');
                }
            });
        }

        if (!Schema::hasTable('proveedores')) {
            Schema::create('proveedores', function (Blueprint $table): void {
                $table->increments('id');
                $table->string('nombre', 255);
                $table->string('contacto', 255)->nullable();
                $table->string('telefono', 100)->nullable();
                $table->string('email', 255)->nullable();
                $table->text('direccion')->nullable();
            });
        }

        if (!Schema::hasTable('compras') && Schema::hasTable('proveedores')) {
            Schema::create('compras', function (Blueprint $table): void {
                $table->increments('id');
                $table->unsignedInteger('proveedor_id');
                $table->dateTime('fecha')->nullable();
                $table->string('estado', 30)->default('recibida');
                $table->decimal('total', 14, 2)->default(0);
                $table->string('referencia', 120)->nullable();

                $table->foreign('proveedor_id')->references('id')->on('proveedores')->onDelete('restrict');
            });
        }

        if (!Schema::hasTable('compra_detalle') && Schema::hasTable('compras') && Schema::hasTable('productos')) {
            Schema::create('compra_detalle', function (Blueprint $table): void {
                $table->increments('id');
                $table->unsignedInteger('compra_id');
                // BD legacy: productos.id es INT (signed). Debe matchear para FK.
                $table->integer('producto_id');
                $table->integer('cantidad');
                $table->decimal('costo_unitario', 14, 4);
                $table->decimal('subtotal', 14, 2)->default(0);

                $table->foreign('compra_id')->references('id')->on('compras')->onDelete('cascade');
                $table->foreign('producto_id')->references('id')->on('productos')->onDelete('restrict');
            });
        }

        if (!Schema::hasTable('inventario_movimientos') && Schema::hasTable('productos')) {
            Schema::create('inventario_movimientos', function (Blueprint $table): void {
                $table->increments('id');
                // BD legacy: productos.id es INT (signed). Debe matchear para FK.
                $table->integer('producto_id');
                $table->string('tipo', 20); // entrada|salida|ajuste
                $table->integer('cantidad');
                $table->integer('stock_anterior')->default(0);
                $table->integer('stock_nuevo')->default(0);
                $table->string('referencia_tipo', 50)->nullable(); // compra|pedido|manual
                $table->unsignedInteger('referencia_id')->nullable();
                $table->dateTime('fecha');
                $table->text('nota')->nullable();

                $table->index(['producto_id', 'fecha']);
                $table->foreign('producto_id')->references('id')->on('productos')->onDelete('cascade');
            });
        }

        if (!Schema::hasTable('marketing_kpis_diarios')) {
            Schema::create('marketing_kpis_diarios', function (Blueprint $table): void {
                $table->increments('id');
                $table->date('fecha');
                $table->string('canal', 40); // facebook|tiktok|general
                $table->decimal('inversion', 14, 2)->default(0);
                $table->integer('visitas')->default(0);
                $table->integer('leads')->default(0);
                $table->integer('ventas')->default(0);
                $table->decimal('ingresos', 14, 2)->default(0);
                $table->decimal('cac', 14, 2)->nullable();
                $table->decimal('roas', 14, 4)->nullable();
                $table->decimal('conversion_rate', 8, 4)->nullable();
                $table->integer('recompras')->default(0);
                $table->decimal('ticket_promedio', 14, 2)->nullable();
                $table->decimal('abandono_carrito', 8, 4)->nullable();

                $table->unique(['fecha', 'canal']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('marketing_kpis_diarios')) {
            Schema::dropIfExists('marketing_kpis_diarios');
        }
        if (Schema::hasTable('inventario_movimientos')) {
            Schema::dropIfExists('inventario_movimientos');
        }
        if (Schema::hasTable('compra_detalle')) {
            Schema::dropIfExists('compra_detalle');
        }
        if (Schema::hasTable('compras')) {
            Schema::dropIfExists('compras');
        }
        if (Schema::hasTable('proveedores')) {
            Schema::dropIfExists('proveedores');
        }

        if (Schema::hasTable('pedidos')) {
            Schema::table('pedidos', function (Blueprint $table): void {
                foreach (['estado_logistico', 'estado_pago', 'canal_venta'] as $col) {
                    if (Schema::hasColumn('pedidos', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('productos')) {
            Schema::table('productos', function (Blueprint $table): void {
                foreach (['stock_maximo', 'stock_minimo', 'precio_mayorista'] as $col) {
                    if (Schema::hasColumn('productos', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('usuarios') && Schema::hasColumn('usuarios', 'es_mayorista')) {
            Schema::table('usuarios', function (Blueprint $table): void {
                $table->dropColumn('es_mayorista');
            });
        }
    }
};
