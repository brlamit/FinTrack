<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;
use App\Models\UserOtp;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
    'email_verified_at',
        'username',
        'password',
        'phone',
        'avatar',
        'avatar_disk',
        'role',
        'invited_by',
        'invited_at',
        'status',
        'password_changed_at',
        'first_login_done',
    ];

    /**
     * Return a public URL for the avatar when one is set.
     */
    public function getAvatarAttribute($value)
    {
        if (!$value) {
            return null;
        }

        // If the stored value already looks like a URL, return as-is
        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return $value;
        }
        // Determine which disk the avatar is stored on. Prefer the explicitly
        // stored avatar_disk (raw) if present; otherwise use AVATAR_DISK env.
        $disk = $this->getRawOriginal('avatar_disk') ?: env('AVATAR_DISK', 'public');

        // If the file exists locally (storage/app/public/...), prefer serving
        // it from the local public disk so the frontend shows the image even
        // if a previous attempt to upload to Supabase failed.
        $localPath = storage_path('app/public/' . ltrim($value, '/'));
        if (file_exists($localPath)) {
            try {
                return Storage::disk('public')->url($value);
            } catch (\Throwable $e) {
                // ignore and continue to attempt other disks
            }
        }

        // Special case for Supabase: if SUPABASE_PUBLIC_URL is provided we can
        // build a direct public URL (useful when bucket objects are public).
        if ($disk === 'supabase' && env('SUPABASE_PUBLIC_URL')) {
            return rtrim(env('SUPABASE_PUBLIC_URL'), '/') . '/' . ltrim($value, '/');
        }

        try {
            return Storage::disk($disk)->url($value);
        } catch (\Throwable $e) {
            try {
                return Storage::disk('public')->url($value);
            } catch (\Throwable $e) {
                return $value;
            }
        }
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'invited_at' => 'datetime',
            'password_changed_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get OTP records associated with the user.
     */
    public function otps()
    {
        return $this->hasMany(UserOtp::class);
    }

    /**
     * Get the user's transactions.
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get the user's categories.
     */
    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    /**
     * Get the user's receipts.
     */
    public function receipts()
    {
        return $this->hasMany(Receipt::class);
    }

    /**
     * Get the user's groups.
     */
    public function groups()
    {
        return $this->belongsToMany(Group::class, 'group_members');
    }

    /**
     * Get the groups owned by the user.
     */
    public function ownedGroups()
    {
        return $this->hasMany(Group::class, 'owner_id');
    }

    /**
     * Get the user's budgets.
     */
    public function budgets()
    {
        return $this->hasMany(Budget::class);
    }

    /**
     * Get the user's goals.
     */
    public function goals()
    {
        return $this->hasMany(Goal::class);
    }

    /**
     * Get the user's notifications.
     */
    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Get the user's sync tokens.
     */
    public function syncTokens()
    {
        return $this->hasMany(SyncToken::class);
    }

    /**
     * Check if user is admin.
     */
    public function isAdmin()
    {
        return $this->role === 'admin';
    }
}
