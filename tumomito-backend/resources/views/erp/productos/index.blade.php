@extends('layouts.app')

@section('content')
<div class="glass-panel" style="padding:1.25rem">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap">
        <div>
            <h2 class="gradient-text" style="margin:0">Productos por categoría</h2>
            <div class="minor-label">Solo administradores. Listado ordenado por categoría; filtra o busca por nombre/código.
                <a href="{{ route('erp.categorias.index') }}">Administrar categorías</a>.
            </div>
        </div>
        <a class="btn-primary" href="{{ route('erp.productos.create') }}">
            <i class="fa-solid fa-plus"></i> Nuevo producto
        </a>
    </div>

    <form method="GET" action="{{ route('erp.productos.index') }}" style="margin-top:1rem;display:flex;gap:0.5rem;flex-wrap:wrap;align-items:flex-end">
        <div class="form-group" style="margin:0;min-width:200px">
            <label class="minor-label">Categoría</label>
            <select name="categoria_id" class="qty-input" style="width:100%;height:44px">
                <option value="" {{ $categoria_id === '' ? 'selected' : '' }}>Todas</option>
                @foreach($categorias as $c)
                    <option value="{{ $c->id }}" {{ (string)$c->id === $categoria_id ? 'selected' : '' }}>{{ $c->nombre }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group" style="margin:0;min-width:220px">
            <label class="minor-label">Buscar</label>
            <input type="text" name="q" value="{{ $q }}" placeholder="Nombre o código" style="width:100%;height:44px">
        </div>
        <button type="submit" class="btn-success" style="height:44px">Aplicar</button>
        @if($q !== '' || $categoria_id !== '')
            <a class="btn-primary" href="{{ route('erp.productos.index') }}" style="height:44px;display:inline-flex;align-items:center">Limpiar</a>
        @endif
    </form>

    <div style="overflow:auto;margin-top:1rem">
        <table class="table" style="width:100%;min-width:920px">
            <thead>
            <tr>
                <th>ID</th>
                <th>Categoría</th>
                <th>Código</th>
                <th>Nombre</th>
                <th>Precio</th>
                <th>Mayorista</th>
                <th>Stock</th>
                <th>Valoración</th>
                <th style="width:100px">Acciones</th>
            </tr>
            </thead>
            <tbody>
            @forelse($productos as $p)
                <tr>
                    <td>{{ $p->id }}</td>
                    <td>{{ $p->categoria->nombre ?? '—' }}</td>
                    <td>{{ $p->codigo ?? '—' }}</td>
                    <td>{{ $p->nombre }}</td>
                    <td>Bs. {{ number_format((float)$p->precio, 2) }}</td>
                    <td>@if($p->precio_mayorista !== null) Bs. {{ number_format((float)$p->precio_mayorista, 2) }} @else — @endif</td>
                    <td>{{ (int)$p->stock }}</td>
                    <td>{{ $p->metodo_valoracion ?? '—' }}</td>
                    <td>
                        <a class="btn-primary" style="padding:0.35rem 0.6rem" href="{{ route('erp.productos.edit', ['id' => $p->id]) }}">
                            <i class="fa-solid fa-pen"></i>
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="minor-label">No hay productos con los filtros actuales.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
        @if($productos->hasPages())
            <div style="margin-top:0.75rem">{{ $productos->links() }}</div>
        @endif
    </div>
</div>
@endsection
