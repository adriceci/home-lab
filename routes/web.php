<?php

use Illuminate\Support\Facades\Route;

// Web Routes
Route::get('/', function () {
    return redirect('/dashboard');
});

Route::get('/{page}', function () {
    return view('app');
});
