<?php

use Illuminate\Support\Facades\Route;
use Shekel\Controllers\StripeWebhookController;

Route::post('/stripe/webhook', [StripeWebhookController::class, 'handleWebhook'])->name('stripe.webhook');