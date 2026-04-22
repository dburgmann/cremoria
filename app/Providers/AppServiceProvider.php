<?php

namespace App\Providers;

use App\Services\Game\GameDataRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // GameDataRepository is backed by opcache'd config arrays — safe as a singleton
        $this->app->singleton(GameDataRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Load nested config/game/ subdirectory files.
        // Laravel's auto-loader only handles top-level config/ files.
        // Each file is merged under the 'game' namespace: config('game.buildings.1'), etc.
        $gameConfigPath = config_path('game');
        if (is_dir($gameConfigPath)) {
            foreach (glob("{$gameConfigPath}/*.php") as $file) {
                $key = 'game.' . basename($file, '.php');
                if (!config()->has($key)) {
                    config([$key => require $file]);
                }
            }
        }
    }
}
