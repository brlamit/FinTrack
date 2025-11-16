<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;

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
            'avatar' => ['required', 'image', 'max:5120'], // 5MB
        ]);

        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $disk = env('AVATAR_DISK', 'public');

        $file = $request->file('avatar');

        // delete previous avatar file if stored on a disk and looks like a stored path
        $previousPath = $user->getRawOriginal('avatar');
        $previousDisk = $user->getRawOriginal('avatar_disk') ?: $disk;
        if ($previousPath && !str_starts_with($previousPath, 'http')) {
            try {
                Storage::disk($previousDisk)->delete($previousPath);
            } catch (\Throwable $e) {
                // log and continue
                Log::warning('Failed to delete previous avatar', ['user' => $user->id, 'path' => $previousPath, 'disk' => $previousDisk, 'error' => $e->getMessage()]);
            }
        }

        // attempt to resize image server-side when GD functions are available
        $path = null;
        try {
            $maxWidth = 800; // max width preserved
            $maxHeight = 800;

            if (function_exists('imagecreatefromstring') && function_exists('imagecreatetruecolor')) {
                $imgData = file_get_contents($file->getRealPath());
                $src = @imagecreatefromstring($imgData);
                if ($src) {
                    $width = imagesx($src);
                    $height = imagesy($src);

                    // compute new size preserving aspect
                    $scale = min(1, min($maxWidth / $width, $maxHeight / $height));
                    $newW = (int) round($width * $scale);
                    $newH = (int) round($height * $scale);

                    if ($scale < 1) {
                        $dst = imagecreatetruecolor($newW, $newH);
                        // preserve transparency for png/gif
                        imagealphablending($dst, false);
                        imagesavealpha($dst, true);
                        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $width, $height);

                        // determine extension and save to temp file
                        $ext = strtolower($file->getClientOriginalExtension() ?: pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION));
                        $tempPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('avatar_', true) . '.' . ($ext ?: 'jpg');
                        if (in_array($ext, ['png'])) {
                            imagepng($dst, $tempPath);
                        } elseif (in_array($ext, ['gif'])) {
                            imagegif($dst, $tempPath);
                        } else {
                            imagejpeg($dst, $tempPath, 85);
                        }

                        imagedestroy($dst);
                        imagedestroy($src);

                        // store resized file
                        $storedName = basename($tempPath);
                        $stored = Storage::disk($disk)->putFileAs('avatars', new \Illuminate\Http\File($tempPath), $storedName);
                        // remove temp file
                        @unlink($tempPath);

                        $path = $stored;
                    } else {
                        imagedestroy($src);
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Image resize failed, falling back to direct store', ['error' => $e->getMessage()]);
        }

        // fallback: store the new file directly if resize didn't produce a path
        if (!$path) {
            $path = $file->store('avatars', $disk);
        }

        // Save the path and disk on the user
        $user->avatar = $path;
        $user->avatar_disk = $disk;
        $user->save();

        return response()->json([
            'success' => true,
            'avatar' => $user->avatar, // accessor returns full URL when available
            'avatar_url' => $user->avatar,
        ]);
    }

    public function edit()
    {
        $user = Auth::user();
        return view('admin.profile.edit', compact('user'));
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

        if ($previousPath && !str_starts_with($previousPath, 'http')) {
            try {
                Storage::disk($previousDisk)->delete($previousPath);
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
}
