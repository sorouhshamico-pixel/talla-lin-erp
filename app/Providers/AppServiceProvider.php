<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        RateLimiter::for(
            'saved-view-retention-summary-cache-diagnostics-refresh',
            function (Request $request): Limit {
                $identity = $request->user()
                    ? 'user:' . $request->user()->getAuthIdentifier()
                    : 'ip:' . $request->ip();

                return Limit::perMinute(30)->by(
                    hash('sha256', $identity)
                );
            }
        );

        Gate::define(
            'manage_saved_view_share_activity_retention',
            fn (User $user): bool => $user->isOwner()
        );
    }
}
