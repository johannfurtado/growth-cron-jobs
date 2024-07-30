<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $title = 'Laravel 8';
    return view('welcome');
});
