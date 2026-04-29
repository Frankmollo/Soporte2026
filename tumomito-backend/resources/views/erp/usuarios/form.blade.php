@extends('layouts.app')

@section('content')
<div class="glass-panel" style="padding:1.25rem;max-width:860px;margin:0 auto">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap">
        <div>
            <h2 class="gradient-text" style="margin:0">{{ $mode === 'edit' ? 'Editar usuario' : 'Crear usuario' }}</h2>
            <div class="minor-label">Administra rol, mayorista y credenciales (modo prototipo).</div>
        </div>
        <a class="btn-primary" href="{{ route('erp.usuarios.index') }}">
            <i class="fa-solid fa-arrow-left"></i> Volver
        </a>
    </div>

    <form method="POST"
          action="{{ $mode === 'edit' ? route('erp.usuarios.update', ['id' => $usuario->id]) : route('erp.usuarios.store') }}"
          class="checkout-form mt-3">
        @csrf

        <div class="form-group">
            <label>Nombre</label>
            <input type="text" name="nombre" value="{{ old('nombre', $usuario->nombre ?? '') }}" required>
        </div>

        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" value="{{ old('email', $usuario->email ?? '') }}" required>
        </div>

        <div class="form-group">
            <label>Dirección</label>
            <input type="text" name="direccion" value="{{ old('direccion', $usuario->direccion ?? '') }}">
        </div>

        <div class="form-group">
            <label>{{ $mode === 'edit' ? 'Contraseña (dejar vacío para mantener)' : 'Contraseña' }}</label>
            <input type="password" name="contrasena">
            <div class="minor-label">Prototipo: se guarda tal cual (sin hash).</div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem">
            <div class="form-group">
                <label>Rol</label>
                <select name="rol">
                    @php($currentRol = old('rol', $usuario->rol ?? 'cliente'))
                    <option value="cliente" {{ $currentRol === 'cliente' ? 'selected' : '' }}>cliente</option>
                    <option value="admin" {{ $currentRol === 'admin' ? 'selected' : '' }}>admin</option>
                </select>
            </div>

            <div class="form-group">
                <label>Mayorista (B2B)</label>
                @php($currentMay = (int) old('es_mayorista', (int)($usuario->es_mayorista ?? 0)))
                <select name="es_mayorista">
                    <option value="0" {{ $currentMay === 0 ? 'selected' : '' }}>No</option>
                    <option value="1" {{ $currentMay === 1 ? 'selected' : '' }}>Sí</option>
                </select>
            </div>
        </div>

        <button type="submit" class="btn-success btn-block" style="margin-top:0.5rem">
            {{ $mode === 'edit' ? 'Guardar cambios' : 'Crear usuario' }}
        </button>
    </form>
</div>
@endsection

