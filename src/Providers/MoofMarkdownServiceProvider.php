<?php

namespace DeanHowe\Laravel\Moof\Providers;

use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Support\Str;
use Illuminate\Support\ServiceProvider;
use Spatie\LaravelMarkdown\MarkdownRenderer;

class MoofMarkdownServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    public function boot(): void
    {
        AboutCommand::add('MOOF 🐄 Markdown!', 'Version', '🐮.i.0');

        // $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'moof-markdown');
        Str::macro('spatieMarkdown', function ($string = []) {
            return app(MarkdownRenderer::class)->toHtml($string);
        });
    }
}