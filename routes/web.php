<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'accounting')->name('home');
Route::view('/login', 'login')->name('login');
