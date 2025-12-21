<?php

namespace App\Http\Controllers;

use App\Models\Receipt;
use App\Services\ReceiptOcrService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ReceiptController extends Controller
{
    public function __construct(private readonly ReceiptOcrService $receiptOcrService)
    {
    }
    /**
     * Display a listing of the user's receipts.
     */
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
            'processed' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $query = Receipt::where('user_id', auth()->id());

        if ($request->has('processed')) {
            $query->where('processed', $request->boolean('processed'));
        }

        $receipts = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $receipts,
        ]);
    }

    /**
     * Generate presigned URL for receipt upload.
     */
    public function presign(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'filename' => 'required|string|max:255',
            'mime_type' => 'required|string|max:100',
            'size' => 'required|integer|min:1|max:10485760', // 10MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $filename = Str::uuid() . '_' . $request->filename;
        $path = 'receipts/' . auth()->id() . '/' . $filename;

        // Validate configured disk exists, fallback to 'public'
        $disk = config('filesystems.default');
        $disks = array_keys(config('filesystems.disks', []));
        if (!in_array($disk, $disks, true)) {
            $disk = 'public';
        }

        // Generate a presigned URL if supported by disk, otherwise return a storage key
        try {
            $presignedUrl = Storage::disk($disk)->temporaryUrl(
                $path,
                now()->addMinutes(15),
                ['PutObject', 'PutObjectAcl']
            );
        } catch (\Throwable $e) {
            // Some adapters may not support temporaryUrl; return the storage key instead
            $presignedUrl = null;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'upload_url' => $presignedUrl,
                'key' => $path,
                'filename' => $filename,
            ],
        ]);
    }

    /**
     * Directly upload a receipt file (for mobile clients) and run OCR.
     */
    public function uploadDirect(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'receipt' => 'required|file|image|max:5120', // 5MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $file = $request->file('receipt');

        // Store on configured disk (falling back to default/public when necessary)
        $disk = env('FILESYSTEM_DISK', config('filesystems.default'));
        $disks = array_keys(config('filesystems.disks', []));
        if (!in_array($disk, $disks, true)) {
            $disk = config('filesystems.default');
            if (!in_array($disk, $disks, true)) {
                $disk = 'public';
            }
        }

        $filename = time() . '_' . $file->getClientOriginalName();
        $path = $file->storeAs('receipts/' . auth()->id(), $filename, $disk);

        // Build a public URL (or best-effort path) similar to group/personal flows
        $pathToSave = $path;
        $generated = null;
        try {
            $generated = Storage::disk($disk)->url($path);
        } catch (\Throwable $e) {
            $generated = null;
        }

        $diskConfig = config("filesystems.disks.{$disk}", []);
        $diskUrl = $diskConfig['url'] ?? null;
        $bucket = $diskConfig['bucket'] ?? env('SUPABASE_PUBLIC_BUCKET');

        if (!empty($generated) && !empty($bucket) && strpos($generated, trim($bucket, '/')) === false) {
            $generated = null;
        }

        if (empty($generated) && !empty($diskUrl)) {
            $encodedKey = implode('/', array_map('rawurlencode', explode('/', $path)));
            if (!empty($bucket)) {
                $generated = rtrim($diskUrl, '/') . '/' . trim($bucket, '/') . '/' . ltrim($encodedKey, '/');
            } else {
                $generated = rtrim($diskUrl, '/') . '/' . ltrim($encodedKey, '/');
            }
        }

        if (!empty($generated)) {
            $pathToSave = $generated;
        }

        $receipt = Receipt::create([
            'user_id' => auth()->id(),
            'filename' => $filename,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'path' => $pathToSave,
            'size' => $file->getSize(),
            'processed' => false,
        ]);

        // Run OCR so parsed JSON (including estimated_total) is available immediately
        $this->receiptOcrService->process($receipt);

        return response()->json([
            'success' => true,
            'message' => 'Receipt uploaded successfully',
            'data' => $receipt,
        ], 201);
    }

    /**
     * Complete receipt upload and create receipt record.
     */
    public function complete(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'key' => 'required|string|max:500',
            'original_filename' => 'required|string|max:255',
            'mime_type' => 'required|string|max:100',
            'size' => 'required|integer|min:1|max:10485760',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Determine disk from env or config and verify file exists
        $disk = env('FILESYSTEM_DISK', config('filesystems.default'));
        $disks = array_keys(config('filesystems.disks', []));
        if (!in_array($disk, $disks, true)) {
            $disk = config('filesystems.default');
            if (!in_array($disk, $disks, true)) {
                $disk = 'public';
            }
        }

        if (!Storage::disk($disk)->exists($request->key)) {
            return response()->json([
                'success' => false,
                'message' => 'Uploaded file not found',
            ], 404);
        }

        // Attempt to generate a public URL from the disk. If not possible, build from disk config url.
        $pathToSave = $request->key;
        $generated = null;
        try {
            $generated = Storage::disk($disk)->url($request->key);
        } catch (\Throwable $e) {
            $generated = null;
        }

        $diskConfig = config("filesystems.disks.{$disk}", []);
        $diskUrl = $diskConfig['url'] ?? null;
        $bucket = $diskConfig['bucket'] ?? env('SUPABASE_PUBLIC_BUCKET');

        // If Storage returned a URL but it's missing the bucket segment, prefer constructing
        // a URL that includes the bucket when we have the bucket configured.
        if (!empty($generated) && !empty($bucket) && strpos($generated, trim($bucket, '/')) === false) {
            $generated = null; // force rebuild below
        }

        if (empty($generated) && !empty($diskUrl)) {
            // URL-encode each segment of the key to handle spaces and special chars
            $encodedKey = implode('/', array_map('rawurlencode', explode('/', $request->key)));
            if (!empty($bucket)) {
                $generated = rtrim($diskUrl, '/') . '/' . trim($bucket, '/') . '/' . ltrim($encodedKey, '/');
            } else {
                $generated = rtrim($diskUrl, '/') . '/' . ltrim($encodedKey, '/');
            }
        }

        if (!empty($generated)) {
            $pathToSave = $generated;
        }

        $receipt = Receipt::create([
            'user_id' => auth()->id(),
            'filename' => basename($request->key),
            'original_filename' => $request->original_filename,
            'mime_type' => $request->mime_type,
            'path' => $pathToSave,
            'size' => $request->size,
            'processed' => false,
        ]);

        // Perform OCR synchronously so that JSON data is available
        $this->receiptOcrService->process($receipt);

        return response()->json([
            'success' => true,
            'message' => 'Receipt uploaded successfully',
            'data' => $receipt,
        ], 201);
    }

    /**
     * Display the specified receipt.
     */
    public function show(Receipt $receipt): JsonResponse
    {
        // Check if receipt belongs to authenticated user
        if ($receipt->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Receipt not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $receipt,
        ]);
    }

    /**
     * Return parsed OCR data for a receipt, including estimated total.
     */
    public function parsed(Receipt $receipt): JsonResponse
    {
        if ($receipt->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Receipt not found',
            ], 404);
        }

        $parsed = $receipt->parsed_data ?? [];
        $ocr = $receipt->ocr_data ?? null;
        $estimatedTotal = is_array($parsed) && array_key_exists('estimated_total', $parsed)
            ? $parsed['estimated_total']
            : null;

        return response()->json([
            'success' => true,
            'data' => [
                'estimated_total' => $estimatedTotal,
                'parsed_data' => $parsed,
                'ocr_data' => $ocr,
            ],
        ]);
    }

    /**
     * Download the receipt file.
     */
    public function download(Receipt $receipt): JsonResponse
    {
        // Check if receipt belongs to authenticated user
        if ($receipt->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Receipt not found',
            ], 404);
        }

        // Use the model accessor which normalizes the URL (includes bucket when configured)
        $url = $receipt->url;
        // If the stored value is not a full URL, try to generate a temporary URL from the configured disk
        if (!(strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0)) {
            $disk = env('FILESYSTEM_DISK', config('filesystems.default'));
            try {
                $url = Storage::disk($disk)->temporaryUrl(
                    $receipt->path,
                    now()->addMinutes(5),
                    ['GetObject']
                );
            } catch (\Throwable $e) {
                try {
                    $url = Storage::disk($disk)->url($receipt->path);
                } catch (\Throwable $e) {
                    $url = $receipt->path;
                }
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'download_url' => $url,
                'filename' => $receipt->original_filename,
                'mime_type' => $receipt->mime_type,
            ],
        ]);
    }

    /**
     * Update the specified receipt.
     */
    public function update(Request $request, Receipt $receipt): JsonResponse
    {
        // Check if receipt belongs to authenticated user
        if ($receipt->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Receipt not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'ocr_data' => 'nullable|array',
            'parsed_data' => 'nullable|array',
            'processed' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $receipt->update($request->only([
            'ocr_data',
            'parsed_data',
            'processed',
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Receipt updated successfully',
            'data' => $receipt,
        ]);
    }

    /**
     * Remove the specified receipt.
     */
    public function destroy(Receipt $receipt): JsonResponse
    {
        // Check if receipt belongs to authenticated user
        if ($receipt->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Receipt not found',
            ], 404);
        }

        // Delete file from configured disk when possible
        $disk = config('filesystems.default');

        try {
            if (strpos($receipt->path, 'http://') === 0 || strpos($receipt->path, 'https://') === 0) {
                // Try to derive the storage key by stripping the disk url prefix
                $diskUrl = config("filesystems.disks.{$disk}.url");
                if (!empty($diskUrl) && strpos($receipt->path, $diskUrl) === 0) {
                    $maybeKey = ltrim(substr($receipt->path, strlen($diskUrl)), '/');
                    Storage::disk($disk)->delete($maybeKey);
                }
                // If we couldn't derive a key, skip deletion of remote object
            } else {
                Storage::disk($disk)->delete($receipt->path);
            }
        } catch (\Throwable $e) {
            // ignore deletion errors
        }

        $receipt->delete();

        return response()->json([
            'success' => true,
            'message' => 'Receipt deleted successfully',
        ]);
    }
}