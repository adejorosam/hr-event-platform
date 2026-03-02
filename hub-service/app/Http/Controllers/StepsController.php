<?php

namespace App\Http\Controllers;

use App\Http\Requests\CountryRequest;
use Illuminate\Http\JsonResponse;

class StepsController extends Controller
{
    private const STEPS = [
        'USA' => [
            [
                'id' => 'dashboard',
                'label' => 'Dashboard',
                'icon' => 'dashboard',
                'path' => '/dashboard',
                'order' => 1,
            ],
            [
                'id' => 'employees',
                'label' => 'Employees',
                'icon' => 'people',
                'path' => '/employees',
                'order' => 2,
            ],
        ],
        'Germany' => [
            [
                'id' => 'dashboard',
                'label' => 'Dashboard',
                'icon' => 'dashboard',
                'path' => '/dashboard',
                'order' => 1,
            ],
            [
                'id' => 'employees',
                'label' => 'Employees',
                'icon' => 'people',
                'path' => '/employees',
                'order' => 2,
            ],
            [
                'id' => 'documentation',
                'label' => 'Documentation',
                'icon' => 'description',
                'path' => '/documentation',
                'order' => 3,
            ],
        ],
    ];

    public function index(CountryRequest $request): JsonResponse
    {
        $country = $request->validated('country');

        return response()->json([
            'country' => $country,
            'steps' => self::STEPS[$country] ?? [],
        ]);
    }
}
