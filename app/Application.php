<?php

namespace App;

use Illuminate\Filesystem\Filesystem;
use LaravelZero\Framework\Application as FrameworkApplication;
use LaravelZero\Framework\ProviderRepository;

class Application extends FrameworkApplication
{
    /**
     * Register all the configured providers.
     */
    public function registerConfiguredProviders(): void
    {
        // only load the providers from the config file
        (new ProviderRepository($this, new Filesystem, $this->getCachedServicesPath()))->load(
            $this['config']['app.providers']
        );
    }
}
