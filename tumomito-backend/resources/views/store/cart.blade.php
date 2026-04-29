@extends('layouts.app')

@section('content')
<div class="header-section">
    <h1 class="gradient-text"><i class="fa-solid fa-cart-shopping"></i> Tu Carrito</h1>
    @if(!empty($esMayorista))
        <p class="channel-hint"><i class="fa-solid fa-store"></i> Tarifa mayorista aplicada</p>
    @endif
</div>

<div class="cart-layout">
    <div class="cart-items glass-panel">
        @if($carrito->isEmpty())
            <div class="empty-state">
                <i class="fa-solid fa-basket-shopping"></i>
                <p>Tu carrito está vacío.</p>
                <a href="{{ route('store.index') }}" class="btn-primary mt-3">Ir al catálogo</a>
            </div>
        @else
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Precio</th>
                        <th>Cantidad</th>
                        <th>Subtotal</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($carrito as $item)
                    <tr>
                        <td>
                            <strong>{{ $item->producto->nombre }}</strong>
                            <br><small class="text-muted">Cod: {{ $item->producto->codigo }}</small>
                        </td>
                        <td>Bs. {{ number_format($item->producto->precioParaCliente(!empty($esMayorista)), 2) }}</td>
                        <td>{{ $item->cantidad }}</td>
                        <td>Bs. {{ number_format($item->producto->precioParaCliente(!empty($esMayorista)) * $item->cantidad, 2) }}</td>
                        <td>
                            <form action="{{ route('cart.destroy', $item->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn-danger"><i class="fa-solid fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    @if(!$carrito->isEmpty())
    <div class="checkout-panel glass-panel">
        <h3>Resumen del Pedido</h3>
        <div class="summary-row">
            <span>Subtotal</span>
            <span>Bs. {{ number_format($total, 2) }}</span>
        </div>
        <div class="summary-row total-row">
            <span>Total a Pagar</span>
            <span class="gradient-text">Bs. {{ number_format($total, 2) }}</span>
        </div>

        <form action="{{ route('checkout.process') }}" method="POST" class="checkout-form">
            @csrf
            <h4>Datos de Facturación</h4>
            <div class="form-group">
                <label>NIT / CI</label>
                <input type="text" name="nit_ci" required placeholder="Ej: 12345678">
            </div>
            <div class="form-group">
                <label>Razón Social</label>
                <input type="text" name="razon_social" required placeholder="Nombre o Empresa">
            </div>
            <button type="submit" class="btn-success btn-block"><i class="fa-solid fa-credit-card"></i> Finalizar Compra</button>
        </form>
    </div>
    @endif
</div>
@endsection
