<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Hash; 
use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(
            \Laravel\Fortify\Contracts\LoginResponse::class,
            fn () => redirect('/login/redirect')
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);

        Fortify::registerView(function () {
            return view('auth.register');
        });

        Fortify::loginView(function () {
            return view('auth.login');
        });

        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->email;

            return Limit::perMinute(10)->by($email . $request->ip());
        });

       Fortify::authenticateUsing(function (Request $request) {
            $user = User::where('email', $request->email)->first();

            // 認証が成功した場合、ユーザーオブジェクトを返します。
            if ($user && Hash::check($request->password, $user->password)) {
                return $user;
            }

            // 認証に失敗した場合は、nullを返します。
            return null;
        });

    }
}
