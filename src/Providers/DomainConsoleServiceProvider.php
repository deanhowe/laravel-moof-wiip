<?php

namespace DeanHowe\Laravel\Moof\Providers;

use App;
use BladeUI\Icons\Factory;
use DeanHowe\Laravel\Moof\Console\Commands\MoofMoofDeveloperCommand;
use DeanHowe\Laravel\Moof\Console\Commands\MoofMoofInstallCommand;
use DeanHowe\Laravel\Moof\MultiDomain\Console\AddDomainCommand;
use DeanHowe\Laravel\Moof\MultiDomain\Console\DomainCommand;
use DeanHowe\Laravel\Moof\MultiDomain\Console\ListDomainCommand;
use DeanHowe\Laravel\Moof\MultiDomain\Console\RemoveDomainCommand;
use DeanHowe\Laravel\Moof\MultiDomain\Console\UpdateEnvDomainCommand;
// use DeanHowe\Laravel\Moof\Livewire\GetInTouchContactForm;
// use DeanHowe\Laravel\Moof\Livewire\SendInternalSystemMessageForm;
use Illuminate\Contracts\Container\Container;
use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class DomainConsoleServiceProvider extends ServiceProvider
{
    protected bool $defer = false;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerConfig();

        // This adds shit to ARTISAN!
        // $this->app->alias('artisan', \DeanHowe\Laravel\Moof\Console\Application::class);

        $this->callAfterResolving(Factory::class, function (Factory $factory, Container $container) {
            $config = $container->make('config')->get('blade-mooficons', []);

            // ray($config);
            //
            //            $factory->add('mooficons', array_merge([
            //                'path' => __DIR__ . '/../../../resources/svg'
            //            ], $config
            //            ));
            // ray($config);
        });

        if ($this->app->runningInConsole()) {
            $this->commands([
                MoofMoofDeveloperCommand::class,
                MoofMoofInstallCommand::class,
                DomainCommand::class,
                AddDomainCommand::class,
                RemoveDomainCommand::class,
                UpdateEnvDomainCommand::class,
                ListDomainCommand::class,
            ]);
        }

    }

    /**
     * Register Moof's migration files.
     *
     * @return void
     */
    protected function registerMigrations()
    {
        // return $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }

    public function boot()
    {
        AboutCommand::add('MOOF 🐄', 'Version', '🐮.i.0');

        // Livewire::component('send-internal-system-message-form', SendInternalSystemMessageForm::class);
        // Livewire::component('get-in-touch-contact-form', GetInTouchContactForm::class);

        //        $this->routes(function () {
        //            Route::middleware('webhook')
        //                ->prefix('webhook')
        //                ->group(base_path('routes/webhooks.php'));
        //
        //        });
        // $this->loadRoutesFrom(__DIR__.'/../../routes/web.php');
        // $this->loadRoutesFrom(__DIR__ . '/../../routes/webhooks.php');

        if ($this->app->runningInConsole()) {

            $this->registerMigrations();

            $this->publishes([
                __DIR__ . '/../../../public/build' => $this->app->basePath('public/build'),
            ], 'moof-moof-public-assets');

            $this->publishes([
                __DIR__ . '/../../../stubs/resources/views' => $this->app->resourcePath('views'),
            ], 'moof-moof-views');

            $this->publishes([
                __DIR__ . '/../../../stubs/config/domain.php' => config_path('domain.php'),
            ], ['moof-moof-domain-config']);

            $this->publishes([
                __DIR__ . '/../../../stubs/config/database.php' => config_path('database.php'),
            ], ['moof-moof-database-config']);

            $this->publishes([
                __DIR__ . '/../../../stubs/config/queue.php' => config_path('queue.php'),
            ], ['moof-moof-queue-config']);

            $this->publishes([
                __DIR__ . '/../../../stubs/config/markdown.php' => config_path('markdown.php'),
            ], ['moof-moof-markdown-config']);

            $this->publishes([
                __DIR__ . '/../../../stubs/config/blade-mooficons.php' => $this->app->configPath('blade-mooficons.php'),
            ], 'blade-mooficons-config');

            $this->publishes([
                __DIR__ . '/../../../stubs/config' => config_path(),
                //                __DIR__ . '/../../../stubs/config/blade-icons.php' => config_path('blade-icons.php'),
                //                __DIR__ . '/../../../stubs/config/blade-heroicons.php' => config_path('blade-icons.php'),
                //                __DIR__ . '/../../../stubs/config/blade-mooficons.php' => $this->app->configPath('blade-mooficons.php'),
                //                __DIR__ . '/../../../stubs/config/broadcasting.php' => config_path('broadcasting.php'),
                //                __DIR__ . '/../../../stubs/config/cache.php' => config_path('cache.php'),
                //                __DIR__ . '/../../../stubs/config/cors.php' => config_path('cors.php'),
                //                __DIR__ . '/../../../stubs/config/database.php' => config_path('database.php'),
                //                __DIR__ . '/../../../stubs/config/filesystems.php' => config_path('filesystems.php'),
                //                __DIR__ . '/../../../stubs/config/flare.php' => config_path('flare.php'),
                //                __DIR__ . '/../../../stubs/config/fortify.php' => config_path('fortify.php'),
                //                __DIR__ . '/../../../stubs/config/hashing.php' => config_path('hashing.php'),
                //                __DIR__ . '/../../../stubs/config/ignition.php' => config_path('ignition.php'),
                //                __DIR__ . '/../../../stubs/config/image.php' => config_path('image.php'),
                //                __DIR__ . '/../../../stubs/config/jetstream.php' => config_path('jetstream.php'),
                //                __DIR__ . '/../../../stubs/config/livewire.php' => config_path('livewire.php'),
                //                __DIR__ . '/../../../stubs/config/logging.php' => config_path('logging.php'),
                //                __DIR__ . '/../../../stubs/config/mail.php' => config_path('mail.php'),
                //                __DIR__ . '/../../../stubs/config/markdown.php' => config_path('markdown.php'),
                //                __DIR__ . '/../../../stubs/config/navigation.php' => config_path('navigation.php'),
                //                __DIR__ . '/../../../stubs/config/openai.php' => config_path('openai.php'),
                //                __DIR__ . '/../../../stubs/config/purify.php' => config_path('purify.php'),
                //                __DIR__ . '/../../../stubs/config/queue.php' => config_path('queue.php'),
                //                __DIR__ . '/../../../stubs/config/sanctum.php' => config_path('sanctum.php'),
                //                __DIR__ . '/../../../stubs/config/services.php' => config_path('services.php'),
                //                __DIR__ . '/../../../stubs/config/session.php' => config_path('session.php'),
                //                __DIR__ . '/../../../stubs/config/tinker.php' => config_path('tinker.php'),
                //                __DIR__ . '/../../../stubs/config/view.php' => config_path('view.php'),
                //                __DIR__ . '/../../../stubs/config/wink.php' => config_path('wink.php'),

            ], ['moof-moof-configs']);

            $this->publishes([
                __DIR__ . '/../../../stubs/resources/svg' => public_path('vendor/moof'),
            ], 'blade-mooficons');

            $this->publishes([
                __DIR__ . '/../../../stubs/.env.dist' => $this->app->basePath('moof_envs/.env'),
            ], 'moof-moof-env');
        }
    }

    private function registerConfig(): void
    {
        //        $this->mergeConfigFrom(__DIR__ . '/../../../config/blade-mooficons.php', 'blade-mooficons');
    }
}
