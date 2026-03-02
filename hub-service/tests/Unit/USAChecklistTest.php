<?php

namespace Tests\Unit;

use App\Services\Checklist\USAChecklist;
use PHPUnit\Framework\TestCase;

class USAChecklistTest extends TestCase
{
    private USAChecklist $checklist;

    protected function setUp(): void
    {
        parent::setUp();
        $this->checklist = new USAChecklist();
    }

    public function test_country_code_is_usa(): void
    {
        $this->assertEquals('USA', $this->checklist->getCountryCode());
    }

    public function test_has_three_requirements(): void
    {
        $this->assertCount(3, $this->checklist->getRequirements());
    }

    public function test_complete_employee_passes_all_checks(): void
    {
        $employee = [
            'ssn' => '123-45-6789',
            'salary' => 75000,
            'address' => '123 Main St, New York, NY',
        ];

        $items = $this->checklist->validate($employee);

        foreach ($items as $item) {
            $this->assertTrue($item['complete'], "Field {$item['field']} should be complete");
            $this->assertNull($item['message']);
        }
    }

    public function test_missing_ssn_fails_validation(): void
    {
        $employee = [
            'ssn' => null,
            'salary' => 75000,
            'address' => '123 Main St',
        ];

        $items = $this->checklist->validate($employee);
        $ssnItem = collect($items)->firstWhere('field', 'ssn');

        $this->assertFalse($ssnItem['complete']);
        $this->assertNotNull($ssnItem['message']);
    }

    public function test_zero_salary_fails_validation(): void
    {
        $employee = [
            'ssn' => '123-45-6789',
            'salary' => 0,
            'address' => '123 Main St',
        ];

        $items = $this->checklist->validate($employee);
        $salaryItem = collect($items)->firstWhere('field', 'salary');

        $this->assertFalse($salaryItem['complete']);
    }

    public function test_empty_address_fails_validation(): void
    {
        $employee = [
            'ssn' => '123-45-6789',
            'salary' => 75000,
            'address' => '',
        ];

        $items = $this->checklist->validate($employee);
        $addressItem = collect($items)->firstWhere('field', 'address');

        $this->assertFalse($addressItem['complete']);
    }

    public function test_all_missing_fields_return_messages(): void
    {
        $employee = [
            'ssn' => null,
            'salary' => 0,
            'address' => null,
        ];

        $items = $this->checklist->validate($employee);

        foreach ($items as $item) {
            $this->assertFalse($item['complete']);
            $this->assertNotNull($item['message']);
            $this->assertStringContainsString('Missing', $item['message']);
        }
    }
}
