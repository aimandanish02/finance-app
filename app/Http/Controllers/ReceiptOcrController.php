<?php

namespace App\Http\Controllers;

use App\Services\OcrService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use thiagoalessio\TesseractOCR\TesseractOCR;

class ReceiptOcrController extends Controller
{
    public function __construct(private OcrService $ocrService) {}

    public function extract(Request $request): JsonResponse
    {
        $request->validate([
            'receipt' => 'required|file|max:10240|mimetypes:image/jpeg,image/jpg,image/png,image/webp,image/heic,image/heif,application/pdf',
        ]);

        $file = $request->file('receipt');

        // Capture all file metadata BEFORE moving — once moved, getMimeType() fails
        $mimeType     = $file->getMimeType() ?? $file->getClientMimeType();
        $guessedExt   = $file->guessExtension() ?? 'jpg';
        $originalName = $file->getClientOriginalName();
        $fileSize     = $file->getSize();
        $realPath     = $file->getRealPath();

        Log::info('[OCR] File received', [
            'original_name' => $originalName,
            'mime_type'     => $mimeType,
            'client_mime'   => $file->getClientMimeType(),
            'size'          => $fileSize,
            'extension'     => $guessedExt,
            'real_path'     => $realPath,
        ]);

        // Use Laravel's local disk root (storage/app/private) — not storage_path('app')
        $localDiskRoot = config('filesystems.disks.local.root');
        $tmpDir        = $localDiskRoot . '/ocr-temp';
        $tmpFilename   = uniqid('ocr_', true) . '.' . $guessedExt;
        $fullPath      = $tmpDir . '/' . $tmpFilename;

        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0755, true);
        }

        // Copy rather than move — safer, keeps original PHP temp file intact
        if (!copy($realPath, $fullPath)) {
            Log::error('[OCR] Failed to copy file to temp dir', [
                'from' => $realPath,
                'to'   => $fullPath,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to store file for processing.',
            ], 422);
        }

        Log::info('[OCR] Stored temp file', [
            'full_path' => $fullPath,
            'exists'    => file_exists($fullPath),
            'readable'  => is_readable($fullPath),
            'size'      => file_exists($fullPath) ? filesize($fullPath) : 'N/A',
        ]);

        try {
            $ocrPath = $fullPath;

            if ($mimeType === 'application/pdf') {
                Log::info('[OCR] PDF detected — attempting image conversion');
                $imagePath = $this->convertPdfToImage($fullPath);

                if (!$imagePath) {
                    Log::warning('[OCR] PDF conversion failed — Imagick unavailable or failed');
                    return response()->json([
                        'success' => false,
                        'message' => 'Could not process PDF. Please upload an image instead.',
                    ], 422);
                }

                Log::info('[OCR] PDF converted to image', ['image_path' => $imagePath]);
                $ocrPath = $imagePath;
            }

            // Check Tesseract binary is available
            $tesseractBin = trim(shell_exec('which tesseract 2>/dev/null') ?? '');
            // Fallback to Homebrew Apple Silicon path if not in PATH
            if (empty($tesseractBin)) {
                $tesseractBin = '/opt/homebrew/bin/tesseract';
            }

            Log::info('[OCR] Tesseract binary', [
                'path'    => $tesseractBin,
                'exists'  => file_exists($tesseractBin),
                'version' => trim(shell_exec($tesseractBin . ' --version 2>&1 | head -1') ?? ''),
            ]);

            Log::info('[OCR] About to run Tesseract', [
                'ocr_path' => $ocrPath,
                'exists'   => file_exists($ocrPath),
                'readable' => is_readable($ocrPath),
                'size'     => file_exists($ocrPath) ? filesize($ocrPath) : 'N/A',
            ]);

            $ocr = new TesseractOCR($ocrPath);
            $ocr->executable($tesseractBin);
            $ocr->lang('eng');
            // Force 300 DPI via config option — avoids "Estimating resolution" empty output on low-DPI images
            $ocr->config('user_defined_dpi', '300');

            $rawText = $ocr->run();

            Log::info('[OCR] Tesseract completed', [
                'raw_text_length' => strlen($rawText),
                'raw_text_preview' => substr($rawText, 0, 200),
            ]);

            $amount = $this->ocrService->extractAmount($rawText);
            $date   = $this->ocrService->extractDate($rawText);

            Log::info('[OCR] Parsed results', [
                'amount' => $amount,
                'date'   => $date,
            ]);

            // Clean up
            @unlink($fullPath);
            if (isset($imagePath) && $imagePath !== $fullPath) {
                @unlink($imagePath);
            }

            return response()->json([
                'success'  => true,
                'raw_text' => $rawText,
                'amount'   => $amount,
                'date'     => $date,
            ]);

        } catch (\Exception $e) {
            Log::error('[OCR] Exception caught', [
                'message' => $e->getMessage(),
                'class'   => get_class($e),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTraceAsString(),
            ]);

            @unlink($fullPath);
            if (isset($imagePath) && $imagePath !== $fullPath) {
                @unlink($imagePath);
            }

            return response()->json([
                'success' => false,
                'message' => 'OCR processing failed. You can still fill in the details manually.',
                'error'   => config('app.debug') ? $e->getMessage() : null,
            ], 422);
        }
    }

    private function convertPdfToImage(string $pdfPath): ?string
    {
        if (!extension_loaded('imagick') || !class_exists('\Imagick')) {
            Log::warning('[OCR] Imagick extension not available');
            return null;
        }

        try {
            /** @var \Imagick $imagick */
            $imagick = new \Imagick();
            $imagick->setResolution(300, 300);
            $imagick->readImage($pdfPath . '[0]');
            $imagick->setImageFormat('png');
            $imagick->mergeImageLayers(\Imagick::LAYERMETHOD_FLATTEN);

            $outputPath = $pdfPath . '_page1.png';
            $imagick->writeImage($outputPath);
            $imagick->clear();
            $imagick->destroy();

            return $outputPath;
        } catch (\Exception $e) {
            Log::error('[OCR] Imagick conversion failed', ['message' => $e->getMessage()]);
            return null;
        }
    }
}