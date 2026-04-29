@extends('layouts.app')

@section('content')
<div class="success-container glass-panel">
    <h1 class="gradient-text">Iniciar Sesión</h1>
    <p>Accede para operar carrito, checkout y panel ERP.</p>

    <form action="{{ route('auth.login') }}" method="POST" class="checkout-form mt-3">
        @csrf
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" value="{{ old('email') }}" required>
        </div>
        <div class="form-group">
            <label>Contraseña</label>
            <input type="password" name="contrasena" required>
        </div>
        <button type="submit" class="btn-success btn-block">Ingresar</button>
    </form>

    <div class="minor-label" style="margin-top:0.75rem">
        ¿No tienes cuenta? <a href="{{ route('auth.register.form') }}">Regístrate</a>
    </div>
</div>
@endsection
