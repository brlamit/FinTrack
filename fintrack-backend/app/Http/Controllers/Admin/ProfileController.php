<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function show()
    {
        $user = Auth::user();
        return view('admin.profile', compact('user'));
    }

      public function updateAvatar(Request $request)
{
    $request->validate([
        'avatar' => 'required|image|mimes:jpeg,png,jpg,webp,gif|max:8192',
    ]);

    $user = $request->user();
    $file = $request->file('avatar');

    // Delete old avatar (handle stored URL or storage key)
    $oldPath = $user->getRawOriginal('avatar');
    $oldDisk = $user->getRawOriginal('avatar_disk') ?? 'public';

    if ($oldPath && $oldPath !== 'default.png') {
        try {
            if (strpos($oldPath, 'http://') === 0 || strpos($oldPath, 'https://') === 0) {
                $diskUrl = config("filesystems.disks.{$oldDisk}.url");
                if (!empty($diskUrl) && strpos($oldPath, $diskUrl) === 0) {
                    $maybeKey = ltrim(substr($oldPath, strlen($diskUrl)), '/');
                    Storage::disk($oldDisk)->delete($maybeKey);
                }
                // otherwise skip deletion since we can't derive a key
            } else {
                Storage::disk($oldDisk)->delete($oldPath);
            }
        } catch (\Throwable $e) {
            // Ignore if already deleted or deletion failed
        }
    }

    // Determine which disk to use for avatars (allow overriding via AVATAR_DISK env)
    $disk = env('AVATAR_DISK', config('filesystems.default'));
    $available = array_keys(config('filesystems.disks', []));
    if (!in_array($disk, $available, true)) {
        // fallback to configured default disk, then 'public'
        $disk = config('filesystems.default');
        if (!in_array($disk, $available, true)) {
            $disk = 'public';
        }
    }

    // Upload new avatar to configured disk
    $filename = $user->id . '_' . Str::random(20) . '.' . $file->extension();
    try {
        $imagePath = $file->storeAs("avatars/{$user->id}", $filename, $disk);
    } catch (\Throwable $e) {
        Log::error('Avatar upload failed: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Avatar upload failed. Check logs.'
        ], 500);
    }

    if ($imagePath === false || $imagePath === null) {
        Log::error('Avatar upload returned false/null for disk: ' . $disk);
        return response()->json([
            'success' => false,
            'message' => 'Avatar upload failed. Check logs.'
        ], 500);
    }

    // Make sure it's public if the disk supports visibility
    try {
        Storage::disk($disk)->setVisibility($imagePath, 'public');
    } catch (\Throwable $e) {
        // Ignore - some disks or buckets are already public or don't support visibility
    }

    // save object key and the public URL (include bucket in the public URL when available)
    $avatarToSave = $imagePath;
    $diskConfig = config("filesystems.disks.{$disk}", []);
    $generated = null;
    try {
        $generated = Storage::disk($disk)->url($imagePath);
    } catch (\Throwable $e) {
        $generated = null;
    }

    $bucket = $diskConfig['bucket'] ?? null;
    // If generated URL exists but is missing the bucket, discard it so we can build one that includes the bucket
    if (!empty($generated) && !empty($bucket) && strpos($generated, trim($bucket, '/')) === false) {
        $generated = null;
    }

    if (empty($generated) && !empty($diskConfig['url'])) {
        $diskUrl = rtrim($diskConfig['url'], '/');
        $encodedKey = implode('/', array_map('rawurlencode', explode('/', $imagePath)));
        if (!empty($bucket)) {
            $generated = $diskUrl . '/' . trim($bucket, '/') . '/' . ltrim($encodedKey, '/');
        } else {
            $generated = $diskUrl . '/' . ltrim($encodedKey, '/');
        }
    }

    if (!empty($generated)) {
        $avatarToSave = $generated;
    }

    // Save avatar (URL or key) + disk
    $user->update([
        'avatar'      => $avatarToSave,
        'avatar_disk' => $disk,
    ]);

    return response()->json([
        'success'    => true,
        'avatar_url' => $user->fresh()->avatar . '?v=' . now()->timestamp,
        'message'    => 'Avatar updated successfully!',
    ]);
}

    public function edit()
    {
        $user = Auth::user();
        return view('admin.profile_edit', compact('user'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return Redirect::back()->with('error', 'Unauthorized');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'phone' => ['nullable', 'string', 'max:50'],
        ]);

        $user->fill($data);
        $user->save();

        return Redirect::route('admin.profile')->with('success', 'Profile updated');
    }

    public function removeAvatar(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return Redirect::back()->with('error', 'Unauthorized');
        }

        $previousPath = $user->getRawOriginal('avatar');
        $previousDisk = $user->getRawOriginal('avatar_disk') ?: env('AVATAR_DISK', 'public');

        if ($previousPath && $previousPath !== 'default.png') {
            try {
                if (strpos($previousPath, 'http://') === 0 || strpos($previousPath, 'https://') === 0) {
                    // Extract the storage key from full URL
                    $diskConfig = config("filesystems.disks.{$previousDisk}", []);
                    $diskUrl = $diskConfig['url'] ?? '';
                    $bucket = $diskConfig['bucket'] ?? '';
                    
                    if (!empty($diskUrl) && strpos($previousPath, $diskUrl) === 0) {
                        $maybeKey = ltrim(substr($previousPath, strlen($diskUrl)), '/');
                        // Remove bucket prefix if present
                        if (!empty($bucket) && strpos($maybeKey, trim($bucket, '/') . '/') === 0) {
                            $maybeKey = substr($maybeKey, strlen(trim($bucket, '/')) + 1);
                        }
                        if (!empty($maybeKey)) {
                            Storage::disk($previousDisk)->delete($maybeKey);
                        }
                    }
                } else {
                    Storage::disk($previousDisk)->delete($previousPath);
                }
            } catch (\Throwable $e) {
                Log::warning('Failed to delete avatar on remove', ['user' => $user->id, 'path' => $previousPath, 'error' => $e->getMessage()]);
            }
        }

        // clear avatar fields
        $user->avatar = null;
        $user->avatar_disk = null;
        $user->save();

        return Redirect::route('admin.profile')->with('success', 'Avatar removed');
    }

    /**
     * Show security settings page
     */
    public function security()
    {
        $user = Auth::user();
        return view('admin.security', compact('user'));
    }

    /**
     * Update admin password
     */
    public function updatePassword(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return Redirect::back()->with('error', 'Unauthorized');
        }

        $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:8', 'confirmed', Password::defaults()],
        ]);

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'The provided password does not match our records.']);
        }

        $user->update([
            'password' => Hash::make($request->new_password),
            'password_changed_at' => now(),
        ]);

        return back()->with('password_updated', 'Password updated successfully!');
    }

    /**
     * Show preferences page
     */
    public function preferences()
    {
        $user = Auth::user();
        return view('admin.preferences', compact('user'));
    }

    /**
     * Update admin preferences
     */
    public function updatePreferences(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return Redirect::back()->with('error', 'Unauthorized');
        }

        $preferences = [
            'notify_email' => $request->has('notify_email'),
            'notify_system' => $request->has('notify_system'),
            'notify_users' => $request->has('notify_users'),
            'theme' => $request->input('theme', 'light'),
            'dashboard_layout' => $request->input('dashboard_layout', 'default'),
            'items_per_page' => $request->input('items_per_page', 25),
        ];

        $user->preferences = $preferences;
        $user->save();

        return back()->with('preferences_updated', 'Preferences updated successfully!');
    }

    /**
     * Logout from all sessions
     */
    public function logoutAll(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return Redirect::back()->with('error', 'Unauthorized');
        }

        // Regenerate session and invalidate all other sessions
        Auth::logoutOtherDevices($request->password ?? '');
        
        return back()->with('success', 'Logged out from all other sessions.');
    }
}
