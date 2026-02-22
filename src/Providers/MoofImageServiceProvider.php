<?php

namespace DeanHowe\Laravel\Moof\Providers;

use DeanHowe\Laravel\Moof\Console\Commands\MoofImageProcessCommand;
use Illuminate\Cache\CacheManager;
use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Support\ServiceProvider;

// use DeanHowe\Laravel\Moof\Livewire\SendInternalSystemMessageForm;

class MoofImageServiceProvider extends ServiceProvider
{
    protected bool $defer = false;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        // $this->registerConfig();

        if ($this->app->runningInConsole()) {
            $this->commands([
                MoofImageProcessCommand::class,
                // MoofMoofDeveloperCommand::class,
            ]);
        }


    }

    /**
     * Register Moof🐮Moof Image migration files.
     *
     * @return void
     */
    protected function registerMigrations()
    {
        // return $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }

    private function countProcessedDomainAssets(): string|int
    {
        //        $domainAssets = (new Filesystem)->allFiles(app()->exactProcessedDomainAssetsPath());
        //        return count($domainAssets);
        return 0;
    }

    private function countRawDomainAssets(): string|int
    {
        //        $domainAssets = (new Filesystem)->allFiles(app()->exactRawDomainAssetsPath());
        //        return count($domainAssets);
        return 0;
    }

    private function countPublishedDomainAssets(): string|int
    {
        //        $domainAssets = (new Filesystem)->files(app()->exactPublishedDomainAssetsPath());
        //        return count($domainAssets);
        return 0;
    }

    public function boot()
    {
        AboutCommand::add('MOOF 🐄 Images!', ' Version', '🐮.i.0');
        AboutCommand::add('MOOF 🐄 Images!', 'Assets RAW', $this->countRawDomainAssets());
        AboutCommand::add('MOOF 🐄 Images!', 'Assets Processed', $this->countProcessedDomainAssets());
        AboutCommand::add('MOOF 🐄 Images!', 'Assets Published', $this->countPublishedDomainAssets());

        // $this->loadRoutesFrom(__DIR__ . '/../../routes/images.php');
    }
}
