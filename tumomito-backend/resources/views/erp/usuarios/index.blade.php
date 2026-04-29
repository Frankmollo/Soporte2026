@extends('layouts.app')

@section('content')
<div class="glass-panel" style="padding:1.25rem">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap">
        <div>
            <h2 class="gradient-text" style="margin:0">Usuarios</h2>
            <div class="minor-label">Administración de clientes y administradores.</div>
        </div>
        <a class="btn-primary" href="{{ route('erp.usuarios.create') }}">
            <i class="fa-solid fa-user-plus"></i> Crear usuario
        </a>
    </div>

    <form method="GET" action="{{ route('erp.usuarios.index') }}" style="margin-top:1rem;display:flex;gap:0.5rem;flex-wrap:wrap">
        <input type="text" name="q" value="{{ $q }}" placeholder="Buscar por email o nombre" style="min-width:260px">
        <button type="submit" class="btn-success">Buscar</button>
        @if($q !== '')
            <a class="btn-primary" href="{{ route('erp.usuarios.index') }}">Limpiar</a>
        @endif
    </form>

    <div style="overflow:auto;margin-top:1rem">
        <table class="table" style="width:100%;min-width:900px">
            <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Email</th>
                <th>Rol</th>
                <th>Mayorista</th>
                <th>Dirección</th>
                <th style="width:120px">Acciones</th>
            </tr>
            </thead>
            <tbody>
            @forelse($usuarios as $u)
                <tr>
                    <td>{{ $u->id }}</td>
                    <td>{{ $u->nombre }}</td>
                    <td>{{ $u->email }}</td>
                    <td>
                        <span class="badge-method" style="background: {{ ($u->rol ?? 'cliente') === 'admin' ? 'rgba(255,120,120,0.18)' : 'rgba(120,255,120,0.14)' }};">
                            {{ $u->rol ?? 'cliente' }}
                        </span>
                    </td>
                    <td>{{ (int)($u->es_mayorista ?? 0) }}</td>
                    <td>{{ $u->direccion }}</td>
                    <td>
                        <a class="btn-primary" style="padding:0.35rem 0.6rem" href="{{ route('erp.usuarios.edit', ['id' => $u->id]) }}">
                            <i class="fa-solid fa-pen"></i>
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="minor-label">No hay usuarios.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top:1rem">
        {{ $usuarios->links() }}
    </div>
</div>
@endsection

