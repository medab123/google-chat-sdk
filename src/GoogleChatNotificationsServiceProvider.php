<?php

namespace Gooogle\GoogleChatNotifications;

use Illuminate\Support\ServiceProvider;

class GoogleChatNotificationsServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/google-chat.php' => config_path('google-chat.php'),
        ], 'config');
    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/google-chat.php', 'google-chat'
        );

        $this->app->singleton('google-chat', function ($app) {
            return new GoogleChatNotifier(config('google-chat.webhook_url'));
        });
    }
}
