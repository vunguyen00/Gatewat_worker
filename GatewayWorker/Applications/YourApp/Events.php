<?php
namespace Applications\YourApp;

use GatewayWorker\Lib\Gateway;

class Events
{
    public static function onConnect($client_id)
    {
        Gateway::sendToClient($client_id, "Hello, welcome to the server!");
    }

    public static function onMessage($client_id, $message)
    {
        Gateway::sendToClient($client_id, "Server received: $message");
    }

    public static function onClose($client_id)
    {
        echo "Client $client_id disconnected\n";
    }
}
