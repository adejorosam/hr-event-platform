<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class EventPublisher
{
    private ?AMQPStreamConnection $connection = null;

    public function publish(string $eventType, string $country, array $data): void
    {
        try {
            $channel = $this->getChannel();
            $exchange = config('rabbitmq.exchange', 'hr_events');

            $routingKey = strtolower("employee.{$eventType}.{$country}");

            $payload = [
                'event_type' => $eventType,
                'event_id' => (string) \Illuminate\Support\Str::uuid(),
                'timestamp' => now()->toIso8601String(),
                'country' => $country,
                'data' => $data,
            ];

            $message = new AMQPMessage(
                json_encode($payload),
                [
                    'content_type' => 'application/json',
                    'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                ]
            );

            $channel->basic_publish($message, $exchange, $routingKey);

            Log::info("Event published: {$eventType}", [
                'routing_key' => $routingKey,
                'employee_id' => $data['employee_id'] ?? null,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to publish event: {$eventType}", [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
            throw $e;
        }
    }

    private function getChannel(): \PhpAmqpLib\Channel\AMQPChannel
    {
        if ($this->connection === null || !$this->connection->isConnected()) {
            $this->connection = new AMQPStreamConnection(
                config('rabbitmq.host', 'rabbitmq'),
                config('rabbitmq.port', 5672),
                config('rabbitmq.user', 'guest'),
                config('rabbitmq.password', 'guest'),
            );
        }

        $channel = $this->connection->channel();
        $exchange = config('rabbitmq.exchange', 'hr_events');

        $channel->exchange_declare($exchange, 'topic', false, true, false);

        return $channel;
    }

    public function __destruct()
    {
        try {
            $this->connection?->close();
        } catch (\Exception $e) {
            // Silently close
        }
    }
}
