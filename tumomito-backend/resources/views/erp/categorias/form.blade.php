@extends('layouts.app')

@section('content')
<div class="glass-panel" style="padding:1.25rem;max-width:560px;margin:0 auto">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap">
        <div>
            <h2 class="gradient-text" style="margin:0">{{ $mode === 'edit' ? 'Editar categoría' : 'Nueva categoría' }}</h2>
            <div class="minor-label">El nombre debe ser único.</div>
        </div>
        <a class="btn-primary" href="{{ route('erp.categorias.index') }}">
            <i class="fa-solid fa-arrow-left"></i> Volver
        </a>
    </div>

    <form method="POST"
          action="{{ $mode === 'edit' ? route('erp.categorias.update', ['id' => $categoria->id]) : route('erp.categorias.store') }}"
          class="checkout-form mt-3">
        @csrf

        <div class="form-group">
            <label>Nombre</label>
            <input type="text" name="nombre" value="{{ old('nombre', $categoria->nombre ?? '') }}" required maxlength="255" placeholder="Ej. Electrónica">
        </div>

        <button type="submit" class="btn-success btn-block" style="width:100%;margin-top:0.5rem">
            {{ $mode === 'edit' ? 'Guardar' : 'Crear categoría' }}
        </button>
    </form>
</div>
@endsection
