<?php

namespace DeanHowe\Laravel\Moof\Providers;

use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;

/**
 * https://sebastiandedeyne.com/theme-based-views-in-laravel-using-vendor-namespaces
 */
class ThemeServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot(Request $request): void
    {
        \URL::forceScheme('https');

        //$currentDomain = domain_sanitized(app()->domain());
        // ray('Theme Views', resource_path('views/themes/'. $currentDomain) .'/');

        $views = [
          //  resource_path('views/x_moof_themes/'. $currentDomain .'/'),
            //resource_path('views/x_moof_themes/_default-pages/'),
            resource_path('views/components'),
            resource_path('views/'),
            //__DIR__ . '/../../stubs/resources/views/_default-pages/',
            #__DIR__ . '/../../stubs/resources/views/components/',
            #__DIR__ . '/../../stubs/resources/views/',
        ];

        $this->loadViewsFrom($views, 'theme');
    }
}
