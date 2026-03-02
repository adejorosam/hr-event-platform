<?php

namespace App\Contracts;

interface EventProcessorInterface
{
    public function process(array $event): void;

    public function supports(string $eventType): bool;
}
