<?php

declare(strict_types=1);

namespace SharpAPI\ContentDetectPhones;

use Illuminate\Support\ServiceProvider;

/**
 * @api
 */
class ContentDetectPhonesProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/sharpapi-content-detect-phones.php' => config_path('sharpapi-content-detect-phones.php'),
            ], 'sharpapi-content-detect-phones');
        }
    }

    /**
     * Register the application services.
     */
    public function register(): void
    {
        // Merge the package configuration with the app configuration.
        $this->mergeConfigFrom(
            __DIR__.'/../config/sharpapi-content-detect-phones.php', 'sharpapi-content-detect-phones'
        );
    }
}