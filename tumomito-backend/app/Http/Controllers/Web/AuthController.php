<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => 'required|email',
            'contrasena' => 'required|string|min:1',
        ]);

        $usuario = Usuario::query()->where('email', $data['email'])->first();
        if (!$usuario || $usuario->contrasena !== $data['contrasena']) {
            return back()->withInput()->with('error', 'Credenciales inválidas.');
        }

        $request->session()->put('tumomito_user_id', (int) $usuario->id);
        $request->session()->put('tumomito_user_name', (string) $usuario->nombre);
        $request->session()->put('tumomito_user_mayorista', (bool) ($usuario->es_mayorista ?? false));
        $request->session()->put('tumomito_user_role', (string) ($usuario->rol ?? 'cliente'));

        return redirect()->route('store.index')->with('success', 'Sesión iniciada correctamente.');
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nombre' => 'required|string|min:2|max:255',
            'email' => 'required|email|max:255',
            'direccion' => 'nullable|string|max:255',
            'contrasena' => 'required|string|min:4|max:255',
        ]);

        $exists = Usuario::query()->where('email', $data['email'])->exists();
        if ($exists) {
            return back()->withInput()->with('error', 'Ese email ya está registrado.');
        }

        $usuario = Usuario::query()->create([
            'nombre' => $data['nombre'],
            'email' => $data['email'],
            'direccion' => $data['direccion'] ?? null,
            'contrasena' => $data['contrasena'],
            'rol' => 'cliente',
            'es_mayorista' => false,
        ]);

        $request->session()->put('tumomito_user_id', (int) $usuario->id);
        $request->session()->put('tumomito_user_name', (string) $usuario->nombre);
        $request->session()->put('tumomito_user_mayorista', false);
        $request->session()->put('tumomito_user_role', 'cliente');

        return redirect()->route('store.index')->with('success', 'Cuenta creada y sesión iniciada.');
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget(['tumomito_user_id', 'tumomito_user_name', 'tumomito_user_mayorista', 'tumomito_user_role']);

        return redirect()->route('auth.login.form')->with('success', 'Sesión cerrada.');
    }
}
