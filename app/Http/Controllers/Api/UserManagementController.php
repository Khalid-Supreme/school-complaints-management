<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
                  ->orWhere('first_name', 'ilike', "%{$search}%")
                  ->orWhere('last_name', 'ilike', "%{$search}%")
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
            ->whereHas('role', function ($q) use ($request) {
                $manageableRoles = ['staff', 'complaint_officer'];

                if ($request->user()->canManageRole('sub_admin')) {
                    $manageableRoles[] = 'sub_admin';
                }

                $q->whereIn('slug', $manageableRoles);
            });

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('first_name', 'ilike', "%{$search}%")
                  ->orWhere('last_name', 'ilike', "%{$search}%")
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
        if (!$request->user()->canManageUsers()) {
            return response()->json(['message' => 'You are not authorized to create users.'], 403);
        }

        $rules = [
            'role' => ['required', 'string', 'in:student,staff,complaint_officer'],
            'first_name' => ['required', 'string', 'max:150'],
            'last_name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'string', 'email', 'max:150', 'unique:users,email'],
            'gender' => ['required', 'string', 'in:male,female'],
            'department' => ['required', 'integer', 'exists:departments,id'],
            'password' => ['sometimes', 'string', 'min:8'],
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

        $institutionId = $this->userRepository->generateInstitutionId($prefix);

        $firstName = $request->first_name;
        $lastName = $request->last_name;
        $fullName = User::composeName($firstName, $lastName);

        $user = User::create([
            'role_id' => $role->id,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'name' => $fullName,
            'email' => $request->email,
            'institution_id' => $institutionId,
            'password' => $request->filled('password')
                ? $request->password
                : hash('sha256', $institutionId),
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
        if (!$request->user()->canManageUsers()) {
            return response()->json(['message' => 'You are not authorized to update users.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'first_name' => ['sometimes', 'string', 'max:150'],
            'last_name' => ['sometimes', 'string', 'max:150'],
            'email' => ['sometimes', 'string', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'gender' => ['sometimes', 'string', 'in:male,female'],
            'title' => ['sometimes', 'string', 'in:Mr.,Mrs.,Miss,Dr.,Prof.'],
            'department' => ['sometimes', 'integer', 'exists:departments,id'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = [];

        if ($request->has('first_name') || $request->has('last_name')) {
            $firstName = $request->filled('first_name') ? $request->first_name : $user->first_name;
            $lastName = $request->filled('last_name') ? $request->last_name : $user->last_name;
            $data['first_name'] = $firstName;
            $data['last_name'] = $lastName;
            $data['name'] = User::composeName($firstName, $lastName);
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

        $user->update($data);
        $user->load('role', 'department');

        return response()->json([
            'message' => 'User updated successfully.',
            'user' => $user,
        ]);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        if (!$request->user()->canManageUsers()) {
            return response()->json(['message' => 'You are not authorized to delete users.'], 403);
        }

        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully.',
        ]);
    }

    public function updateRole(Request $request, User $user): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'role' => ['required', 'string', 'max:50'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $roleSlug = $request->role;

        if (!$request->user()->canAssignRole($roleSlug)) {
            return response()->json(['message' => 'You are not authorized to assign this role.'], 403);
        }

        if ((int) $user->id === (int) $request->user()->id) {
            return response()->json(['message' => 'You cannot change your own role.'], 403);
        }

        $role = Role::where('slug', $roleSlug)->firstOrFail();
        $user->role_id = $role->id;
        $user->save();
        $user->load('role', 'department');

        return response()->json([
            'message' => 'Role updated successfully.',
            'user' => $user,
        ]);
    }

    public function resetPassword(Request $request, User $user): JsonResponse
    {
        if (!$request->user()->canManageUsers()) {
            return response()->json(['message' => 'You are not authorized to reset passwords.'], 403);
        }

        if (!$user->institution_id) {
            return response()->json(['message' => 'This user has no registration number to use as a default password.'], 422);
        }

        // Default password is the user's registration number. The client sends the
        // SHA-256 digest (matching the login/register pattern); fall back to hashing
        // server-side if it is missing. The 'hashed' cast bcrypts the value on save.
        $password = $request->input('password');
        if (!is_string($password) || strlen($password) < 8) {
            $password = hash('sha256', $user->institution_id);
        }

        $user->password = $password;
        $user->save();

        return response()->json([
            'message' => 'Password has been reset successfully.',
            'default_password' => $user->institution_id,
        ]);
    }
}
