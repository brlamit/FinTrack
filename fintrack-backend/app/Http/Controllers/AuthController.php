<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Show login form
     */
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('user.dashboard');
        }
        return view('auth.login');
    }

    /**
     * Handle login
     */
    public function login(Request $request)
    {
        $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string', 'min:6'],
        ]);

        $login = $request->input('login');
        $password = $request->input('password');

        // Check if login is email or username
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $credentials = [
            $field => $login,
            'password' => $password,
        ];

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            
            $user = Auth::user();

            // Check if user needs to change password on first login
            if (!$user->password_changed_at || !$user->first_login_done) {
                return redirect()->route('auth.force-password-change');
            }

            return redirect()->intended(route('user.dashboard'));
        }

        throw ValidationException::withMessages([
            'login' => ['Invalid email/username or password.'],
        ]);
    }

    /**
     * Show registration form
     */
    public function showRegister()
    {
        if (Auth::check()) {
            return redirect()->route('user.dashboard');
        }
        return view('auth.register');
    }

    /**
     * Handle registration
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'password_changed_at' => now(),
            'first_login_done' => true,
        ]);

        Auth::login($user);

        return redirect()->route('user.dashboard')
            ->with('success', 'Account created successfully! Welcome to FinTrack.');
    }

    /**
     * Show force password change form (first login)
     */
    public function showForcePasswordChange()
    {
        return view('auth.force-password-change');
    }

    /**
     * Handle force password change (first login)
     */
    public function forcePasswordChange(Request $request)
    {
        $validated = $request->validate([
            'password' => [
                'required',
                'string',
                'min:8',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/',
                'confirmed',
            ],
        ], [
            'password.regex' => 'Password must contain at least one uppercase letter, one lowercase letter, and one number.',
            'password.confirmed' => 'Password confirmation does not match.',
        ]);

        $user = Auth::user();
        $user->update([
            'password' => Hash::make($validated['password']),
            'password_changed_at' => now(),
            'first_login_done' => true,
        ]);

        return redirect()->route('user.dashboard')
            ->with('success', 'Password set successfully! You can now access your dashboard.');
    }

    /**
     * Show forgot password form
     */
    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle forgot password
     */
    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            // Don't reveal if email exists for security
            return back()->with('status', 'If that email address is in our system, we have sent a password reset link.');
        }

        // Generate reset token
        $token = app('auth.password.broker')->createToken($user);

        // Send password reset email
        // TODO: Implement password reset email sending

        return back()->with('status', 'A password reset link has been sent to your email address.');
    }

    /**
     * Show password reset form
     */
    public function showResetPassword($token)
    {
        return view('auth.reset-password', ['token' => $token]);
    }

    /**
     * Handle password reset
     */
    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => [
                'required',
                'string',
                'min:8',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/',
                'confirmed',
            ],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user) {
            return back()->withErrors(['email' => 'Email not found']);
        }

        $user->update([
            'password' => Hash::make($validated['password']),
            'password_changed_at' => now(),
        ]);

        Auth::login($user);

        return redirect()->route('user.dashboard')
            ->with('success', 'Password reset successfully!');
    }

    /**
     * Handle logout
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('auth.login')
            ->with('success', 'Logged out successfully!');
    }
}
