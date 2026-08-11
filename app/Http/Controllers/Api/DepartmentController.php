<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Department\IndexDepartmentsRequest;
use App\Models\Department;
use Illuminate\Http\JsonResponse;

class DepartmentController extends Controller
{
    public function index(IndexDepartmentsRequest $request): JsonResponse
    {
        $query = Department::query();

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $departments = $query->get(['id', 'name', 'type']);

        return response()->json([
            'data' => $departments,
        ]);
    }
}
