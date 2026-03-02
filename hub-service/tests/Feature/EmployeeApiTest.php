<?php

namespace Tests\Feature;

use App\Services\HrServiceClient;
use Mockery;
use Tests\TestCase;

class EmployeeApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $mock = Mockery::mock(HrServiceClient::class);
        $mock->shouldReceive('getEmployees')
            ->with('USA', 1, 15)
            ->andReturn([
                'data' => [
                    [
                        'id' => 1,
                        'name' => 'John',
                        'last_name' => 'Doe',
                        'salary' => 75000,
                        'ssn' => '123-45-6789',
                        'address' => '123 Main St',
                        'country' => 'USA',
                    ],
                ],
                'meta' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => 15,
                    'total' => 1,
                ],
            ]);

        $mock->shouldReceive('getEmployees')
            ->with('Germany', 1, 15)
            ->andReturn([
                'data' => [
                    [
                        'id' => 2,
                        'name' => 'Hans',
                        'last_name' => 'Mueller',
                        'salary' => 65000,
                        'goal' => 'Increase productivity',
                        'tax_id' => 'DE123456789',
                        'country' => 'Germany',
                    ],
                ],
                'meta' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => 15,
                    'total' => 1,
                ],
            ]);

        $this->app->instance(HrServiceClient::class, $mock);
    }

    public function test_returns_usa_employees_with_columns(): void
    {
        $response = $this->getJson('/api/employees?country=USA');

        $response->assertStatus(200)
            ->assertJsonPath('country', 'USA')
            ->assertJsonCount(4, 'columns');

        $columnKeys = array_column($response->json('columns'), 'key');
        $this->assertEquals(['name', 'last_name', 'salary', 'ssn'], $columnKeys);
    }

    public function test_returns_germany_employees_with_columns(): void
    {
        $response = $this->getJson('/api/employees?country=Germany');

        $response->assertStatus(200)
            ->assertJsonPath('country', 'Germany');

        $columnKeys = array_column($response->json('columns'), 'key');
        $this->assertEquals(['name', 'last_name', 'salary', 'goal'], $columnKeys);
    }

    public function test_usa_ssn_is_masked(): void
    {
        $response = $this->getJson('/api/employees?country=USA');

        $employees = $response->json('data');
        $this->assertEquals('***-**-6789', $employees[0]['ssn']);
    }

    public function test_includes_real_time_channel(): void
    {
        $response = $this->getJson('/api/employees?country=USA');

        $response->assertJsonPath('real_time_channel', 'country.USA');
    }

    public function test_requires_country_parameter(): void
    {
        $response = $this->getJson('/api/employees');

        $response->assertStatus(422);
    }
}
