<?php

namespace App\Http\Controllers;

use Illuminate\Translation\Translator;

abstract class Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(protected readonly Translator $translator)
    {
        //
    }
}
