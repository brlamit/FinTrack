<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class VoiceController extends Controller
{
    /**
     * Parse voice input for transaction data.
     */
    public function parse(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'audio' => 'required|file|mimes:mp3,wav,m4a|max:10240', // 10MB max
            'language' => 'nullable|string|in:en,np',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // TODO: Implement voice-to-text processing
        // This would send audio to speech-to-text service and parse the transaction data

        $parsedData = [
            'amount' => 25.50,
            'description' => 'Coffee at Starbucks',
            'category' => 'Food & Dining',
            'confidence' => 0.95,
        ];

        return response()->json([
            'success' => true,
            'data' => $parsedData,
        ]);
    }
}