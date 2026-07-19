<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index(Request $request): JsonResponse
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
