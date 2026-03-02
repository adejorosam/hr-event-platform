<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Services\EventPublisher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class EmployeeApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock EventPublisher to avoid RabbitMQ dependency in tests
        $mock = Mockery::mock(EventPublisher::class);
        $mock->shouldReceive('publish')->andReturnNull();
        $this->app->instance(EventPublisher::class, $mock);
    }

    public function test_can_list_employees(): void
    {
        Employee::factory()->usa()->count(3)->create();
        Employee::factory()->germany()->count(2)->create();

        $response = $this->getJson('/api/employees');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data');
    }

    public function test_can_filter_employees_by_country(): void
    {
        Employee::factory()->usa()->count(3)->create();
        Employee::factory()->germany()->count(2)->create();

        $response = $this->getJson('/api/employees?country=USA');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_can_create_usa_employee(): void
    {
        $payload = [
            'name' => 'John',
            'last_name' => 'Doe',
            'salary' => 75000,
            'ssn' => '123-45-6789',
            'address' => '123 Main St, New York, NY',
            'country' => 'USA',
        ];

        $response = $this->postJson('/api/employees', $payload);

        $response->assertStatus(201)
            ->assertJsonFragment([
                'name' => 'John',
                'last_name' => 'Doe',
                'country' => 'USA',
            ]);

        $this->assertDatabaseHas('employees', [
            'name' => 'John',
            'last_name' => 'Doe',
            'country' => 'USA',
            'ssn' => '123-45-6789',
        ]);
    }

    public function test_can_create_germany_employee(): void
    {
        $payload = [
            'name' => 'Hans',
            'last_name' => 'Mueller',
            'salary' => 65000,
            'goal' => 'Increase team productivity by 20%',
            'tax_id' => 'DE123456789',
            'country' => 'Germany',
        ];

        $response = $this->postJson('/api/employees', $payload);

        $response->assertStatus(201)
            ->assertJsonFragment([
                'name' => 'Hans',
                'country' => 'Germany',
                'tax_id' => 'DE123456789',
            ]);
    }

    public function test_can_show_employee(): void
    {
        $employee = Employee::factory()->usa()->create();

        $response = $this->getJson("/api/employees/{$employee->id}");

        $response->assertStatus(200)
            ->assertJsonFragment(['id' => $employee->id]);
    }

    public function test_can_update_employee(): void
    {
        $employee = Employee::factory()->usa()->create(['salary' => 50000]);

        $response = $this->putJson("/api/employees/{$employee->id}", [
            'salary' => 80000,
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['salary' => 80000.0]);
    }

    public function test_can_delete_employee(): void
    {
        $employee = Employee::factory()->usa()->create();

        $response = $this->deleteJson("/api/employees/{$employee->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('employees', ['id' => $employee->id]);
    }

    public function test_validation_rejects_invalid_country(): void
    {
        $payload = [
            'name' => 'Test',
            'last_name' => 'User',
            'salary' => 50000,
            'country' => 'InvalidCountry',
        ];

        $response = $this->postJson('/api/employees', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['country']);
    }

    public function test_validation_rejects_invalid_ssn_format(): void
    {
        $payload = [
            'name' => 'Test',
            'last_name' => 'User',
            'salary' => 50000,
            'country' => 'USA',
            'ssn' => 'invalid-ssn',
        ];

        $response = $this->postJson('/api/employees', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ssn']);
    }

    public function test_validation_rejects_invalid_tax_id_format(): void
    {
        $payload = [
            'name' => 'Test',
            'last_name' => 'User',
            'salary' => 50000,
            'country' => 'Germany',
            'tax_id' => 'INVALID',
        ];

        $response = $this->postJson('/api/employees', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['tax_id']);
    }

    public function test_pagination_works(): void
    {
        Employee::factory()->usa()->count(20)->create();

        $response = $this->getJson('/api/employees?per_page=5&page=1');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.per_page', 5);
    }
}
