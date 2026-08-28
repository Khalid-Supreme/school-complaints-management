<?php

namespace App\Http\Controllers\Api;

use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\IndexStaffRequest;
use App\Http\Requests\User\IndexStudentsRequest;
use App\Http\Requests\User\ResetUserPasswordRequest;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Requests\User\UpdateUserRoleRequest;
use App\Models\Role;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\AuditLogger;
use App\Services\PasswordResetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserManagementController extends Controller
{
    protected UserRepository $userRepository;

    protected AuditLogger $auditLogger;

    protected PasswordResetService $passwordResetService;

    public function __construct(
        UserRepository $userRepository,
        AuditLogger $auditLogger,
        PasswordResetService $passwordResetService,
    ) {
        $this->userRepository = $userRepository;
        $this->auditLogger = $auditLogger;
        $this->passwordResetService = $passwordResetService;
    }

    public function students(IndexStudentsRequest $request): JsonResponse
    {
        $search = $request->validated('search');
        $departmentId = $request->get('department_id');
        $gender = $request->get('gender');
        $status = $request->get('status');

        $query = User::with('role', 'department')
            ->whereHas('role', fn ($q) => $q->where('slug', 'student'));

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'ilike', "%{$search}%")
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

    public function staff(IndexStaffRequest $request): JsonResponse
    {
        $search = $request->validated('search');
        $departmentId = $request->get('department_id');
        $roleSlug = $request->get('role');
        $status = $request->get('status');

        $query = User::with('role', 'department')
            ->whereHas('role', function ($q) use ($request) {
                $manageableRoles = ['staff', 'complaint_officer'];

                if ($request->user()->canManageRole('sub_admin')) {
                    $manageableRoles[] = 'sub_admin';
                }

                if ($request->user()->canManageRole('security')) {
                    $manageableRoles[] = 'security';
                }

                $q->whereIn('slug', $manageableRoles);
            });

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'ilike', "%{$search}%")
                    ->orWhere('last_name', 'ilike', "%{$search}%")
                    ->orWhere('email', 'ilike', "%{$search}%")
                    ->orWhere('institution_id', 'ilike', "%{$search}%");
            });
        }

        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }

        if ($roleSlug) {
            $query->whereHas('role', fn ($q) => $q->where('slug', $roleSlug));
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

    public function store(StoreUserRequest $request): JsonResponse
    {
        if (! $request->user()->canManageUsers()) {
            return response()->json(['message' => 'You are not authorized to create users.'], 403);
        }

        $validated = $request->validated();

        $role = Role::where('slug', $validated['role'])->firstOrFail();
        $prefix = ($validated['role'] === 'student') ? 'STD' : 'STF';

        $title = $validated['title'] ?? ($validated['gender'] === 'male' ? 'Mr.' : 'Miss');

        $institutionId = $this->userRepository->generateInstitutionId($prefix);

        $firstName = $validated['first_name'];
        $lastName = $validated['last_name'];

        // The account is created with a random temporary password that is
        // emailed to the owner and must be changed on first login. The
        // plaintext is never stored or returned in the API response.
        $temporaryPassword = $this->passwordResetService->generateTemporaryPassword();

        // All database changes for the new account are committed atomically:
        // either the user (with their generated institution ID and hashed
        // temporary password) is fully created, or nothing is written. No
        // partial rows are left behind if any part fails.
        $user = DB::transaction(function () use ($role, $firstName, $lastName, $validated, $institutionId, $title, $temporaryPassword) {
            return User::create([
                'role_id' => $role->id,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $validated['email'],
                'institution_id' => $institutionId,
                // Stored as bcrypt(sha256(temp)) so the client-side SHA-256 login
                // flow verifies against the plaintext the user types.
                'password' => Hash::make(hash('sha256', $temporaryPassword)),
                'department_id' => $validated['department'],
                'gender' => $validated['gender'],
                'title' => $title,
                'is_active' => true,
                'must_change_password' => true,
                'email_verified_at' => now(),
            ]);
        });

        $user->load('role', 'department');

        $this->auditLogger->log(AuditAction::UserCreated, $request->user(), $user, 'User created by administrator', [
            'role' => $role->slug,
        ]);

        // Email dispatch is fail-open by design: the account is committed and
        // audited before delivery is attempted, so a transport failure can
        // never turn into a half-created account. If delivery fails the
        // administrator is told and can use Reset Password to re-issue the
        // credentials, which never duplicates the account.
        $delivered = $this->passwordResetService->sendTemporaryPassword($user, $temporaryPassword);

        return response()->json([
            'message' => $delivered
                ? 'User created successfully. A temporary password has been sent to the user\'s email.'
                : 'User account created successfully, but the temporary password email could not be sent. You can reset the password to resend it.',
            'user' => $user,
            'credentials_delivered' => $delivered,
        ], 201);
    }

    public function show(Request $request, User $user): JsonResponse
    {
        if (! $request->user()->canManageRole($user->role?->slug ?? '')) {
            return response()->json(['message' => 'You are not authorized to view this user.'], 403);
        }

        return response()->json($user->load('role', 'department'));
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        if (! $request->user()->canManageUsers()) {
            return response()->json(['message' => 'You are not authorized to update users.'], 403);
        }

        $validated = $request->validated();

        $data = [];

        if ($request->has('first_name') || $request->has('last_name')) {
            $firstName = $request->filled('first_name') ? $validated['first_name'] : $user->first_name;
            $lastName = $request->filled('last_name') ? $validated['last_name'] : $user->last_name;
            $data['first_name'] = $firstName;
            $data['last_name'] = $lastName;
        }

        if ($request->has('email')) {
            $data['email'] = $validated['email'];
        }
        if ($request->has('gender')) {
            $data['gender'] = $validated['gender'];
        }
        if ($request->has('title')) {
            $data['title'] = $validated['title'];
        }
        if ($request->has('department')) {
            $data['department_id'] = $validated['department'];
        }
        if ($request->has('is_active')) {
            $data['is_active'] = $request->boolean('is_active');
        }

        $user->update($data);
        $user->load('role', 'department');

        $this->auditLogger->log(AuditAction::UserUpdated, $request->user(), $user, 'User updated by administrator', [
            'changed_fields' => array_keys($data),
        ]);

        return response()->json([
            'message' => 'User updated successfully.',
            'user' => $user,
        ]);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        if (! $request->user()->canManageUsers()) {
            return response()->json(['message' => 'You are not authorized to delete users.'], 403);
        }

        if ((int) $user->id === (int) $request->user()->id) {
            return response()->json(['message' => 'You cannot delete your own account.'], 403);
        }

        if ($user->role?->slug === 'admin' && User::where('role_id', $user->role_id)->count() <= 1) {
            return response()->json(['message' => 'You cannot delete the last administrator account.'], 403);
        }

        $user->delete();

        $this->auditLogger->log(AuditAction::UserDeleted, $request->user(), $user, 'User deleted by administrator', [
            'role' => $user->role?->slug,
            'full_name' => $user->full_name,
        ]);

        return response()->json([
            'message' => 'User deleted successfully.',
        ]);
    }

    public function updateRole(UpdateUserRoleRequest $request, User $user): JsonResponse
    {
        $roleSlug = $request->validated('role');

        if (! $request->user()->canAssignRole($roleSlug)) {
            return response()->json(['message' => 'You are not authorized to assign this role.'], 403);
        }

        if ((int) $user->id === (int) $request->user()->id) {
            return response()->json(['message' => 'You cannot change your own role.'], 403);
        }

        $role = Role::where('slug', $roleSlug)->firstOrFail();
        $oldRoleId = (int) $user->role_id;
        $user->role_id = $role->id;
        $user->save();
        $user->load('role', 'department');

        $this->auditLogger->log(AuditAction::RoleChanged, $request->user(), $user, 'User role changed', [
            'old_role_id' => $oldRoleId,
            'new_role_id' => (int) $user->role_id,
            'new_role' => $roleSlug,
        ]);

        return response()->json([
            'message' => 'Role updated successfully.',
            'user' => $user,
        ]);
    }

    public function resetPassword(ResetUserPasswordRequest $request, User $user): JsonResponse
    {
        if (! $request->user()->canManageUsers()) {
            return response()->json(['message' => 'You are not authorized to reset passwords.'], 403);
        }

        // Generate a random temporary password (never derivable from the
        // institution ID), force a change on next login, invalidate any
        // existing verification challenges and revoke all active
        // sessions/tokens so a compromised account cannot remain
        // authenticated. The password, challenge deletion and token
        // revocation are committed atomically.
        $temporaryPassword = DB::transaction(function () use ($user) {
            $plaintext = $this->passwordResetService->applyTemporaryPassword($user);

            $user->passwordChangeVerifications()->delete();
            $user->tokens()->delete();

            return $plaintext;
        });

        // Fail-open email dispatch: the reset state is already committed, so a
        // delivery failure must not surface as an error — the administrator can
        // retry via this same endpoint without side effects.
        $delivered = $this->passwordResetService->sendTemporaryPassword($user, $temporaryPassword);

        $this->auditLogger->log(AuditAction::AdminPasswordReset, $request->user(), $user, 'Password reset by administrator', [
            'tokens_revoked' => true,
        ]);

        return response()->json([
            'message' => $delivered
                ? 'A temporary password has been sent to the user\'s email.'
                : 'The password has been reset, but the temporary password email could not be sent. Please try again.',
            'credentials_delivered' => $delivered,
        ]);
    }
}
