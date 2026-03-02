<?php

namespace App\Contracts;

interface CountryChecklistInterface
{
    /**
     * Get the list of required fields and their validation rules.
     *
     * @return array<string, array{label: string, rule: callable}>
     */
    public function getRequirements(): array;

    /**
     * Validate an employee's data completeness.
     *
     * @return array{field: string, label: string, complete: bool, message: string|null}[]
     */
    public function validate(array $employee): array;

    /**
     * Get the country code this checklist handles.
     */
    public function getCountryCode(): string;
}
