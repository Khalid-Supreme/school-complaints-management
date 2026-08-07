<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterStaffRequest;
use App\Http\Requests\RegisterStudentRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

use App\Repositories\UserRepository;

class RegisterController extends Controller
{
    protected UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
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
        }

        return redirect('/login?verified=1&institution_id=' . $user->institution_id);
    }

    public function resend(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ]);

        $user = User::where('email', $request->email)->firstOrFail();

        if (is_null($user->email_verified_at)) {
            $this->sendVerificationMail($user);
        }

        return response()->json([
            'message' => 'Verification email sent successfully.',
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
