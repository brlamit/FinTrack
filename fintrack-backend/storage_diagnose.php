<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $disk = env('AVATAR_DISK', 'supabase_avatars');
    $path = 'diagnostics/' . uniqid('diag_', true) . '.txt';
    $ok = Illuminate\Support\Facades\Storage::disk($disk)->put($path, 'ok');
    $exists = Illuminate\Support\Facades\Storage::disk($disk)->exists($path);
    echo json_encode(['ok' => $ok, 'disk' => $disk, 'path' => $path, 'exists' => $exists]) . PHP_EOL;
} catch (Throwable $e) {
    $prev = $e->getPrevious();
    $prevInfo = null;
    if ($prev) {
        $prevInfo = [
            'message' => $prev->getMessage(),
            'class' => get_class($prev),
            'code' => $prev->getCode(),
            'trace' => $prev->getTraceAsString(),
        ];
    }

    echo json_encode([
        'error' => $e->getMessage(),
        'class' => get_class($e),
        'code' => $e->getCode(),
        'trace' => $e->getTraceAsString(),
        'previous' => $prevInfo,
    ]) . PHP_EOL;
}
