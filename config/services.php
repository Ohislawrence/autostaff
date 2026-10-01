<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'deepseek' => [
        'api_key' => env('DEEPSEEK_API_KEY', ''),
        'base_url' => env('DEEPSEEK_BASE_URL', 'https://api.deepseek.com'),
        'model' => env('DEEPSEEK_MODEL', 'deepseek-chat'),
        'embedding_model' => env('DEEPSEEK_EMBEDDING_MODEL', 'deepseek-embedding'),
    ],

    'whatsapp' => [
        'access_token' => env('WHATSAPP_ACCESS_TOKEN', env('WHATSAPP_API_KEY', '')),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID', ''),
        'business_account_id' => env('WHATSAPP_BUSINESS_ACCOUNT_ID', ''),
        'verify_token' => env('WHATSAPP_VERIFY_TOKEN', ''),
    ],

    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'chat_id' => env('TELEGRAM_CHAT_ID'),
    ],

    'prospecting' => [
        'alert_email' => env('PROSPECTING_ALERT_EMAIL'),
        'reply_webhook_secret' => env('PROSPECTING_REPLY_WEBHOOK_SECRET'),
    ],

    'nomba' => [
        'secret_key' => env('NOMBA_SECRET_KEY'),
        'account_id' => env('NOMBA_ACCOUNT_ID'),
        'public_key' => env('NOMBA_PUBLIC_KEY'),
        'base_url' => env('NOMBA_BASE_URL', 'https://api.nomba.com'),
        'webhook_secret' => env('NOMBA_WEBHOOK_SECRET'),
    ],

    'paystack' => [
        'secret_key' => env('PAYSTACK_SECRET_KEY'),
        'public_key' => env('PAYSTACK_PUBLIC_KEY'),
    ],

    'currency' => [
        'ngn_to_usd' => (float) env('NGN_TO_USD_RATE', 1500),
    ],

    /*
    |--------------------------------------------------------------------------
    | ClicksIntel — Affiliate Network (CPA conversion tracking)
    |--------------------------------------------------------------------------
    |
    | Nomdal is promoted as an offer on the ClicksIntel affiliate network.
    | Affiliates send traffic through a ClicksIntel tracking link; Nomdal
    | captures the click id, attributes it to the signup, and fires a
    | server-to-server postback back to ClicksIntel when a paid plan is
    | activated (or on signup, if configured).
    |
    */
    'clicksintel' => [
        'enabled' => env('CLICKSINTEL_ENABLED', false),
        'postback_url' => env('CLICKSINTEL_POSTBACK_URL'),
        'click_param' => env('CLICKSINTEL_CLICK_PARAM', 'click_id'),
        'sub_param' => env('CLICKSINTEL_SUB_PARAM', 'sub_id'),
        'transaction_param' => env('CLICKSINTEL_TRANSACTION_PARAM', 'txn_id'),
        'cookie_name' => env('CLICKSINTEL_COOKIE_NAME', 'ci_click'),
        'cookie_ttl_days' => (int) env('CLICKSINTEL_COOKIE_TTL_DAYS', 30),
        'conversion_event' => env('CLICKSINTEL_CONVERSION_EVENT', 'paid_subscription'),
        'payout_amount' => (float) env('CLICKSINTEL_PAYOUT_AMOUNT', 0),
        'payout_currency' => env('CLICKSINTEL_PAYOUT_CURRENCY', 'NGN'),
    ],

];
