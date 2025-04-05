<?php

namespace Tests\Traits;

use Illuminate\Translation\Translator;

trait WithTranslator
{
    /**
     * The Translator instance.
     */
    protected Translator $translator;

    /**
     * Set up the Translator instance.
     */
    protected function setUpWithTranslator(): void
    {
        $this->translator = $this->makeTranslator();
    }

    /**
     * Get the default Translator instance for a given locale.
     */
    protected function translator(string|null $locale = null): Translator
    {
        return is_null($locale) ? $this->translator : $this->makeTranslator($locale);
    }

    /**
     * Create a Translator instance for the given locale.
     */
    protected function makeTranslator(string|null $locale = null): Translator
    {
        $this->translator = $this->app->make(Translator::class);

        $this->translator->setLocale($locale ?? $this->app->getLocale());

        return $this->translator;
    }
}
