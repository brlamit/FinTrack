<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\OtpNotification;
use App\Services\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class OtpController extends Controller
{
    public function __construct(private readonly OtpService $otpService)
    {
    }

    public function show(Request $request)
    {
        $context = Session::get('otp.context');
        $userId = Session::get('otp.user_id');

        if (!$context || !$userId) {
            return redirect()->route('auth.login');
        }

        $user = User::find($userId);

        if (!$user) {
            return redirect()->route('auth.login');
        }

        return view('auth.verify-otp', [
            'email' => $user->email,
            'context' => $context,
        ]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $context = Session::get('otp.context');
        $userId = Session::get('otp.user_id');

        if (!$context || !$userId) {
            return redirect()->route('auth.login');
        }

        $digits = [
            trim((string) $request->input('otp_1')),
            trim((string) $request->input('otp_2')),
            trim((string) $request->input('otp_3')),
            trim((string) $request->input('otp_4')),
        ];

        $code = implode('', $digits);

        if (strlen($code) !== 4 || !ctype_digit($code)) {
            return back()->withErrors(['otp' => 'Enter the 4-digit verification code.']);
        }

        $user = User::find($userId);

        if (!$user) {
            return redirect()->route('auth.login');
        }

        if (!$this->otpService->validate($user, $context, $code)) {
            return back()->withErrors(['otp' => 'The verification code is invalid or has expired.']);
        }

        if ($context === 'registration') {
            $user->forceFill([
                'email_verified_at' => now(),
                'first_login_done' => true,
                'password_changed_at' => $user->password_changed_at ?? now(),
            ])->save();

            Session::forget(['otp.context', 'otp.user_id']);

            Auth::login($user);

            return redirect()->route('user.dashboard')->with('success', 'Registration complete!');
        }

        if ($context === 'password_reset') {
            Session::put('password_reset.email', $user->email);
            Session::put('password_reset.verified', true);
            Session::forget(['otp.context', 'otp.user_id']);

            return redirect()->route('auth.reset-password')->with('status', 'Verification successful. Set your new password.');
        }

        if ($context === 'password_change') {
            Session::put('password_change.verified', true);
            Session::forget(['otp.context', 'otp.user_id']);

            return back()->with('status', 'Verification successful. You can now update your password.');
        }

        return redirect()->route('auth.login');
    }

    public function resend(Request $request): RedirectResponse
    {
        $context = Session::get('otp.context');
        $userId = Session::get('otp.user_id');

        if (!$context || !$userId) {
            return redirect()->route('auth.login');
        }

        $user = User::find($userId);

        if (!$user) {
            return redirect()->route('auth.login');
        }

        $otp = $this->otpService->generate($user, $context);
        $user->notify(new OtpNotification($otp->code, $context));

        return back()->with('status', 'A new verification code has been sent.');
    }

    public function sendPasswordChangeOtp(Request $request): RedirectResponse
    {
        $user = $request->user();

        $otp = $this->otpService->generate($user, 'password_change');
        $user->notify(new OtpNotification($otp->code, 'password_change'));

        return back()->with('status', 'We have emailed a verification code to ' . $user->email . '.');
    }
}
