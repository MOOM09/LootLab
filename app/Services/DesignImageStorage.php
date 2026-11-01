<?php

class DesignImageStorage
{
    private string $directory;

    public function __construct()
    {
        $this->directory =
            dirname(__DIR__, 2) . '/storage/custom-designs';
    }

    public function save(string $base64): string
    {
        if (strlen($base64) > 28 * 1024 * 1024) {
            throw new RuntimeException('حجم الصورة الناتجة كبير جدًا.');
        }

        $binary = base64_decode($base64, true);

        if (
            $binary === false ||
            $binary === '' ||
            strlen($binary) > 20 * 1024 * 1024
        ) {
            throw new RuntimeException('بيانات الصورة الناتجة غير صالحة.');
        }

        $dimensions = @getimagesizefromstring($binary);

        if (
            $dimensions === false ||
            $dimensions[2] !== IMAGETYPE_PNG ||
            max($dimensions[0], $dimensions[1]) > 4096 ||
            $dimensions[0] * $dimensions[1] > 16000000
        ) {
            throw new RuntimeException('صيغة الصورة أو أبعادها غير صالحة.');
        }

        if (
            !is_dir($this->directory) &&
            !@mkdir($this->directory, 0750, true) &&
            !is_dir($this->directory)
        ) {
            throw new RuntimeException('تعذّر تجهيز مجلد حفظ التصاميم.');
        }

        $filename = bin2hex(random_bytes(16)) . '.png';
        $path = $this->pathFor($filename);

        $written = @file_put_contents($path, $binary, LOCK_EX);

        if ($written !== strlen($binary)) {
            if (is_file($path)) {
                @unlink($path);
            }

            throw new RuntimeException('تعذّر حفظ صورة التصميم.');
        }

        return $filename;
    }

    public function pathFor(string $filename): string
    {
        if (!preg_match('/\A[a-f0-9]{32}\.png\z/', $filename)) {
            throw new RuntimeException('اسم ملف التصميم غير صالح.');
        }

        return $this->directory . '/' . $filename;
    }

    public function delete(string $filename): void
    {
        $path = $this->pathFor($filename);

        if (is_file($path) && !@unlink($path)) {
            error_log('Could not remove design file: ' . $filename);
        }
    }
}