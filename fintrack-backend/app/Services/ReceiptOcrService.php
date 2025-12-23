<?php

namespace App\Services;

use App\Models\Receipt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use thiagoalessio\TesseractOCR\TesseractOCR;
use Illuminate\Support\Facades\Storage;

class ReceiptOcrService
{
    public function process(Receipt $receipt): void
    {
        $imageUrl = (string) $receipt->url;
        if ($imageUrl === '') return;

        try {
            $rawResponse = null;
            $normalized = null;

            /**
             * 1️⃣ OCR.space
             */
            $ocrSpaceKey = config('services.ocr_space.key');
            if (!empty($ocrSpaceKey)) {
                try {
                    $response = Http::asForm()
                        ->timeout(30) // Reduced from 45 to 30 seconds
                        ->retry(2, 100) // Retry up to 2 times with 100ms delay
                        ->post('https://api.ocr.space/parse/image', [
                            'apikey' => $ocrSpaceKey,
                            'url' => $imageUrl,
                            'language' => 'eng',
                            'OCREngine' => 2,
                        ]);

                    if ($response->successful()) {
                        $rawResponse = $response->json();
                        if (empty($rawResponse['IsErroredOnProcessing'] ?? false)) {
                            $normalized = $rawResponse;
                        }
                    }
                } catch (\Throwable $e) {
                    Log::warning('OCR.space API failed, falling back to Tesseract', [
                        'receipt_id' => $receipt->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            /**
             * 2️⃣ Local Tesseract (Windows-safe)
             */
            if ($normalized === null) {
                try {
                    $tmpDir = storage_path('app/tmp');
                    if (!is_dir($tmpDir)) mkdir($tmpDir, 0777, true);

                    $path = $tmpDir . DIRECTORY_SEPARATOR . 'receipt_'.$receipt->id.'.jpg';

                    // Download image with timeout and retry
                    $response = Http::timeout(15)->retry(2, 500)->get($imageUrl);

                    if (!$response->successful()) {
                        throw new \Exception('Failed to download receipt image: HTTP '.$response->status());
                    }

                    file_put_contents($path, $response->body());

                    if (!file_exists($path) || filesize($path) === 0) {
                        throw new \Exception('Downloaded receipt image is empty or corrupted');
                    }

                    $realPath = realpath($path);
                    if (!$realPath) {
                        throw new \Exception('Receipt image not found after download');
                    }

                    // Check if Tesseract executable exists
                    $tesseractPath = 'C:\\Program Files\\Tesseract-OCR\\tesseract.exe';
                    if (!file_exists($tesseractPath)) {
                        throw new \Exception('Tesseract OCR executable not found at: ' . $tesseractPath);
                    }

                    $text = (new TesseractOCR($realPath))
                        ->executable($tesseractPath)
                        ->lang('eng')
                        ->run();

                    if (trim($text) !== '' && strlen(trim($text)) > 10) { // Ensure we have meaningful text
                        $rawResponse = [
                            'ParsedResults' => [
                                ['ParsedText' => $text],
                            ],
                        ];
                        $normalized = $rawResponse;
                    } else {
                        throw new \Exception('Tesseract returned empty or insufficient text');
                    }

                    // Clean up temp file
                    @unlink($realPath);
                } catch (\Throwable $e) {
                    Log::warning('Tesseract OCR failed', [
                        'receipt_id' => $receipt->id,
                        'error' => $e->getMessage(),
                        'image_url' => $imageUrl,
                    ]);
                }
            }

            /**
             * 3️⃣ If ALL OCR failed → allow manual amount but try basic pattern matching
             */
            if ($normalized === null) {
                // Try one more basic approach - look for common receipt patterns
                try {
                    $response = Http::timeout(10)->get($imageUrl);
                    if ($response->successful()) {
                        $imageContent = $response->body();
                        // Basic check if it's actually an image
                        $finfo = finfo_open(FILEINFO_MIME_TYPE);
                        $mimeType = finfo_buffer($finfo, $imageContent);
                        finfo_close($finfo);

                        if (strpos($mimeType, 'image/') === 0) {
                            Log::info('Image downloaded successfully but OCR failed', [
                                'receipt_id' => $receipt->id,
                                'mime_type' => $mimeType,
                                'size' => strlen($imageContent),
                            ]);
                        }
                    }
                } catch (\Throwable $e) {
                    Log::warning('Failed to validate image for OCR fallback', [
                        'receipt_id' => $receipt->id,
                        'error' => $e->getMessage(),
                    ]);
                }

                Log::warning('All OCR providers failed, requiring manual amount entry', [
                    'receipt_id' => $receipt->id,
                    'image_url' => $imageUrl,
                ]);

                $receipt->update([
                    'ocr_data' => null,
                    'parsed_data' => [
                        'raw_text' => null,
                        'estimated_total' => null,
                        'totals' => [],
                        'requires_manual_amount' => true,
                        'ocr_failed' => true,
                        'failure_reason' => 'All OCR services timed out or failed',
                    ],
                    'processed' => true,
                ]);
                return;
            }

            /**
             * 4️⃣ Extract totals
             */
            $simplified = $this->simplifyOcrResponse($normalized, $receipt->id);

            $receipt->update([
                'ocr_data' => $rawResponse,
                'parsed_data' => $simplified,
                'processed' => true,
            ]);

        } catch (\Throwable $e) {
            Log::error('Failed to OCR receipt', [
                'receipt_id' => $receipt->id,
                'exception' => $e->getMessage(),
            ]);
        }
    }

protected function simplifyOcrResponse(array $ocrResponse, ?int $receiptId = null): array
{
    $text = $ocrResponse['ParsedResults'][0]['ParsedText'] ?? '';

    $result = [
        'raw_text' => $text,
        'estimated_total' => null,
        'totals' => [],
        'requires_manual_amount' => true,
    ];

    if ($text === '') return $result;

    $lines = preg_split("/\R/", $text);
    $candidates = [];

    // Debug log the full OCR text for analysis
    Log::debug('OCR text analysis', [
        'receipt_id' => $receiptId,
        'text_length' => strlen($text),
        'first_200_chars' => substr($text, 0, 200),
    ]);

    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;

        // Look for total-related lines with higher priority
        $isTotalLine = stripos($line, 'total') !== false
            || stripos($line, 'amount') !== false
            || stripos($line, 'grand') !== false
            || stripos($line, 'subtotal') !== false
            || stripos($line, 'balance') !== false
            || stripos($line, 'due') !== false
            || stripos($line, 'sum') !== false
            || preg_match('/^\s*\$?\d/', $line); // Lines starting with numbers or $

        // Multiple regex patterns to catch various monetary formats
        $patterns = [
            // Standard US format with decimals: $1,234.56 or 1,234.56
            '/\$?\d{1,3}(?:,\d{3})*\.\d{2}/',
            // European format with decimals: 1.234,56 or 1234,56
            '/\d{1,3}(?:\.\d{3})*,\d{2}/',
            // Simple decimals without thousand separators: 123.45 or 123.4
            '/\b\d+\.\d{1,2}\b/',
            // Amounts with currency symbols: $123.45 or 123.45$
            '/\$?\d+(?:\.\d{1,2})?\$?/',
            // Whole numbers that might be totals (only if line contains total keywords)
            '/\b\d{2,5}\b/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $line, $matches)) {
                foreach ($matches[0] as $value) {
                    // Clean the value to extract the number
                    $cleanValue = preg_replace('/[^\d.,]/', '', $value);

                    // Handle European format (comma as decimal separator)
                    if (strpos($cleanValue, ',') !== false) {
                        // If there's a comma and no dot, treat comma as decimal
                        if (strpos($cleanValue, '.') === false) {
                            $cleanValue = str_replace(',', '.', $cleanValue);
                        } else {
                            // Both comma and dot present - comma is likely thousand separator
                            $cleanValue = str_replace(',', '', $cleanValue);
                        }
                    }

                    $num = (float) $cleanValue;

                    // Skip very small amounts (likely not totals)
                    if ($num < 0.01) continue;

                    // Skip unreasonably large amounts (likely not totals)
                    if ($num > 100000) continue;

                    // Skip amounts that are too round (like 100, 1000) unless they're in total lines
                    if (!$isTotalLine && in_array($num, [100, 500, 1000, 2000, 5000, 10000])) {
                        continue;
                    }

                    $candidates[] = [
                        'value' => $num,
                        'priority' => $isTotalLine ? 3 : 1,
                        'hasDecimal' => strpos($cleanValue, '.') !== false,
                        'line' => $line,
                        'pattern' => $pattern,
                    ];
                }
            }
        }
    }

    if (!empty($candidates)) {
        // Sort by priority (total lines first), then by decimal preference, then by value (higher amounts first)
        usort($candidates, function ($a, $b) {
            // First priority: total lines
            if ($a['priority'] !== $b['priority']) {
                return $b['priority'] <=> $a['priority'];
            }
            // Second priority: amounts with decimals over whole numbers
            if ($a['hasDecimal'] !== $b['hasDecimal']) {
                return $b['hasDecimal'] <=> $a['hasDecimal'];
            }
            // Third priority: higher values
            return $b['value'] <=> $a['value'];
        });

        // Take the highest priority candidate, but ensure it's reasonable
        $bestCandidate = $candidates[0];

        // Additional validation: check if this amount appears multiple times (more reliable)
        $valueCount = array_reduce($candidates, function ($count, $candidate) use ($bestCandidate) {
            return $count + (abs($candidate['value'] - $bestCandidate['value']) < 0.01 ? 1 : 0);
        }, 0);

        // If the amount appears multiple times, it's more likely to be correct
        if ($valueCount > 1) {
            $bestCandidate['priority'] += 1;
        }

        $result['estimated_total'] = $bestCandidate['value'];
        $result['totals'] = array_slice(array_unique(array_column($candidates, 'value')), 0, 5);
        $result['requires_manual_amount'] = false;

        Log::info('OCR extracted amount', [
            'receipt_id' => $receiptId,
            'estimated_total' => $bestCandidate['value'],
            'line' => $bestCandidate['line'],
            'priority' => $bestCandidate['priority'],
            'pattern_used' => $bestCandidate['pattern'],
            'total_candidates' => count($candidates),
        ]);
    } else {
        Log::warning('No monetary amounts found in OCR text', [
            'receipt_id' => $receiptId,
            'text_length' => strlen($text),
            'sample_text' => substr($text, 0, 100), // First 100 chars for debugging
        ]);
    }

    return $result;
}

}
