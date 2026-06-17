<?php
return [

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID', env('SOCIAL_GOOGLE_CLIENT_ID')),
        'client_secret' => env('GOOGLE_CLIENT_SECRET', env('SOCIAL_GOOGLE_CLIENT_SECRET')),
        'redirect' => env('GOOGLE_REDIRECT_URI', rtrim((string) env('APP_URL'), '/').'/auth/google/callback'),
    ],
    'facebook' => [
        'client_id' => env('FACEBOOK_CLIENT_ID', env('SOCIAL_FACEBOOK_CLIENT_ID')),
        'client_secret' => env('FACEBOOK_CLIENT_SECRET', env('SOCIAL_FACEBOOK_CLIENT_SECRET')),
        'redirect' => env('FACEBOOK_REDIRECT_URI', rtrim((string) env('APP_URL'), '/').'/auth/facebook/callback'),
    ],
    'line' => [
        'client_id' => env('LINE_CLIENT_ID', env('SOCIAL_LINE_CLIENT_ID')),
        'client_secret' => env('LINE_CLIENT_SECRET', env('SOCIAL_LINE_CLIENT_SECRET')),
        'redirect' => env('LINE_REDIRECT_URI', rtrim((string) env('APP_URL'), '/').'/auth/line/callback'),
    ],

];
