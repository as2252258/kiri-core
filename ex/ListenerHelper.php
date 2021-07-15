<?php


trait ListenerHelper
{


    /**
     * @param $server
     * @param $newServer
     */
    public static function onConnectAndClose($server, $newServer)
    {
        if (in_array($server->setting['dispatch_mode'] ?? 2, [1, 3])){
            return;
        }
        if (!($server->setting['enable_unsafe_event'] ?? false)) {
            return;
        }
        $newServer->on('connect', $settings['events'][BASEServerListener::SERVER_ON_CONNECT] ?? [static::class, 'onConnect']);
        $newServer->on('close', $settings['events'][BASEServerListener::SERVER_ON_CLOSE] ?? [static::class, 'onClose']);
    }

}
