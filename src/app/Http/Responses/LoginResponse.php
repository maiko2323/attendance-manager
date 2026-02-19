<?php

namespace App\Http\Responses;

use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        $user = $request->user();

        //管理者ログイン画面から来た場合は、intendedを無視して管理者へ
        if ($request->input('login_type') === 'admin') {

            // 一般ユーザーがadmin画面からログインしたら弾く
            if (! $user || $user->role !== 'admin') {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()
                    ->route('admin.login')
                    ->withErrors(['email' => '管理者アカウントではありません。'])
                    ->onlyInput('email');
            }
            $request->session()->put('login_type', 'admin');
            $request->session()->forget('url.intended');

            return redirect()->route('admin.attendance.list');
        }

        $request->session()->forget('login_type');
        return redirect()->route('attendance.index');
    }
}
