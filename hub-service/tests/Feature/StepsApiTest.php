<?php

namespace Tests\Feature;

use Tests\TestCase;

class StepsApiTest extends TestCase
{
    public function test_returns_usa_steps(): void
    {
        $response = $this->getJson('/api/steps?country=USA');

        $response->assertStatus(200)
            ->assertJsonPath('country', 'USA')
            ->assertJsonCount(2, 'steps')
            ->assertJsonFragment(['id' => 'dashboard'])
            ->assertJsonFragment(['id' => 'employees']);
    }

    public function test_returns_germany_steps_with_documentation(): void
    {
        $response = $this->getJson('/api/steps?country=Germany');

        $response->assertStatus(200)
            ->assertJsonPath('country', 'Germany')
            ->assertJsonCount(3, 'steps')
            ->assertJsonFragment(['id' => 'dashboard'])
            ->assertJsonFragment(['id' => 'employees'])
            ->assertJsonFragment(['id' => 'documentation']);
    }

    public function test_steps_include_metadata(): void
    {
        $response = $this->getJson('/api/steps?country=USA');

        $response->assertStatus(200);

        $steps = $response->json('steps');
        foreach ($steps as $step) {
            $this->assertArrayHasKey('id', $step);
            $this->assertArrayHasKey('label', $step);
            $this->assertArrayHasKey('icon', $step);
            $this->assertArrayHasKey('path', $step);
            $this->assertArrayHasKey('order', $step);
        }
    }

    public function test_requires_country_parameter(): void
    {
        $response = $this->getJson('/api/steps');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['country']);
    }

    public function test_rejects_invalid_country(): void
    {
        $response = $this->getJson('/api/steps?country=France');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['country']);
    }
}
