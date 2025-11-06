<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class QrController extends Controller
{
    /**
     * Parse QR code for transaction data.
     */
    public function parse(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'qr_data' => 'required|string|max:1000',
            'qr_type' => 'nullable|string|in:payment,receipt,card',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $qrData = $request->qr_data;
        $qrType = $request->qr_type ?? 'payment';

        $parsedData = [];

        // TODO: Implement QR code parsing logic
        // This would parse different QR code formats (UPI, credit card, receipt QR codes, etc.)

        switch ($qrType) {
            case 'payment':
                // Parse UPI or payment QR codes
                $parsedData = [
                    'type' => 'payment',
                    'amount' => 100.00,
                    'merchant' => 'Sample Merchant',
                    'reference' => 'TXN123456',
                ];
                break;

            case 'receipt':
                // Parse receipt QR codes
                $parsedData = [
                    'type' => 'receipt',
                    'total_amount' => 45.67,
                    'items' => [
                        ['name' => 'Item 1', 'price' => 15.00],
                        ['name' => 'Item 2', 'price' => 30.67],
                    ],
                    'merchant' => 'Sample Store',
                ];
                break;

            case 'card':
                // Parse credit/debit card QR codes
                $parsedData = [
                    'type' => 'card',
                    'card_number' => '**** **** **** 1234',
                    'card_type' => 'Visa',
                    'expiry' => '12/25',
                ];
                break;
        }

        return response()->json([
            'success' => true,
            'data' => $parsedData,
        ]);
    }
}