<?php

namespace App\Http\Controllers;

use App\Http\Requests\CountryRequest;
use App\Services\Checklist\ChecklistService;
use Illuminate\Http\JsonResponse;

class ChecklistController extends Controller
{
    public function __construct(
        private readonly ChecklistService $checklistService
    ) {}

    public function index(CountryRequest $request): JsonResponse
    {
        $country = $request->validated('country');
        $data = $this->checklistService->getChecklistData($country);

        return response()->json($data);
    }
}
