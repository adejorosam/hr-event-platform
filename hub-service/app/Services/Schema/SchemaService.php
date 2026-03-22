<?php

namespace App\Services\Schema;

class SchemaService
{
    public function getSchema(string $stepId, string $country): ?array
    {
        return match ($stepId) {
            'dashboard'     => $this->getDashboardSchema($country),
            'employees'     => $this->getEmployeesSchema($country),
            'documentation' => $this->getDocumentationSchema($country),
            default         => null,
        };
    }

    private function getDashboardSchema(string $country): array
    {
        $widgets = [
            [
                'id'              => 'employee_count',
                'type'            => 'stat_card',
                'label'           => 'Total Employees',
                'data_source'     => "/api/employees?country={$country}",
                'data_key'        => 'meta.total',
                'refresh_channel' => "country.{$country}",
                'refresh_event'   => 'employee.list.updated',
                'icon'            => 'people',
                'order'           => 1,
            ],
        ];

        if ($country === 'USA') {
            $widgets[] = [
                'id'              => 'average_salary',
                'type'            => 'stat_card',
                'label'           => 'Average Salary',
                'data_source'     => '/api/employees?country=USA',
                'data_key'        => 'computed.average_salary',
                'refresh_channel' => 'country.USA',
                'refresh_event'   => 'employee.list.updated',
                'format'          => 'currency',
                'icon'            => 'attach_money',
                'order'           => 2,
            ];
            $widgets[] = [
                'id'              => 'completion_rate',
                'type'            => 'progress_card',
                'label'           => 'Checklist Completion Rate',
                'data_source'     => '/api/checklists?country=USA',
                'data_key'        => 'completion_rate',
                'refresh_channel' => 'checklist.USA',
                'refresh_event'   => 'checklist.updated',
                'format'          => 'percentage',
                'icon'            => 'check_circle',
                'order'           => 3,
            ];
        }

        if ($country === 'Germany') {
            $widgets[] = [
                'id'              => 'goal_tracking',
                'type'            => 'list_card',
                'label'           => 'Goal Tracking',
                'description'     => 'Employees with defined goals',
                'data_source'     => '/api/checklists?country=Germany',
                'data_key'        => 'employees',
                'filter_field'    => 'items',
                'filter_value'    => 'goal',
                'refresh_channel' => 'checklist.Germany',
                'refresh_event'   => 'checklist.updated',
                'icon'            => 'flag',
                'order'           => 2,
            ];
        }

        return $widgets;
    }

    private function getEmployeesSchema(string $country): array
    {
        return [
            [
                'id'              => 'employee_table',
                'type'            => 'data_table',
                'label'           => 'Employee List',
                'data_source'     => "/api/employees?country={$country}",
                'refresh_channel' => "country.{$country}",
                'refresh_event'   => 'employee.list.updated',
                'columns_key'     => 'columns',
                'data_key'        => 'data',
                'pagination'      => true,
                'order'           => 1,
            ],
        ];
    }

    private function getDocumentationSchema(string $country): array
    {
        if ($country !== 'Germany') {
            return [];
        }

        return [
            [
                'id'          => 'documentation_overview',
                'type'        => 'content_card',
                'label'       => 'Documentation',
                'description' => 'Required documentation for German employees',
                'content'     => [
                    ['title' => 'Tax ID Verification', 'description' => 'All employees must have a valid German Tax ID (format: DE + 9 digits).'],
                    ['title' => 'Goal Setting',         'description' => 'Each employee must have documented performance goals.'],
                ],
                'order' => 1,
            ],
        ];
    }
}
