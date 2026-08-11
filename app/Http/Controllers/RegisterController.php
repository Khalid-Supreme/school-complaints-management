<?php

namespace App\Http\Controllers;

use App\Enums\AuditAction;
use App\Http\Requests\Auth\ResendVerificationRequest;
use App\Http\Requests\RegisterStaffRequest;
use App\Http\Requests\RegisterStudentRequest;
use App\Models\Role;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class RegisterController extends Controller
{
    protected UserRepository $userRepository;

    protected AuditLogger $auditLogger;

    public function __construct(UserRepository $userRepository, AuditLogger $auditLogger)
    {
        $this->userRepository = $userRepository;
        $this->auditLogger = $auditLogger;
    }

    public function registerStudent(RegisterStudentRequest $request)
    {
        return $this->registerUser($request, 'student', 'STD');
    }

    public function registerStaff(RegisterStaffRequest $request)
    {
        return $this->registerUser($request, 'staff', 'STF');
    }

    public function verify(Request $request, User $user, string $hash)
    {
        if (! $request->hasValidSignature() || $hash !== sha1($user->email)) {
            abort(403);
        }

        if (is_null($user->email_verified_at)) {
            $user->forceFill([
                'email_verified_at' => now(),
                'is_active' => true,
            ])->save();

            $this->auditLogger->log(AuditAction::EmailVerified, $user, $user, 'Email address verified');
        }

        return redirect('/login?verified=1&institution_id='.$user->institution_id);
    }

    public function resend(ResendVerificationRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        // Uniform response regardless of whether the account exists, so the
        // endpoint cannot be used to enumerate registered email addresses.
        if ($user && is_null($user->email_verified_at)) {
            $this->sendVerificationMail($user);
        }

        return response()->json([
            'message' => 'If the account exists and is unverified, a verification email has been sent.',
        ]);
    }

    protected function registerUser($request, string $roleSlug, string $prefix)
    {
        $role = Role::where('slug', $roleSlug)->firstOrFail();
        $institutionId = $this->userRepository->generateInstitutionId($prefix);

        $firstName = $request->first_name;
        $lastName = $request->last_name;
        $fullName = User::composeName($firstName, $lastName);

        $data = [
            'role_id' => $role->id,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'name' => $fullName,
            'email' => $request->email,
            'institution_id' => $institutionId,
            'password' => Hash::make($request->password),
            'department_id' => $request->department,
            'gender' => $request->gender,
            'is_active' => true,
            'email_verified_at' => null,
        ];

        if ($roleSlug === 'student') {
            $data['title'] = $request->gender === 'male' ? 'Mr.' : 'Miss';
        } else {
            $data['title'] = $request->title;
        }

        $user = User::create($data);

        $this->auditLogger->log(AuditAction::UserRegistered, $user, $user, 'User registered via public form', [
            'role' => $roleSlug,
        ]);

        $this->sendVerificationMail($user);

        return response()->json([
            'message' => 'Registration successful. Check your email to verify your account.',
            'institution_id' => $institutionId,
            'email' => $user->email,
        ], 201);
    }

    protected function generateInstitutionId(string $prefix): string
    {
        return $this->userRepository->generateInstitutionId($prefix);
    }

    protected function sendVerificationMail(User $user): void
    {
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['user' => $user->id, 'hash' => sha1($user->email)]
        );

        Mail::send('emails.verification', [
            'user' => $user,
            'verificationUrl' => $verificationUrl,
        ], function ($message) use ($user) {
            $message->to($user->email, $user->full_name)->subject('Verify your email address');
        });
    }
}
