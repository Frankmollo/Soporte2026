@extends('layouts.app')

@section('content')
<div class="glass-panel" style="padding:1.25rem">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap">
        <div>
            <h2 class="gradient-text" style="margin:0">Categorías</h2>
            <div class="minor-label">Gestioná las categorías del catálogo (solo administradores).</div>
        </div>
        <a class="btn-primary" href="{{ route('erp.categorias.create') }}">
            <i class="fa-solid fa-folder-plus"></i> Nueva categoría
        </a>
    </div>

    <form method="GET" action="{{ route('erp.categorias.index') }}" style="margin-top:1rem;display:flex;gap:0.5rem;flex-wrap:wrap">
        <input type="text" name="q" value="{{ $q }}" placeholder="Buscar por nombre" style="min-width:240px;height:44px">
        <button type="submit" class="btn-success" style="height:44px">Buscar</button>
        @if($q !== '')
            <a class="btn-primary" href="{{ route('erp.categorias.index') }}" style="height:44px;display:inline-flex;align-items:center">Limpiar</a>
        @endif
    </form>

    <div style="overflow:auto;margin-top:1rem">
        <table class="table" style="width:100%;min-width:640px">
            <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Productos</th>
                <th style="width:180px">Acciones</th>
            </tr>
            </thead>
            <tbody>
            @forelse($categorias as $c)
                <tr>
                    <td>{{ $c->id }}</td>
                    <td>{{ $c->nombre }}</td>
                    <td>{{ (int)($c->productos_count ?? 0) }}</td>
                    <td style="display:flex;gap:0.35rem;flex-wrap:wrap;align-items:center">
                        <a class="btn-primary" style="padding:0.35rem 0.6rem" href="{{ route('erp.categorias.edit', ['id' => $c->id]) }}">
                            <i class="fa-solid fa-pen"></i>
                        </a>
                        <form method="POST" action="{{ route('erp.categorias.destroy', ['id' => $c->id]) }}" style="display:inline"
                              onsubmit="return confirm('¿Eliminar esta categoría? Los productos pueden quedar sin categoría.');">
                            @csrf
                            <button type="submit" class="btn-primary" style="padding:0.35rem 0.6rem;background:rgba(220,80,80,0.35);border:none;cursor:pointer">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="minor-label">No hay categorías con los filtros actuales.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
        @if($categorias->hasPages())
            <div style="margin-top:0.75rem">{{ $categorias->links() }}</div>
        @endif
    </div>

    <p class="minor-label" style="margin-top:1rem">
        Para cargar productos usá <a href="{{ route('erp.productos.index') }}">Catálogo (por categoría)</a>.
    </p>
</div>
@endsection
