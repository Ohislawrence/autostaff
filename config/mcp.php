<?php

return [

    /*
    |--------------------------------------------------------------------------
    | MCP Integrations
    |--------------------------------------------------------------------------
    |
    | Feature flag and guardrails for Model Context Protocol integrations.
    | Set MCP_ENABLED=false in .env to ship the platform with MCP disabled.
    |
    */

    'enabled' => env('MCP_ENABLED', true),

    // Providers allowed to be connected. Trim this list for staged rollout.
    'allowed_providers' => ['google_calendar', 'gmail', 'crm', 'microsoft_365', 'custom'],

    // Hard cap on MCP tool descriptions (a prompt-injection surface).
    'max_description_length' => 2000,

    // Reject endpoints that resolve to private/reserved addresses (SSRF).
    'ssrf_protection' => true,

    /*
    |--------------------------------------------------------------------------
    | OAuth Providers
    |--------------------------------------------------------------------------
    |
    | OAuth2 authorization-code flow for connecting MCP providers that expose
    | their tools behind a third-party OAuth login (e.g. Google).
    |
    */
    'oauth' => [
        'google' => [
            'client_id' => env('GOOGLE_CLIENT_ID'),
            'client_secret' => env('GOOGLE_CLIENT_SECRET'),
            'redirect_uri' => env('GOOGLE_REDIRECT_URI'),
            'authorize_url' => 'https://accounts.google.com/o/oauth2/v2/auth',
            'token_url' => 'https://oauth2.googleapis.com/token',
            'endpoint' => env('GOOGLE_MCP_ENDPOINT', 'https://mcp.googleapis.com'),
            'authorize_params' => ['access_type' => 'offline', 'prompt' => 'consent'],
            'scopes' => [
                'google_calendar' => ['https://www.googleapis.com/auth/calendar'],
                'gmail' => ['https://www.googleapis.com/auth/gmail.send', 'https://www.googleapis.com/auth/gmail.readonly'],
            ],
        ],
    ],

];
