<?php

namespace App\Services;

use Illuminate\Support\Str;

class SlipOcrService
{
    /**
     * ตรวจสอบสลิปแบบ 3 ชั้น
     * Level 1: QR Code Detector แบบไม่พึ่ง API ภายนอก
     * Level 2: OCR ด้วย Tesseract
     * Level 3: Admin ตรวจสอบเองสำหรับเคสที่ระบบยังไม่มั่นใจ
     */
    public function analyze(string $absolutePath, ?float $expectedAmount = null): array
    {
        $qrResult = $this->detectQrCode($absolutePath);

        if ($qrResult['detected']) {
            return [
                'status' => 'verified_by_qr',
                'score' => max(3, (int) $qrResult['score']),
                'text' => '',
                'engine' => 'qr_detector',
                'note' => 'ตรวจพบรูปแบบ QR Code ในสลิป ระบบจัดเป็นสลิปที่มีความน่าเชื่อถือ และส่งให้แอดมินตรวจยืนยันขั้นสุดท้าย',
                'qr_detected' => true,
            ];
        }

        $text = '';
        $engine = 'not_installed';
        $note = null;

        try {
            if (class_exists(\thiagoalessio\TesseractOCR\TesseractOCR::class) && is_file($absolutePath)) {
                $engine = 'tesseract';

                $ocr = new \thiagoalessio\TesseractOCR\TesseractOCR($absolutePath);
                $tesseractPath = config('services.tesseract.path') ?: env('TESSERACT_PATH');
                if ($tesseractPath) {
                    $ocr->executable($tesseractPath);
                }

                $text = $ocr->lang('tha', 'eng')->run();
            }
        } catch (\Throwable $e) {
            $engine = 'tesseract_error';
            $note = 'OCR อ่านรูปภาพไม่สำเร็จ: ' . $e->getMessage();
        }

        $normalized = Str::lower(preg_replace('/\s+/u', ' ', $text ?: ''));
        $keywords = [
            'ธนาคาร','โอน','รายการโอน','โอนเงิน','สำเร็จ','ยอดเงิน','จำนวนเงิน','เลขอ้างอิง','พร้อมเพย์','promptpay',
            'transfer','transaction','successful','success','amount','reference','ref','baht','kbank','kasikorn','scb','krungthai','bangkok bank',
        ];

        $score = 0;
        foreach ($keywords as $word) {
            if ($normalized !== '' && Str::contains($normalized, Str::lower($word))) {
                $score++;
            }
        }

        if ($engine === 'not_installed') {
            return [
                'status' => 'needs_review',
                'score' => $score,
                'text' => $text,
                'engine' => $engine,
                'note' => 'ยังไม่ได้ติดตั้ง Tesseract OCR ระบบจึงส่งให้แอดมินตรวจสอบเอง',
                'qr_detected' => false,
            ];
        }

        if ($engine === 'tesseract_error') {
            return [
                'status' => 'invalid',
                'score' => 0,
                'text' => '',
                'engine' => $engine,
                'note' => $note ?: 'OCR อ่านรูปภาพไม่สำเร็จ และไม่พบ QR Code จึงถือว่าไม่คล้ายสลิป',
                'qr_detected' => false,
            ];
        }

        $status = $score >= 3 ? 'verified_by_ocr' : ($score >= 1 ? 'needs_review' : 'invalid');

        return [
            'status' => $status,
            'score' => $score,
            'text' => Str::limit($text, 5000, ''),
            'engine' => $engine,
            'note' => match ($status) {
                'verified_by_ocr' => 'OCR พบคำสำคัญที่คล้ายสลิปโอนเงิน ระบบส่งต่อให้แอดมินตรวจยืนยันขั้นสุดท้าย',
                'needs_review' => 'OCR อ่านได้บางส่วน แต่คะแนนยังไม่พอ ระบบส่งให้แอดมินตรวจเอง',
                default => 'ไม่พบ QR Code และ OCR ไม่พบข้อมูลที่คล้ายสลิปโอนเงิน',
            },
            'qr_detected' => false,
        ];
    }

    /**
     * QR Detector แบบ Lightweight ใช้ GD ตรวจลักษณะภาพ QR เบื้องต้น
     * ไม่ได้อ่านค่าข้างใน QR แต่ตรวจว่ามี pattern ที่คล้าย QR หรือไม่
     */
    private function detectQrCode(string $absolutePath): array
    {
        if (!extension_loaded('gd') || !is_file($absolutePath)) {
            return ['detected' => false, 'score' => 0, 'note' => 'GD extension ไม่พร้อมใช้งาน'];
        }

        $raw = @file_get_contents($absolutePath);
        if (!$raw) {
            return ['detected' => false, 'score' => 0, 'note' => 'อ่านไฟล์รูปไม่ได้'];
        }

        $image = @imagecreatefromstring($raw);
        if (!$image) {
            return ['detected' => false, 'score' => 0, 'note' => 'เปิดรูปด้วย GD ไม่ได้'];
        }

        $width = imagesx($image);
        $height = imagesy($image);
        if ($width < 80 || $height < 80) {
            imagedestroy($image);
            return ['detected' => false, 'score' => 0, 'note' => 'รูปเล็กเกินไป'];
        }

        // ย่อภาพเพื่อให้ประมวลผลเร็วและเสถียรขึ้น
        $sampleSize = 160;
        $sample = imagecreatetruecolor($sampleSize, $sampleSize);
        imagecopyresampled($sample, $image, 0, 0, 0, 0, $sampleSize, $sampleSize, $width, $height);
        imagedestroy($image);

        $bestScore = 0;
        $windowSizes = [40, 52, 64, 76];

        foreach ($windowSizes as $window) {
            $step = max(8, (int) floor($window / 3));
            for ($y = 0; $y <= $sampleSize - $window; $y += $step) {
                for ($x = 0; $x <= $sampleSize - $window; $x += $step) {
                    $score = $this->scoreQrLikeWindow($sample, $x, $y, $window);
                    if ($score > $bestScore) {
                        $bestScore = $score;
                    }
                    if ($bestScore >= 6) {
                        imagedestroy($sample);
                        return ['detected' => true, 'score' => $bestScore, 'note' => 'พบ pattern ที่คล้าย QR Code'];
                    }
                }
            }
        }

        imagedestroy($sample);
        return ['detected' => false, 'score' => $bestScore, 'note' => 'ไม่พบ pattern QR Code'];
    }

    private function scoreQrLikeWindow($image, int $startX, int $startY, int $size): int
    {
        $black = 0;
        $white = 0;
        $transitions = 0;
        $rowsChecked = 0;
        $colsChecked = 0;

        $grid = 21; // QR มาตรฐานขั้นต่ำเป็น grid 21x21
        $cell = max(1, (int) floor($size / $grid));
        $lastRowValue = null;
        $lastColValue = null;

        for ($i = 0; $i < $grid; $i++) {
            $rowTransitions = 0;
            $colTransitions = 0;
            $lastRowValue = null;
            $lastColValue = null;

            for ($j = 0; $j < $grid; $j++) {
                $px = min($startX + ($j * $cell) + (int) floor($cell / 2), imagesx($image) - 1);
                $py = min($startY + ($i * $cell) + (int) floor($cell / 2), imagesy($image) - 1);
                $rowValue = $this->isDarkPixel($image, $px, $py);

                $cx = min($startX + ($i * $cell) + (int) floor($cell / 2), imagesx($image) - 1);
                $cy = min($startY + ($j * $cell) + (int) floor($cell / 2), imagesy($image) - 1);
                $colValue = $this->isDarkPixel($image, $cx, $cy);

                $rowValue ? $black++ : $white++;
                $colValue ? $black++ : $white++;

                if ($lastRowValue !== null && $lastRowValue !== $rowValue) $rowTransitions++;
                if ($lastColValue !== null && $lastColValue !== $colValue) $colTransitions++;
                $lastRowValue = $rowValue;
                $lastColValue = $colValue;
            }

            if ($rowTransitions >= 6) $rowsChecked++;
            if ($colTransitions >= 6) $colsChecked++;
            $transitions += $rowTransitions + $colTransitions;
        }

        $total = max(1, $black + $white);
        $blackRatio = $black / $total;

        $score = 0;
        if ($blackRatio >= 0.18 && $blackRatio <= 0.65) $score += 2;
        if ($rowsChecked >= 8) $score += 2;
        if ($colsChecked >= 8) $score += 2;
        if ($transitions >= 180) $score += 1;
        if ($this->hasFinderLikeCorners($image, $startX, $startY, $size)) $score += 2;

        return $score;
    }

    private function isDarkPixel($image, int $x, int $y): bool
    {
        $rgb = imagecolorat($image, $x, $y);
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;
        $brightness = (0.299 * $r) + (0.587 * $g) + (0.114 * $b);
        return $brightness < 145;
    }

    private function hasFinderLikeCorners($image, int $startX, int $startY, int $size): bool
    {
        $cornerSize = max(8, (int) floor($size * 0.22));
        $corners = [
            [$startX, $startY],
            [$startX + $size - $cornerSize, $startY],
            [$startX, $startY + $size - $cornerSize],
        ];

        $matches = 0;
        foreach ($corners as [$x, $y]) {
            $darkOuter = 0;
            $lightMiddle = 0;
            $darkCenter = 0;
            $checks = 0;

            for ($iy = 0; $iy < $cornerSize; $iy += 2) {
                for ($ix = 0; $ix < $cornerSize; $ix += 2) {
                    $px = min($x + $ix, imagesx($image) - 1);
                    $py = min($y + $iy, imagesy($image) - 1);
                    $dark = $this->isDarkPixel($image, $px, $py);
                    $rx = $ix / max(1, $cornerSize);
                    $ry = $iy / max(1, $cornerSize);
                    $inMiddle = $rx > 0.18 && $rx < 0.82 && $ry > 0.18 && $ry < 0.82;
                    $inCenter = $rx > 0.34 && $rx < 0.66 && $ry > 0.34 && $ry < 0.66;

                    if (!$inMiddle && $dark) $darkOuter++;
                    if ($inMiddle && !$inCenter && !$dark) $lightMiddle++;
                    if ($inCenter && $dark) $darkCenter++;
                    $checks++;
                }
            }

            if (($darkOuter + $lightMiddle + $darkCenter) > ($checks * 0.38)) {
                $matches++;
            }
        }

        return $matches >= 2;
    }
}
