<?php

namespace App\Providers;

use App\Cms\PermissionRegistry;
use App\Cms\SiteContext;
use App\Models\Article;
use App\Models\CaseStudy;
use App\Models\Industry;
use App\Models\MediaAsset;
use App\Models\Page;
use App\Models\Solution;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SiteContext::class, fn () => new SiteContext);
    }

    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        Relation::enforceMorphMap([
            'page' => Page::class,
            'article' => Article::class,
            'case_study' => CaseStudy::class,
            'solution' => Solution::class,
            'industry' => Industry::class,
            'media' => MediaAsset::class,
            'user' => User::class,
        ]);

        Gate::before(function (User $user) {
            return $user->isSuperAdmin() ? true : null;
        });

        foreach (PermissionRegistry::allSlugs() as $permission) {
            Gate::define($permission, function (User $user, $site = null) use ($permission) {
                return $user->hasPermission(
                    $permission,
                    $site ?? $this->app->make(SiteContext::class)->site()
                );
            });
        }

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by(mb_strtolower((string) $request->input('email')).'|'.$request->ip());
        });

        RateLimiter::for('public-forms', function (Request $request) {
            return Limit::perMinute(8)->by($request->ip());
        });

        RateLimiter::for('search', function (Request $request) {
            return Limit::perMinute(40)->by($request->ip());
        });

        RateLimiter::for('uploads', function (Request $request) {
            return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
        });
    }
}
