<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTumomitoAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $rol = (string) $request->session()->get('tumomito_user_role', 'cliente');
        if ($rol !== 'admin') {
            return redirect()->route('store.index')
                ->with('error', 'No tienes permisos para acceder al ERP.');
        }

        return $next($request);
    }
}

