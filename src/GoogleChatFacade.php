<?php 

namespace GooogleChat\GoogleChatNotifications;
use Illuminate\Support\Facades\Facade;

class GoogleChatFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'google-chat';
    }
}