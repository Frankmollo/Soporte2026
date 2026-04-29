@extends('layouts.app')

@section('content')
<div class="success-container glass-panel">
    <h1 class="gradient-text">Crear cuenta</h1>
    <p>Registro rápido para comprar y hacer checkout.</p>

    <form action="{{ route('auth.register') }}" method="POST" class="checkout-form mt-3">
        @csrf
        <div class="form-group">
            <label>Nombre</label>
            <input type="text" name="nombre" value="{{ old('nombre') }}" required>
        </div>
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" value="{{ old('email') }}" required>
        </div>
        <div class="form-group">
            <label>Dirección (opcional)</label>
            <input type="text" name="direccion" value="{{ old('direccion') }}">
        </div>
        <div class="form-group">
            <label>Contraseña</label>
            <input type="password" name="contrasena" required>
            <div class="minor-label">Prototipo: se guarda tal cual (sin hash).</div>
        </div>

        <button type="submit" class="btn-success btn-block">Registrarme</button>
        <div class="minor-label" style="margin-top:0.75rem">
            ¿Ya tienes cuenta? <a href="{{ route('auth.login.form') }}">Inicia sesión</a>
        </div>
    </form>
</div>
@endsection

