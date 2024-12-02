<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Http\Request;
use Laravel\Fortify\Fortify;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\{ServiceProvider, Str};
use App\Actions\Fortify\{CreateNewUser, ResetUserPassword, UpdateUserPassword, UpdateUserProfileInformation};
use App\Enums\OauthProvider;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

final class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower((string) $request->string(Fortify::username())) . '|' . $request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', fn(Request $request) => Limit::perMinute(5)->by($request->session()->get('login.id')));

        $this->configureLoginView();
    }

    private function configureLoginView(): void
    {
        Fortify::loginView(fn() => Inertia::render('Auth/Login', [
            'availableOauthProviders' => OauthProvider::cases(),
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]));
    }
}
