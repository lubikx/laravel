<?php
/**
 * @package php-tmdb\laravel
 * @author Mark Redeman <markredeman@gmail.com>
 * @copyright (c) 2014, Mark Redeman
 */
namespace Tmdb\Laravel;

use Illuminate\Support\ServiceProvider;

class TmdbServiceProviderLaravel extends ServiceProvider {

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->publishes([
            $this->defaultConfig() => $this->app->configPath('tmdb.php'),
        ]);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register(): void
    {
        $this->setupConfiguration();

        $this->app->bind('Tmdb\Laravel\Adapters\EventDispatcherAdapter', 'Tmdb\Laravel\Adapters\EventDispatcherLaravel');
    }

    /**
     * Get the TMDB configuration from the config repository
     *
     * @return array
     * @throws
     */
    public function config(): array
    {
        return $this->app['config']->get('tmdb');
    }

    /**
     * Setup configuration
     *
     * @return  void
     */
    private function setupConfiguration(): void
    {
        $config = $this->defaultConfig();
        $this->mergeConfigFrom($config, 'tmdb');
    }

    /**
     * Returns the default configuration path
     *
     * @return string
     */
    private function defaultConfig(): string
    {
        return __DIR__ . '/config/tmdb.php';
    }
}
