<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UserManagementController extends Controller
{
    protected UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function students(Request $request): JsonResponse
    {
        $search = $request->get('search');
        $departmentId = $request->get('department_id');
        $gender = $request->get('gender');
        $status = $request->get('status');

        $query = User::with('role', 'department')
            ->whereHas('role', fn($q) => $q->where('slug', 'student'));

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('email', 'ilike', "%{$search}%")
                  ->orWhere('institution_id', 'ilike', "%{$search}%");
            });
        }

        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }

        if ($gender) {
            $query->where('gender', $gender);
        }

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json([
            'data' => $users->items(),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    public function staff(Request $request): JsonResponse
    {
        $search = $request->get('search');
        $departmentId = $request->get('department_id');
        $roleSlug = $request->get('role');
        $status = $request->get('status');

        $query = User::with('role', 'department')
            ->whereHas('role', fn($q) => $q->whereIn('slug', ['staff', 'complaint_officer']));

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('email', 'ilike', "%{$search}%")
                  ->orWhere('institution_id', 'ilike', "%{$search}%");
            });
        }

        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }

        if ($roleSlug) {
            $query->whereHas('role', fn($q) => $q->where('slug', $roleSlug));
        }

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json([
            'data' => $users->items(),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $rules = [
            'role' => ['required', 'string', 'in:student,staff,complaint_officer'],
            'full_name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'string', 'email', 'max:150', 'unique:users,email'],
            'gender' => ['required', 'string', 'in:male,female'],
            'department' => ['required', 'integer', 'exists:departments,id'],
            'password' => ['required', 'string', 'min:8'],
        ];

        if ($request->role === 'staff' || $request->role === 'complaint_officer') {
            $rules['title'] = ['required', 'string', 'in:Mr.,Mrs.,Miss,Dr.,Prof.'];
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $role = Role::where('slug', $request->role)->firstOrFail();
        $prefix = ($request->role === 'student') ? 'STD' : 'STF';

        $title = $request->title ?? ($request->gender === 'male' ? 'Mr.' : 'Miss');

        $user = User::create([
            'role_id' => $role->id,
            'name' => $request->full_name,
            'email' => $request->email,
            'institution_id' => $this->userRepository->generateInstitutionId($prefix),
            'password' => Hash::make($request->password),
            'department_id' => $request->department,
            'gender' => $request->gender,
            'title' => $title,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $user->load('role', 'department');

        return response()->json([
            'message' => 'User created successfully.',
            'user' => $user,
        ], 201);
    }

    public function show(User $user): JsonResponse
    {
        return response()->json($user->load('role', 'department'));
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'full_name' => ['sometimes', 'string', 'max:150'],
            'email' => ['sometimes', 'string', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'gender' => ['sometimes', 'string', 'in:male,female'],
            'title' => ['sometimes', 'string', 'in:Mr.,Mrs.,Miss,Dr.,Prof.'],
            'department' => ['sometimes', 'integer', 'exists:departments,id'],
            'is_active' => ['sometimes', 'boolean'],
            'password' => ['sometimes', 'string', 'min:8'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = [];

        if ($request->has('full_name')) {
            $data['name'] = $request->full_name;
        }
        if ($request->has('email')) {
            $data['email'] = $request->email;
        }
        if ($request->has('gender')) {
            $data['gender'] = $request->gender;
        }
        if ($request->has('title')) {
            $data['title'] = $request->title;
        }
        if ($request->has('department')) {
            $data['department_id'] = $request->department;
        }
        if ($request->has('is_active')) {
            $data['is_active'] = $request->boolean('is_active');
        }
        if ($request->has('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);
        $user->load('role', 'department');

        return response()->json([
            'message' => 'User updated successfully.',
            'user' => $user,
        ]);
    }

    public function destroy(User $user): JsonResponse
    {
        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully.',
        ]);
    }

    public function updateRole(Request $request, User $user): JsonResponse
    {
        $allowedSlugs = ['staff', 'complaint_officer'];

        $validator = Validator::make($request->all(), [
            'role' => ['required', 'string', Rule::in($allowedSlugs)],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $role = Role::where('slug', $request->role)->firstOrFail();
        $user->role_id = $role->id;
        $user->save();
        $user->load('role', 'department');

        return response()->json([
            'message' => 'Role updated successfully.',
            'user' => $user,
        ]);
    }
}
