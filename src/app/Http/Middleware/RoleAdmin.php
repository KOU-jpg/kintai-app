<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleAdmin
{
    public function handle(Request $request, Closure $next)
    {
        // ユーザーが認証されていて、roleが'admin'なら通す
        if (Auth::check() && Auth::user()->role === 'admin') {
            return $next($request);
        }

        // それ以外は403エラー
        abort(403, 'このページへのアクセス権限がありません');
    }
}