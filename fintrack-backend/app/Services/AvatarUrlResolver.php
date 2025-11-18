<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use App\Models\User;

class AvatarUrlResolver
{
    public function resolve(User $user, string $path): ?string
    {
        $path = ltrim($path, '/');

        // Already a full Supabase public URL?
        if (str_starts_with($path, 'https://rbvuivngveilamxliumb.supabase.co/storage/v1/object/public/avatars/')) {
            return $path;
        }

        $disk = $user->getRawOriginal('avatar_disk') ?: env('AVATAR_DISK', 'public');

        // 1. Local fallback first (most reliable during upload issues)
        if ($disk === 'public' || file_exists(storage_path("app/public/{$path}"))) {
            try {
                return Storage::disk('public')->url($path);
            } catch (\Throwable) {
                // continue
            }
        }

        // 2. Supabase public URL shortcut
        if ($disk === 'supabase' && env('SUPABASE_PUBLIC_URL')) {
            return rtrim(env('SUPABASE_PUBLIC_URL'), '/') . '/' . $path;
        }

        // 3. Standard disk URL
        try {
            return Storage::disk($disk)->url($path);
        } catch (\Throwable) {
            // 4. Final fallback
            try {
                return Storage::disk('public')->url($path);
            } catch (\Throwable) {
                return $path; // raw path as last resort
            }
        }
    }
}