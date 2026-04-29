<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('productos')) {
            return;
        }

        Schema::table('productos', function (Blueprint $table) {
            if (! Schema::hasColumn('productos', 'metodo_valoracion')) {
                $table->string('metodo_valoracion', 10)->default('PEPS')->after('stock');
            }
        });

        if (! Schema::hasTable('lotes_importacion')) {
            Schema::create('lotes_importacion', function (Blueprint $table) {
                $table->increments('id');
                // BD legacy: productos.id es INT (signed). Debe matchear para FK en MySQL.
                $table->integer('producto_id')->index();
                $table->integer('cantidad_inicial')->default(0);
                $table->integer('cantidad_disponible')->default(0);
                $table->dateTime('fecha_ingreso');
                $table->decimal('costo_unitario', 14, 4)->default(0);

                $table->foreign('producto_id')->references('id')->on('productos')->onDelete('cascade');
            });
        }

        if (! Schema::hasTable('detalle_pedido_lotes')) {
            Schema::create('detalle_pedido_lotes', function (Blueprint $table) {
                $table->increments('id');
                // BD legacy: ids son INT (signed). Debe matchear para FK en MySQL.
                $table->integer('detalle_pedido_id')->index();
                // lotes_importacion.id (increments) es UNSIGNED en MySQL
                $table->unsignedInteger('lote_id')->index();
                $table->integer('cantidad')->default(0);
                $table->decimal('costo_unitario_snapshot', 14, 4)->default(0);

                $table->foreign('detalle_pedido_id')->references('id')->on('detalle_pedido')->onDelete('cascade');
                $table->foreign('lote_id')->references('id')->on('lotes_importacion')->onDelete('restrict');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('detalle_pedido_lotes')) {
            Schema::dropIfExists('detalle_pedido_lotes');
        }
        if (Schema::hasTable('lotes_importacion')) {
            Schema::dropIfExists('lotes_importacion');
        }
        if (Schema::hasTable('productos') && Schema::hasColumn('productos', 'metodo_valoracion')) {
            Schema::table('productos', function (Blueprint $table) {
                $table->dropColumn('metodo_valoracion');
            });
        }
    }
};
