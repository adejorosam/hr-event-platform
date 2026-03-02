<?php

namespace App\Services\EventProcessors;

use App\Contracts\EventProcessorInterface;
use Illuminate\Support\Facades\Log;

class EventProcessorRegistry
{
    /** @var EventProcessorInterface[] */
    private array $processors = [];

    public function register(EventProcessorInterface $processor): void
    {
        $this->processors[] = $processor;
    }

    public function process(array $event): void
    {
        $eventType = $event['event_type'] ?? 'unknown';

        foreach ($this->processors as $processor) {
            if ($processor->supports($eventType)) {
                $processor->process($event);
                return;
            }
        }

        Log::warning("No processor found for event type: {$eventType}");
    }
}
