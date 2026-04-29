@extends('layouts.app')

@section('content')
<div class="success-container glass-panel text-center">
    <div class="success-icon">
        <i class="fa-solid fa-circle-check"></i>
    </div>
    <h1 class="gradient-text">¡Compra Exitosa!</h1>
    <p>Tu pedido ha sido procesado correctamente y el stock ha sido actualizado.</p>
    
    <div class="invoice-box">
        <h3>Factura N° {{ str_pad($factura->id, 6, '0', STR_PAD_LEFT) }}</h3>
        <div class="invoice-details">
            <p><strong>Pedido ID:</strong> #{{ $pedido->id }}</p>
            @if(!empty($pedido->canal_venta))
                <p><strong>Canal:</strong> {{ strtoupper($pedido->canal_venta) }}</p>
            @endif
            @if(!empty($pedido->estado_logistico))
                <p><strong>Estado logístico:</strong> {{ ucfirst($pedido->estado_logistico) }}</p>
            @endif
            <p><strong>NIT/CI:</strong> {{ $factura->nit_ci }}</p>
            <p><strong>Razón Social:</strong> {{ $factura->razon_social }}</p>
            <p><strong>Fecha:</strong> {{ $factura->fecha_emision }}</p>
            <h2 class="mt-3">Total: Bs. {{ number_format($factura->monto_total, 2) }}</h2>
        </div>
    </div>

    <a href="{{ route('store.index') }}" class="btn-primary mt-4"><i class="fa-solid fa-arrow-left"></i> Volver a la Tienda</a>
</div>
@endsection
