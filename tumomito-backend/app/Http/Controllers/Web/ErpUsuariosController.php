<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class ErpUsuariosController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));

        $query = Usuario::query()->orderByDesc('id');
        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('email', 'like', "%{$q}%")
                    ->orWhere('nombre', 'like', "%{$q}%");
            });
        }

        $usuarios = $query->paginate(15)->withQueryString();

        return view('erp.usuarios.index', [
            'usuarios' => $usuarios,
            'q' => $q,
        ]);
    }

    public function create(): View
    {
        return view('erp.usuarios.form', [
            'usuario' => null,
            'mode' => 'create',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nombre' => 'required|string|min:2|max:255',
            'email' => 'required|email|max:255',
            'direccion' => 'nullable|string|max:255',
            'contrasena' => 'required|string|min:4|max:255',
            'rol' => 'nullable|string|max:30',
            'es_mayorista' => 'nullable|boolean',
        ]);

        if (Usuario::query()->where('email', $data['email'])->exists()) {
            return back()->withInput()->with('error', 'Ya existe un usuario con ese email.');
        }

        $payload = [
            'nombre' => $data['nombre'],
            'email' => $data['email'],
            'direccion' => $data['direccion'] ?? null,
            'contrasena' => $data['contrasena'],
        ];

        if (Schema::hasColumn('usuarios', 'rol')) {
            $rol = in_array(($data['rol'] ?? 'cliente'), ['cliente', 'admin'], true) ? $data['rol'] : 'cliente';
            $payload['rol'] = $rol;
        }

        if (Schema::hasColumn('usuarios', 'es_mayorista')) {
            $payload['es_mayorista'] = (bool) ($data['es_mayorista'] ?? false);
        }

        Usuario::query()->create($payload);

        return redirect()->route('erp.usuarios.index')->with('success', 'Usuario creado.');
    }

    public function edit(int $id): View
    {
        $usuario = Usuario::query()->findOrFail($id);

        return view('erp.usuarios.form', [
            'usuario' => $usuario,
            'mode' => 'edit',
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $usuario = Usuario::query()->findOrFail($id);

        $data = $request->validate([
            'nombre' => 'required|string|min:2|max:255',
            'email' => 'required|email|max:255',
            'direccion' => 'nullable|string|max:255',
            'contrasena' => 'nullable|string|min:4|max:255',
            'rol' => 'nullable|string|max:30',
            'es_mayorista' => 'nullable|boolean',
        ]);

        $emailExists = Usuario::query()
            ->where('email', $data['email'])
            ->where('id', '!=', $usuario->id)
            ->exists();
        if ($emailExists) {
            return back()->withInput()->with('error', 'Otro usuario ya tiene ese email.');
        }

        $usuario->nombre = $data['nombre'];
        $usuario->email = $data['email'];
        $usuario->direccion = $data['direccion'] ?? null;

        if (!empty($data['contrasena'])) {
            $usuario->contrasena = $data['contrasena'];
        }

        if (Schema::hasColumn('usuarios', 'rol')) {
            $rol = in_array(($data['rol'] ?? 'cliente'), ['cliente', 'admin'], true) ? $data['rol'] : 'cliente';
            $usuario->rol = $rol;
        }

        if (Schema::hasColumn('usuarios', 'es_mayorista')) {
            $usuario->es_mayorista = (bool) ($data['es_mayorista'] ?? false);
        }

        $usuario->save();

        return redirect()->route('erp.usuarios.index')->with('success', 'Usuario actualizado.');
    }
}

