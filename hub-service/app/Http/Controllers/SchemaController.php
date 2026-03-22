<?php

namespace App\Http\Controllers;

use App\Http\Requests\CountryRequest;
use App\Services\Schema\SchemaService;
use Illuminate\Http\JsonResponse;

class SchemaController extends Controller
{
    public function __construct(
        private readonly SchemaService $schemaService
    ) {}

    public function show(CountryRequest $request, string $stepId): JsonResponse
    {
        $country = $request->validated('country');
        $schema = $this->schemaService->getSchema($stepId, $country);

        if ($schema === null) {
            return response()->json(['error' => 'Step not found.'], 404);
        }

        return response()->json([
            'step_id' => $stepId,
            'country' => $country,
            'widgets' => $schema,
        ]);
    }
}
