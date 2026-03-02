<?php

namespace App\Console\Commands;

use App\Services\EventProcessors\EventProcessorRegistry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Connection\AMQPStreamConnection;

class ConsumeRabbitMQEvents extends Command
{
    protected $signature = 'rabbitmq:consume';
    protected $description = 'Consume events from RabbitMQ';

    private ?AMQPStreamConnection $connection = null;

    public function __construct(
        private readonly EventProcessorRegistry $registry
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Starting RabbitMQ consumer...');

        $maxRetries = 10;
        $retryDelay = 5;

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $this->consume();
                return self::SUCCESS;
            } catch (\Exception $e) {
                $this->error("Connection attempt {$attempt}/{$maxRetries} failed: {$e->getMessage()}");
                Log::error("RabbitMQ connection failed", [
                    'attempt' => $attempt,
                    'error' => $e->getMessage(),
                ]);

                if ($attempt < $maxRetries) {
                    $this->info("Retrying in {$retryDelay} seconds...");
                    sleep($retryDelay);
                }
            }
        }

        $this->error('Failed to connect to RabbitMQ after all retries.');
        return self::FAILURE;
    }

    private function consume(): void
    {
        $this->connection = new AMQPStreamConnection(
            config('rabbitmq.host'),
            config('rabbitmq.port'),
            config('rabbitmq.user'),
            config('rabbitmq.password'),
        );

        $channel = $this->connection->channel();

        $exchange = config('rabbitmq.exchange');
        $queue = config('rabbitmq.queue');

        // Declare exchange and queue
        $channel->exchange_declare($exchange, 'topic', false, true, false);
        $channel->queue_declare($queue, false, true, false, false);

        // Bind queue to all employee events
        $channel->queue_bind($queue, $exchange, 'employee.#');

        $this->info("Listening on queue: {$queue}");
        Log::info("RabbitMQ consumer started", ['queue' => $queue]);

        $channel->basic_qos(0, 1, false);

        $channel->basic_consume(
            $queue,
            '',
            false,
            false,
            false,
            false,
            function ($message) use ($channel) {
                $this->processMessage($message);
            }
        );

        // Keep consuming
        while (count($channel->callbacks)) {
            $channel->wait();
        }
    }

    private function processMessage($message): void
    {
        try {
            $payload = json_decode($message->getBody(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Invalid JSON in RabbitMQ message', [
                    'body' => $message->getBody(),
                ]);
                $message->getChannel()->basic_nack($message->getDeliveryTag(), false, false);
                return;
            }

            $eventType = $payload['event_type'] ?? 'unknown';
            $eventId = $payload['event_id'] ?? 'unknown';

            Log::info("Received event: {$eventType}", [
                'event_id' => $eventId,
                'routing_key' => $message->getRoutingKey() ?? 'unknown',
            ]);

            $this->registry->process($payload);

            // Acknowledge the message
            $message->getChannel()->basic_ack($message->getDeliveryTag());

            $this->info("Processed: {$eventType} (ID: {$eventId})");
        } catch (\Exception $e) {
            Log::error('Failed to process RabbitMQ message', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Reject and requeue
            $message->getChannel()->basic_nack($message->getDeliveryTag(), false, true);
        }
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
