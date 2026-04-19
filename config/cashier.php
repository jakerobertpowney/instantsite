<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cashier Model
    |--------------------------------------------------------------------------
    */
    'model' => App\Models\User::class,

    /*
    |--------------------------------------------------------------------------
    | Stripe Keys
    |--------------------------------------------------------------------------
    | Set STRIPE_KEY and STRIPE_SECRET in your .env file.
    */
    'key'    => env('STRIPE_KEY'),
    'secret' => env('STRIPE_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Stripe Webhook Secret
    |--------------------------------------------------------------------------
    | Set STRIPE_WEBHOOK_SECRET in your .env file after creating the webhook
    | endpoint in the Stripe dashboard pointing to:
    |   POST /stripe/webhook
    */
    'webhook' => [
        'secret'    => env('STRIPE_WEBHOOK_SECRET'),
        'tolerance' => env('STRIPE_WEBHOOK_TOLERANCE', 300),
    ],

    /*
    |--------------------------------------------------------------------------
    | Stripe Price ID
    |--------------------------------------------------------------------------
    | The Stripe Price ID for the 321Sites Premium monthly plan.
    | Create a Product + Price in the Stripe dashboard and paste the
    | price_XXXXXXX ID here.
    */
    'price_id' => env('STRIPE_PRICE_ID'),

    /*
    |--------------------------------------------------------------------------
    | Currency
    |--------------------------------------------------------------------------
    */
    'currency' => env('CASHIER_CURRENCY', 'gbp'),

    'currency_locale' => env('CASHIER_CURRENCY_LOCALE', 'en-GB'),

    /*
    |--------------------------------------------------------------------------
    | Payment Confirmation Notification
    |--------------------------------------------------------------------------
    */
    'payment_notification' => null,

    /*
    |--------------------------------------------------------------------------
    | Invoice Settings
    |--------------------------------------------------------------------------
    */
    'invoice_paper_size' => env('CASHIER_PAPER_SIZE', 'a4'),

    /*
    |--------------------------------------------------------------------------
    | Logger
    |--------------------------------------------------------------------------
    */
    'logger' => env('CASHIER_LOGGER'),

];
