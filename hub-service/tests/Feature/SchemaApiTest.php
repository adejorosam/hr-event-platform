<?php

namespace Tests\Feature;

use Tests\TestCase;

class SchemaApiTest extends TestCase
{
    public function test_returns_usa_dashboard_schema(): void
    {
        $response = $this->getJson('/api/schema/dashboard?country=USA');

        $response->assertStatus(200)
            ->assertJsonPath('step_id', 'dashboard')
            ->assertJsonPath('country', 'USA');

        $widgets = $response->json('widgets');
        $widgetIds = array_column($widgets, 'id');

        $this->assertContains('employee_count', $widgetIds);
        $this->assertContains('average_salary', $widgetIds);
        $this->assertContains('completion_rate', $widgetIds);
    }

    public function test_returns_germany_dashboard_schema(): void
    {
        $response = $this->getJson('/api/schema/dashboard?country=Germany');

        $response->assertStatus(200);

        $widgets = $response->json('widgets');
        $widgetIds = array_column($widgets, 'id');

        $this->assertContains('employee_count', $widgetIds);
        $this->assertContains('goal_tracking', $widgetIds);
        $this->assertNotContains('average_salary', $widgetIds);
    }

    public function test_widgets_include_data_source_and_channel(): void
    {
        $response = $this->getJson('/api/schema/dashboard?country=USA');

        $widgets = $response->json('widgets');
        foreach ($widgets as $widget) {
            $this->assertArrayHasKey('data_source', $widget);
            $this->assertArrayHasKey('refresh_channel', $widget);
            $this->assertArrayHasKey('refresh_event', $widget);
        }
    }

    public function test_returns_404_for_unknown_step(): void
    {
        $response = $this->getJson('/api/schema/unknown?country=USA');

        $response->assertStatus(404);
    }

    public function test_requires_country_parameter(): void
    {
        $response = $this->getJson('/api/schema/dashboard');

        $response->assertStatus(422);
    }

    public function test_documentation_step_only_for_germany(): void
    {
        $response = $this->getJson('/api/schema/documentation?country=Germany');
        $response->assertStatus(200);

        $widgets = $response->json('widgets');
        $this->assertNotEmpty($widgets);

        $response = $this->getJson('/api/schema/documentation?country=USA');
        $response->assertStatus(200);

        $widgets = $response->json('widgets');
        $this->assertEmpty($widgets);
    }
}
