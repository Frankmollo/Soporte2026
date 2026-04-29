<?php

namespace App\Services;

use Illuminate\Http\Request;

class CurrentUserService
{
    public function id(Request $request): int
    {
        return (int) $request->session()->get('tumomito_user_id', config('tumomito.guest_user_id'));
    }

    public function nombre(Request $request): ?string
    {
        return $request->session()->get('tumomito_user_name');
    }
}
