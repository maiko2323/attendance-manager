<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;

class LogoutResponse implements LogoutResponseContract
{
    public function toResponse($request)
    {

        $loginType = $request->session()->pull('login_type');

        return $loginType === 'admin'
            ? redirect('/admin/login')
            : redirect('/login');
    }
}
