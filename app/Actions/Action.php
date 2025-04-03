<?php

namespace App\Actions;

use Illuminate\Cache\CacheManager;

abstract class Action
{
    /**
     * Create a new Action instance.
     */
    public function __construct(protected CacheManager $cache)
    {
        //
    }
}
