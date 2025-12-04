<?php

namespace App\Models;

/**
 * Backward-compatible alias for the Notification model used throughout the codebase.
 *
 * Some code references `AppNotification` while the concrete model file is `Notification`.
 * This lightweight class keeps both names usable and avoids changing call-sites.
 */
class AppNotification extends Notification
{
    /**
     * Use the same table as the primary `Notification` model.
     * This ensures queries like `AppNotification::where(...)` target the
     * existing `notifications` table instead of `app_notifications`.
     *
     * @var string
     */
    protected $table = 'notifications';
    // Intentionally empty - extends Notification to provide a compatible name.
}
