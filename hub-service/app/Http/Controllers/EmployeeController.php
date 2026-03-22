<?php

namespace App\Http\Controllers;

use App\Http\Requests\EmployeeListRequest;
use App\Services\Employee\EmployeeService;
use Illuminate\Http\JsonResponse;

class EmployeeController extends Controller
{
    public function __construct(
        private readonly EmployeeService $employeeService
    ) {}

    public function index(EmployeeListRequest $request): JsonResponse
    {
        $country = $request->validated('country');
        $page    = (int) $request->input('page', 1);
        $perPage = (int) $request->input('per_page', 15);

        return response()->json(
            $this->employeeService->getEmployeeList($country, $page, $perPage)
        );
    }
}
