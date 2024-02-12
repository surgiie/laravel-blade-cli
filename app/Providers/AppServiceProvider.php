<?php

namespace App\Providers;

use App\Engine\FileCompiler;
use App\Engine\FileCompilerEngine;
use App\Engine\FileFactory;
use App\Engine\FileFinder;
use Illuminate\View\DynamicComponent;
use Illuminate\View\ViewServiceProvider;

class AppServiceProvider extends ViewServiceProvider
{
    /**
     * Register the Blade engine implementation.
     *
     * @param  \Illuminate\View\Engines\EngineResolver  $resolver
     * @return void
     */
    public function registerBladeEngine($resolver)
    {
        $resolver->register('blade', function () {

            $compiler = new FileCompilerEngine($this->app['blade.compiler'], $this->app['files']);

            $this->app->terminating(static function () use ($compiler) {
                $compiler->forgetCompiledOrNotExpired();
            });

            return $compiler;
        });
    }

    /**
     * Register the Blade compiler implementation.
     *
     * @return void
     */
    public function registerBladeCompiler()
    {
        $this->app->singleton('blade.compiler', function ($app) {
            return tap(new FileCompiler(
                $app['files'],
                $app['config']['view.compiled'],
                $app['config']->get('view.relative_hash', false) ? $app->basePath() : '',
                $app['config']->get('view.cache', true),
                $app['config']->get('view.compiled_extension', 'php'),
            ), function ($blade) {
                $blade->component('dynamic-component', DynamicComponent::class);
            });
        });
    }

    /**
     * Register the view finder implementation.
     *
     * @return void
     */
    public function registerViewFinder()
    {
        $this->app->bind('view.finder', function ($app) {
            return new FileFinder($app['files'], $app['config']['view.paths']);
        });
    }

    /**
     * Create a new FileFactory Instance.
     *
     * @param  \Illuminate\View\Engines\EngineResolver  $resolver
     * @param  \Illuminate\View\ViewFinderInterface  $finder
     * @param  \Illuminate\Contracts\Events\Dispatcher  $events
     * @return \Illuminate\View\Factory
     */
    protected function createFactory($resolver, $finder, $events)
    {
        return new FileFactory($resolver, $finder, $events);
    }
}
