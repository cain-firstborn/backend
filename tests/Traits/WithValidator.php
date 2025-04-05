<?php

namespace Tests\Traits;

use Illuminate\Validation\Factory as Validator;

trait WithValidator
{
    /**
     * The Validator instance.
     */
    protected Validator $validator;

    /**
     * Set up the Validator instance.
     */
    protected function setUpWithValidator(): void
    {
        $this->validator = $this->makeValidator();
    }

    /**
     * Create a Validator instance.
     */
    protected function makeValidator(): Validator
    {
        return $this->app->make(Validator::class);
    }
}
