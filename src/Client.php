<?php

namespace Exmo\WebSocketApi;

use Closure;
use Ratchet\Client\WebSocket;

class Client
{
    const EVENT_INFO = 'info';
    const EVENT_ERROR = 'error';
    const EVENT_UPDATE = 'update';
    const EVENT_SNAPSHOT = 'snapshot';
    const EVENT_SUBSCRIBED = 'subscribed';
    const EVENT_UNSUBSCRIBED = 'unsubscribed';

    protected WebSocket $connect;
    protected int $messageId = 0;
    protected string $sessionId;

    public function __construct(WebSocket $connect)
    {
        $this->connect = $connect;
    }

    public function getSessionId(): ?string
    {
        return $this->sessionId;
    }

    public function login(string $apiKey, string $apiSecret, ?int $nonce = null): void
    {
        $nonce = $nonce ?: time();
        $this->send([
            'method' => 'login',
            'api_key' => $apiKey,
            'nonce' => $nonce,
            'sign' => $this->getSign($apiKey, $apiSecret, $nonce),
        ]);
    }

    public function subscribe(array $topics): void
    {
        $this->send([
            'method' => 'subscribe',
            'topics' => $topics,
        ]);
    }

    public function unsubscribe(array $topics): void
    {
        $this->send([
            'method' => 'unsubscribe',
            'topics' => $topics,
        ]);
    }

    public function send(array $data): void
    {
        $this->connect->send(json_encode(
            [
                'id' => ++$this->messageId,
            ] + $data
        ));
    }

    public function onMessage(Closure $receiveCallback): void
    {
        $this->connect->on('message', function ($msg) use ($receiveCallback) {
            $receivedData = json_decode($msg, true);
            if (!empty($receivedData['session_id'])) {
                $this->sessionId = $receivedData['session_id'];
            }
            $receiveCallback($receivedData);
        });
    }

    protected function getSign(string $apiKey, string $apiSecret, int $nonce): string
    {
        return base64_encode(hash_hmac('sha512', $apiKey.$nonce, $apiSecret, true));
    }
}