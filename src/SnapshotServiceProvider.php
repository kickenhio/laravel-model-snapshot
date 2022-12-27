<?php namespace Kickenhio\LaravelSqlSnapshot;

use Illuminate\Support\ServiceProvider;

class SnapshotServiceProvider extends ServiceProvider
{
    /**
     * @return void
     */
    public function register(): void
    {
        $this->app->bind('kickenhio.snapshot', function ($app) {
            return new LaravelSqlSnapshot($app['config']);
        });

        $this->app->alias('kickenhio.snapshot', LaravelSqlSnapshot::class);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<string>
     */
    public function provides(): array
    {
        return ['kickenhio.snapshot'];
    }

    /**
     * {@inheritdoc}
     */
    public function boot() {
        if ($this->app->runningInConsole()) {
            $this->publishes([ $this->getConfigFile() => config_path('snapshot.php') ], 'config');
            $this->publishes([ $this->getManifestFile() => resource_path('example.json') ], 'manifest');
        }
    }

    /**
     * @return string
     */
    protected function getConfigFile(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'snapshot.php';
    }

    /**
     * @return string
     */
    protected function getManifestFile(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'example.json';
    }
}