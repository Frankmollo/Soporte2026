@extends('layouts.app')

@section('content')
<div class="glass-panel" style="padding:1.25rem;max-width:860px;margin:0 auto">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap">
        <div>
            <h2 class="gradient-text" style="margin:0">{{ $mode === 'edit' ? 'Editar producto' : 'Nuevo producto' }}</h2>
            <div class="minor-label">Asigná categoría y precios. Cambiar stock manualmente puede desalinear lotes si ya existen movimientos.</div>
        </div>
        <a class="btn-primary" href="{{ route('erp.productos.index') }}">
            <i class="fa-solid fa-arrow-left"></i> Volver
        </a>
    </div>

    <form method="POST"
          action="{{ $mode === 'edit' ? route('erp.productos.update', ['id' => $producto->id]) : route('erp.productos.store') }}"
          class="checkout-form mt-3">
        @csrf

        <div class="form-group">
            <label>Nombre</label>
            <input type="text" name="nombre" value="{{ old('nombre', $producto->nombre ?? '') }}" required>
        </div>

        <div class="form-group">
            <label>Código</label>
            <input type="text" name="codigo" value="{{ old('codigo', $producto->codigo ?? '') }}" placeholder="Opcional, único">
        </div>

        <div class="form-group">
            <label>Categoría</label>
            <select name="categoria_id" class="qty-input" style="width:100%;height:44px" required>
                @php($cid = old('categoria_id', $producto->categoria_id ?? ''))
                @foreach($categorias as $c)
                    <option value="{{ $c->id }}" {{ (string)$cid === (string)$c->id ? 'selected' : '' }}>{{ $c->nombre }}</option>
                @endforeach
            </select>
            @if($categorias->isEmpty())
                <div class="minor-label" style="color:#f88;margin-top:0.35rem">
                    No hay categorías.
                    <a href="{{ route('erp.categorias.create') }}">Crear categoría</a>
                    o ejecutá <code style="font-size:0.85em">php artisan tumomito:poblar-demo</code>.
                </div>
            @endif
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem">
            <div class="form-group">
                <label>Precio venta (minorista)</label>
                <input type="number" name="precio" step="0.01" min="0" value="{{ old('precio', $producto->precio ?? '0') }}" required>
            </div>
            <div class="form-group">
                <label>Precio mayorista</label>
                <input type="number" name="precio_mayorista" step="0.01" min="0" value="{{ old('precio_mayorista', $producto->precio_mayorista ?? '') }}" placeholder="Vacío = usa precio minorista">
            </div>
        </div>

        <div style="display:grid;grid-template-columns:repeat(3, 1fr);gap:0.75rem">
            <div class="form-group">
                <label>Stock</label>
                <input type="number" name="stock" min="0" value="{{ old('stock', $producto->stock ?? 0) }}" required>
            </div>
            <div class="form-group">
                <label>Stock mínimo</label>
                <input type="number" name="stock_minimo" min="0" value="{{ old('stock_minimo', $producto->stock_minimo ?? 0) }}">
            </div>
            <div class="form-group">
                <label>Stock máximo</label>
                <input type="number" name="stock_maximo" min="0" value="{{ old('stock_maximo', $producto->stock_maximo ?? 0) }}">
            </div>
        </div>

        <div class="form-group">
            <label>Método de valoración (lotes)</label>
            <select name="metodo_valoracion" class="qty-input" style="width:100%;height:44px">
                @php($mv = old('metodo_valoracion', $producto->metodo_valoracion ?? 'PEPS'))
                <option value="PEPS" {{ $mv === 'PEPS' ? 'selected' : '' }}>PEPS</option>
                <option value="UEPS" {{ $mv === 'UEPS' ? 'selected' : '' }}>UEPS</option>
            </select>
        </div>

        <button type="submit" class="btn-success btn-block" style="width:100%;margin-top:0.5rem">
            {{ $mode === 'edit' ? 'Guardar cambios' : 'Crear producto' }}
        </button>
    </form>
</div>
@endsection
