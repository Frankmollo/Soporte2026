<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('facturas')) {
            return;
        }

        if (! Schema::hasTable('pedidos')) {
            return;
        }

        Schema::create('facturas', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('pedido_id');
            $table->string('nit_ci', 80);
            $table->string('razon_social', 255);
            $table->decimal('monto_total', 14, 2)->default(0);
            $table->dateTime('fecha_emision')->useCurrent();

            $table->foreign('pedido_id')->references('id')->on('pedidos')->restrictOnDelete();
            $table->unique('pedido_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facturas');
    }
};
