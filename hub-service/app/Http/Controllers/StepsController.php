<?php

namespace App\Http\Controllers;

use App\Http\Requests\CountryRequest;
use App\Services\Steps\StepsService;
use Illuminate\Http\JsonResponse;

class StepsController extends Controller
{
    public function __construct(
        private readonly StepsService $stepsService
    ) {}

    public function index(CountryRequest $request): JsonResponse
    {
        $country = $request->validated('country');

        return response()->json([
            'country' => $country,
            'steps'   => $this->stepsService->getSteps($country),
        ]);
    }
}
