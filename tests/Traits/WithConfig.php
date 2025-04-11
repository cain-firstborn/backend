<?php

namespace Tests\Traits;

use Illuminate\Config\Repository as Config;

trait WithConfig
{
    /**
     * The Config instance.
     */
    protected Config $config;

    /**
     * Set up the Config instance.
     */
    protected function setUpWithConfig(): void
    {
        $this->config = $this->makeConfig();
    }

    /**
     * Create a Config instance.
     */
    protected function makeConfig(): Config
    {
        return $this->app->make(Config::class);
    }
}
