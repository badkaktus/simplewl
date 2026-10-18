<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    public const HOME = '/my-wishlist';

    public const GENERATE_DESCRIPTION_PER_MINUTE = 5;

    public const GENERATE_DESCRIPTION_PER_DAY = 50;

    public const WISH_IMPORT_PER_MINUTE = 10;

    public function boot(): void
    {
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)->by($request->user()?->id ?: $request->ip()));

        // Every generation is a paid OpenAI request
        RateLimiter::for('generate-description', fn (Request $request): array => [
            Limit::perMinute(self::GENERATE_DESCRIPTION_PER_MINUTE)->by('minute:'.$request->user()?->id),
            Limit::perDay(self::GENERATE_DESCRIPTION_PER_DAY)->by('day:'.$request->user()?->id),
        ]);

        // Every import makes the server request a third-party page
        RateLimiter::for('wish-import', fn (Request $request): Limit => Limit::perMinute(self::WISH_IMPORT_PER_MINUTE)
            ->by((string) $request->user()?->id));

        $this->routes(function (): void {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
