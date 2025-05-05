
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Google Chat Webhook URL
    |--------------------------------------------------------------------------
    |
    | This is the webhook URL for your Google Chat space where notifications
    | will be sent. You can get this URL from Google Chat by creating a webhook.
    |
    */
    'webhook_url' => env('GOOGLE_CHAT_WEBHOOK_URL', ''),
];