<?php

namespace Tests\Unit;

use App\Services\Checklist\GermanyChecklist;
use PHPUnit\Framework\TestCase;

class GermanyChecklistTest extends TestCase
{
    private GermanyChecklist $checklist;

    protected function setUp(): void
    {
        parent::setUp();
        $this->checklist = new GermanyChecklist();
    }

    public function test_country_code_is_germany(): void
    {
        $this->assertEquals('Germany', $this->checklist->getCountryCode());
    }

    public function test_has_three_requirements(): void
    {
        $this->assertCount(3, $this->checklist->getRequirements());
    }

    public function test_complete_employee_passes_all_checks(): void
    {
        $employee = [
            'salary' => 65000,
            'goal' => 'Increase team productivity by 20%',
            'tax_id' => 'DE123456789',
        ];

        $items = $this->checklist->validate($employee);

        foreach ($items as $item) {
            $this->assertTrue($item['complete'], "Field {$item['field']} should be complete");
            $this->assertNull($item['message']);
        }
    }

    public function test_invalid_tax_id_format_fails(): void
    {
        $employee = [
            'salary' => 65000,
            'goal' => 'Some goal',
            'tax_id' => 'INVALID',
        ];

        $items = $this->checklist->validate($employee);
        $taxItem = collect($items)->firstWhere('field', 'tax_id');

        $this->assertFalse($taxItem['complete']);
    }

    public function test_tax_id_must_start_with_de(): void
    {
        $employee = [
            'salary' => 65000,
            'goal' => 'Some goal',
            'tax_id' => 'FR123456789',
        ];

        $items = $this->checklist->validate($employee);
        $taxItem = collect($items)->firstWhere('field', 'tax_id');

        $this->assertFalse($taxItem['complete']);
    }

    public function test_tax_id_must_have_9_digits_after_de(): void
    {
        $employee = [
            'salary' => 65000,
            'goal' => 'Some goal',
            'tax_id' => 'DE12345',  // Only 5 digits
        ];

        $items = $this->checklist->validate($employee);
        $taxItem = collect($items)->firstWhere('field', 'tax_id');

        $this->assertFalse($taxItem['complete']);
    }

    public function test_empty_goal_fails_validation(): void
    {
        $employee = [
            'salary' => 65000,
            'goal' => '',
            'tax_id' => 'DE123456789',
        ];

        $items = $this->checklist->validate($employee);
        $goalItem = collect($items)->firstWhere('field', 'goal');

        $this->assertFalse($goalItem['complete']);
    }

    public function test_zero_salary_fails_validation(): void
    {
        $employee = [
            'salary' => 0,
            'goal' => 'Some goal',
            'tax_id' => 'DE123456789',
        ];

        $items = $this->checklist->validate($employee);
        $salaryItem = collect($items)->firstWhere('field', 'salary');

        $this->assertFalse($salaryItem['complete']);
    }
}
