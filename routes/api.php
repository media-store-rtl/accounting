<?php

use App\Http\Controllers\StorefrontSubscriptionController;
use Illuminate\Support\Facades\Route;

Route::post('/storefront/subscription/sync', [StorefrontSubscriptionController::class, 'sync']);
