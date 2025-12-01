<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\OtpNotification;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(private readonly OtpService $otpService)
    {
    }
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

        // Enforce login must be an email
        if (!filter_var($login, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages([
            'login' => ['The login must be a valid email address.'],
            ]);
        }
        $field = 'email';

        $credentials = [
            $field => $login,
            'password' => $password,
        ];

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            
            $user = Auth::user();

            if (!$user->email_verified_at) {
                Auth::logout();

                $otp = $this->otpService->generate($user, 'registration');
                $user->notify(new OtpNotification($otp->code, 'registration'));

                Session::put('otp.context', 'registration');
                Session::put('otp.user_id', $user->id);

                return redirect()->route('auth.otp.show')
                    ->with('status', 'Please verify your email address. We have sent you a new code.');
            }

            // Check if user needs to change password on first login
            if (!$user->first_login_done) {
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
            'email_verified_at' => null,
            'password' => Hash::make($validated['password']),
            'password_changed_at' => now(),
            'first_login_done' => true,
        ]);

        $otp = $this->otpService->generate($user, 'registration');
        $user->notify(new OtpNotification($otp->code, 'registration'));

        Session::put('otp.context', 'registration');
        Session::put('otp.user_id', $user->id);

        return redirect()->route('auth.otp.show')
            ->with('status', 'We have sent a verification code to your email. Enter the code to finish registration.');
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

        return redirect()->route('auth.login')
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
        // Support invoking forgot-password from the security page when user is logged in
        $email = $request->input('email');
        if (!$email && $request->user()) {
            $email = $request->user()->email;
        }

        // Generic response to avoid revealing account existence
        $genericStatus = 'If that email address is in our system, we have sent a password reset link.';

        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return back()->with('status', $genericStatus);
        }

        $user = User::where('email', $email)->first();

        if (!$user) {
            return back()->with('status', $genericStatus);
        }

        $otp = $this->otpService->generate($user, 'password_reset');
        $user->notify(new OtpNotification($otp->code, 'password_reset'));

        Session::put('otp.context', 'password_reset');
        Session::put('otp.user_id', $user->id);

        return redirect()->route('auth.otp.show')
            ->with('status', 'If that email exists, we have sent a verification code to it. Please check your inbox.');
    }

    /**
     * Show password reset form
     */
    public function showResetPassword()
    {
        if (!Session::get('password_reset.verified')) {
            return redirect()->route('auth.forgot-password');
        }

        $email = Session::get('password_reset.email');

        if (!$email) {
            return redirect()->route('auth.forgot-password');
        }

        return view('auth.reset-password', ['email' => $email]);
    }

    /**
     * Handle password reset
     */
    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => [
                'required',
                'string',
                'min:8',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/',
                'confirmed',
            ],
        ]);

        $emailFromSession = Session::get('password_reset.email');

        if (!$emailFromSession || $emailFromSession !== $validated['email']) {
            return redirect()->route('auth.forgot-password')->withErrors([
                'email' => 'Verification required. Request a new password reset.',
            ]);
        }

        $user = User::where('email', $validated['email'])->first();

        if (!$user) {
            return back()->withErrors(['email' => 'Email not found']);
        }

        $user->update([
            'password' => Hash::make($validated['password']),
            'password_changed_at' => now(),
        ]);

        // Clear password reset session data
        Session::forget(['password_reset.email', 'password_reset.verified']);

        // If the user was logged in during the reset flow, log them out to require re-authentication
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('auth.login')
            ->with('success', 'Password reset successfully! Please log in with your new password.');
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
