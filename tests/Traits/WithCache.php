<?php

namespace Tests\Traits;

use Illuminate\Cache\CacheManager;

trait WithCache
{
    /**
     * The Cache instance.
     */
    protected CacheManager $cache;

    /**
     * Set up the Cache instance.
     */
    protected function setUpWithCache(): void
    {
        $this->cache = $this->makeCache();
    }

    /**
     * Create a Cache instance.
     */
    protected function makeCache(): CacheManager
    {
        return $this->app->make(CacheManager::class);
    }
}
