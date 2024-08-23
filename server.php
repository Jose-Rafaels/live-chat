<?php
require __DIR__ . '/vendor/autoload.php';
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use afrizalmy\BWI\BadWord;

function filterJsonContent($jsonString) {
    // Decode JSON menjadi array asosiatif
    $data = json_decode($jsonString, true);

    // Loop melalui setiap key dalam array
    foreach ($data as $key => $value) {
        // Jika value adalah string, kita terapkan filterWords
        if (is_string($value)) {
            $data[$key] = BadWord::masking($value);
        }
    }

    // Encode kembali menjadi JSON
    return json_encode($data);
}   

class Chat implements MessageComponentInterface {
    protected $clients;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
    }

    public function onOpen(ConnectionInterface $conn) {
        // Store the new connection
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $cek = filterJsonContent($msg);
        // Broadcast the message to all connected clients
        foreach ($this->clients as $client) {
            if ($from !== $client) {
                $client->send($cek);
            }
        }
    }

    public function onClose(ConnectionInterface $conn) {
        // Remove the connection
        $this->clients->detach($conn);
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }
}

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new Chat()
        )
    ),
    8080
);

$server->run();
