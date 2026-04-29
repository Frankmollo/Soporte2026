@extends('layouts.app')

@section('content')
<div class="header-section">
    <h1 class="gradient-text">Explora nuestro Catálogo</h1>
    <p>Descubre más de 1,500 productos exclusivos con la mejor calidad.</p>
    @if(!empty($esMayorista))
        <p class="channel-hint"><i class="fa-solid fa-store"></i> Vista mayorista activa (precios B2B)</p>
    @endif
</div>

<div class="catalog-layout">
    <aside class="sidebar glass-panel">
        <h3><i class="fa-solid fa-filter"></i> Categorías</h3>
        <ul class="category-list">
            <li><a href="{{ route('store.index') }}" class="{{ !request('categoria') ? 'active' : '' }}">Todas</a></li>
            @foreach($categorias as $cat)
                <li>
                    <a href="{{ route('store.index', ['categoria' => $cat->id]) }}" class="{{ request('categoria') == $cat->id ? 'active' : '' }}">
                        {{ $cat->nombre }}
                    </a>
                </li>
            @endforeach
        </ul>
    </aside>

    <section class="products-grid">
        @forelse($productos as $producto)
            <div class="product-card glass-panel">
                <div class="product-img">
                    <i class="fa-solid fa-box"></i>
                </div>
                <div class="product-info">
                    <span class="badge">{{ $producto->categoria ? $producto->categoria->nombre : 'Sin Categoría' }}</span>
                    @if(!empty($producto->metodo_valoracion))
                        <span class="badge badge-method" title="Método de valoración inventario">Almacén {{ $producto->metodo_valoracion }}</span>
                    @endif
                    <h4>{{ $producto->nombre }}</h4>
                    <p class="price">Bs. {{ number_format($producto->precioParaCliente(!empty($esMayorista)), 2) }}</p>
                    @if(!empty($esMayorista) && !is_null($producto->precio_mayorista))
                        <p class="minor-label">Ref. minorista: Bs. {{ number_format($producto->precio, 2) }}</p>
                    @endif
                    <p class="stock">Stock: {{ $producto->stock }}</p>
                    <form action="{{ route('cart.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="producto_id" value="{{ $producto->id }}">
                        <div class="add-to-cart-action">
                            <input type="number" name="cantidad" value="1" min="1" max="{{ $producto->stock }}" class="qty-input">
                            <button type="submit" class="btn-primary"><i class="fa-solid fa-plus"></i> Añadir</button>
                        </div>
                    </form>
                </div>
            </div>
        @empty
            <div class="empty-state glass-panel">
                <i class="fa-solid fa-folder-open"></i>
                <p>No se encontraron productos.</p>
            </div>
        @endforelse
    </section>
</div>

<div class="pagination-wrapper glass-panel">
    {{ $productos->withQueryString()->links() }}
</div>
@endsection
