<?php

namespace Tests\Unit;

use App\Models\Employee;
use PHPUnit\Framework\TestCase;

class EmployeeModelTest extends TestCase
{
    public function test_usa_employee_has_correct_country_fields(): void
    {
        $employee = new Employee(['country' => 'USA']);
        $fields = $employee->getCountryFields();

        $this->assertContains('ssn', $fields);
        $this->assertContains('address', $fields);
        $this->assertNotContains('goal', $fields);
        $this->assertNotContains('tax_id', $fields);
    }

    public function test_germany_employee_has_correct_country_fields(): void
    {
        $employee = new Employee(['country' => 'Germany']);
        $fields = $employee->getCountryFields();

        $this->assertContains('goal', $fields);
        $this->assertContains('tax_id', $fields);
        $this->assertNotContains('ssn', $fields);
        $this->assertNotContains('address', $fields);
    }

    public function test_unknown_country_returns_empty_fields(): void
    {
        $employee = new Employee(['country' => 'Unknown']);
        $fields = $employee->getCountryFields();

        $this->assertEmpty($fields);
    }
}
