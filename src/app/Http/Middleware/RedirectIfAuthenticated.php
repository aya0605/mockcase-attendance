<?php

namespace App\Http\Middleware;

use App\Providers\RouteServiceProvider;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @param  string|null  ...$guards
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                // 認証済みユーザーがログインページにアクセスした際のリダイレクト先を決定
                $user = Auth::guard($guard)->user();
                
                // ユーザーのroleが1（管理者）の場合
                if ($user && $user->role === 1) {
                    // 管理者用のダッシュボードページにリダイレクト
                    return redirect('/admin/dashboard');
                }

                // それ以外（一般ユーザー）の場合は通常のHOMEにリダイレクト
                return redirect(RouteServiceProvider::HOME);
            }
        }

        return $next($request);
    }
}
