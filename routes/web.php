<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn() => ['version' => app()->version()]);
