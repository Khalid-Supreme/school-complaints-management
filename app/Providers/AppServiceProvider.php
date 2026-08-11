<?php

namespace App\Providers;

use App\Models\ComplaintCategory;
use App\Models\Department;
use App\Models\User;
use App\Observers\ComplaintCategoryObserver;
use App\Observers\DepartmentObserver;
use App\Support\AuditContext;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Single shared instance so the AuditContext middleware and the
        // AuditEventListener observe the same request-scoped values.
        $this->app->singleton(AuditContext::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Define the admin-access gate for the security dashboard
        Gate::define('admin-access', function (User $user) {
            return $user->role->slug === 'admin';
        });

        // Throttle login to 5 attempts per minute per username + IP.
        RateLimiter::for('login', function (Request $request) {
            $identifier = mb_strtolower((string) $request->input('username')).'|'.$request->ip();

            return Limit::perMinute(5)->by($identifier);
        });

        // Throttle public registration/resend to 10 attempts per hour per IP.
        RateLimiter::for('register', function (Request $request) {
            return Limit::perHour(10)->by($request->ip());
        });

        // Throttle password reset requests to 5 per hour per email + IP.
        RateLimiter::for('password-reset', function (Request $request) {
            $identifier = mb_strtolower((string) $request->input('email')).'|'.$request->ip();

            return Limit::perHour(5)->by($identifier);
        });

        // Audit category/department mutations even when called outside an HTTP
        // request (the AuditContext middleware supplies network context there).
        ComplaintCategory::observe(ComplaintCategoryObserver::class);
        Department::observe(DepartmentObserver::class);
    }
}
