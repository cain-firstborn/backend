<?php

namespace Tests\Traits;

use Livewire\LivewireManager;

trait WithLivewire
{
    /**
     * The Livewire instance.
     */
    protected LivewireManager $livewire;

    /**
     * Set up the Livewire instance.
     */
    protected function setUpWithLivewire(): void
    {
        $this->livewire = $this->makeLivewire();
    }

    /**
     * Create a Livewire instance.
     */
    protected function makeLivewire(): LivewireManager
    {
        return $this->app->make(LivewireManager::class);
    }
}
