<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'message' => '¡Tu aplicación de inscripciones está lista para comenzar!'
    ]);
});
