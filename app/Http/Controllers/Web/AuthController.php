<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function __construct(private readonly NotificationService $notificationService)
    {
    }

    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function showSignupForm()
    {
        return view('auth.signup', [
            'signupRoles' => [
                User::ROLE_OPERATIONS_MANAGER,
                User::ROLE_MANAGING_DIRECTOR,
                User::ROLE_GENERAL_MANAGER,
                User::ROLE_TECHNICIAN,
            ],
        ]);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()->where('email', $credentials['email'])->first();

        if ($user && Hash::check($credentials['password'], $user->password) && ! $user->is_approved) {
            return back()->withErrors([
                'email' => 'Your account is pending operations manager approval.',
            ])->onlyInput('email');
        }

        if (! Auth::attempt($credentials, true)) {
            return back()->withErrors([
                'email' => 'Invalid login credentials.',
            ])->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    public function signup(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:30'],
            'role' => ['required', 'in:operations_manager,managing_director,general_manager,technician'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => $validated['password'],
            'role' => $validated['role'],
            'is_approved' => false,
            'approved_at' => null,
            'approved_by' => null,
        ]);

        $this->notificationService->sendSignupPendingApproval($user);

        return redirect()
            ->route('login')
            ->with('success', 'Sign-up submitted. Your account is pending operations manager approval.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
