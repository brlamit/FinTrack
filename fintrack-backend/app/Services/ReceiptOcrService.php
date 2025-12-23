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
                $response = Http::asForm()
                    ->timeout(45)
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
            }

            /**
             * 2️⃣ Local Tesseract (Windows-safe)
             */
            if ($normalized === null) {
                try {
                    $tmpDir = storage_path('app/tmp');
                    if (!is_dir($tmpDir)) mkdir($tmpDir, 0777, true);

                    $path = $tmpDir . DIRECTORY_SEPARATOR . 'receipt_'.$receipt->id.'.jpg';

                    $response = Http::timeout(20)->get($imageUrl);

                    if (!$response->successful()) {
                        throw new \Exception('Failed to download receipt image: '.$response->status());
                    }

                    file_put_contents($path, $response->body());

                    if (!file_exists($path) || filesize($path) === 0) {
                        throw new \Exception('Downloaded receipt image is empty');
                    }

                    $realPath = realpath($path);
                    if (!$realPath) {
                        throw new \Exception('Receipt image not found after download');
                    }

                    $text = (new TesseractOCR($realPath))
                        ->executable('C:\\Program Files\\Tesseract-OCR\\tesseract.exe')
                        ->lang('eng')
                        ->run();

                    if (trim($text) !== '') {
                        $rawResponse = [
                            'ParsedResults' => [
                                ['ParsedText' => $text],
                            ],
                        ];
                        $normalized = $rawResponse;
                    }

                    // @unlink($realPath);
                } catch (\Throwable $e) {
                    Log::warning('Tesseract OCR failed', [
                        'receipt_id' => $receipt->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            /**
             * 3️⃣ If ALL OCR failed → allow manual amount
             */
            if ($normalized === null) {
                Log::warning('All OCR providers failed', ['receipt_id' => $receipt->id]);

                $receipt->update([
                    'ocr_data' => null,
                    'parsed_data' => [
                        'raw_text' => null,
                        'estimated_total' => null,
                        'totals' => [],
                        'requires_manual_amount' => true,
                    ],
                    'processed' => true,
                ]);
                return;
            }

            /**
             * 4️⃣ Extract totals
             */
            $simplified = $this->simplifyOcrResponse($normalized);

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

protected function simplifyOcrResponse(array $ocrResponse): array
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

    foreach ($lines as $line) {
        $isTotalLine = stripos($line, 'total') !== false
            || stripos($line, 'amount') !== false
            || stripos($line, 'grand') !== false;

        if (preg_match_all('/\d{1,3}(?:[.,]\d{3})*(?:[.,]\d{2})/', $line, $matches)) {
            foreach ($matches[0] as $value) {
                $num = (float) str_replace(',', '', $value);
                $candidates[] = [
                    'value' => $num,
                    'priority' => $isTotalLine ? 2 : 1,
                ];
            }
        }
    }

    if (!empty($candidates)) {
        usort($candidates, fn ($a, $b) =>
            $b['priority'] <=> $a['priority']
            ?: $b['value'] <=> $a['value']
        );

        $result['estimated_total'] = $candidates[0]['value'];
        $result['requires_manual_amount'] = false;
    }

    return $result;
}

}
