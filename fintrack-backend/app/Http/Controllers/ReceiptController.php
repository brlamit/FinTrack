<?php

namespace App\Http\Controllers;

use App\Models\Receipt;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ReceiptController extends Controller
{
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

        $presignedUrl = Storage::disk('s3')->temporaryUrl(
            $path,
            now()->addMinutes(15),
            ['PutObject', 'PutObjectAcl']
        );

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

        // Verify file exists in S3
        if (!Storage::disk('s3')->exists($request->key)) {
            return response()->json([
                'success' => false,
                'message' => 'Uploaded file not found',
            ], 404);
        }

        $receipt = Receipt::create([
            'user_id' => auth()->id(),
            'filename' => basename($request->key),
            'original_filename' => $request->original_filename,
            'mime_type' => $request->mime_type,
            'path' => $request->key,
            'size' => $request->size,
            'processed' => false,
        ]);

        // TODO: Queue OCR processing job
        // ProcessReceipt::dispatch($receipt);

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

        $url = Storage::disk('s3')->temporaryUrl(
            $receipt->path,
            now()->addMinutes(5),
            ['GetObject']
        );

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

        // Delete file from S3
        Storage::disk('s3')->delete($receipt->path);

        $receipt->delete();

        return response()->json([
            'success' => true,
            'message' => 'Receipt deleted successfully',
        ]);
    }
}