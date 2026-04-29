<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTumomitoUser
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->session()->has('tumomito_user_id')) {
            return redirect()->route('auth.login.form')
                ->with('error', 'Debes iniciar sesión para continuar.');
        }

        return $next($request);
    }
}
