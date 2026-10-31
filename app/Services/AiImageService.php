<?php

class AiImageService
{
    private string $generateUrl;

public function __construct(string $generateUrl)
{
    $generateUrl = trim($generateUrl);

    if (
        filter_var($generateUrl, FILTER_VALIDATE_URL) === false
        || !in_array(
            strtolower((string) parse_url($generateUrl, PHP_URL_SCHEME)),
            ['http', 'https'],
            true
        )
    ) {
        throw new RuntimeException('رابط PhotoMaker غير مضبوط.');
    }

    $this->generateUrl = $generateUrl;
}
    public function generate(
        string $imagePath,
        string $prompt,
        string $negativePrompt,
        ?string $referenceImagePath = null
    ): string {
        if (trim($prompt) === '' || strlen($prompt) > 1500 || strlen($negativePrompt) > 1500
            || preg_match_all('/\bimg\b/', $prompt) !== 1) {
            throw new RuntimeException('وصف الصورة غير صالح؛ لازم يحتوي img مرة واحدة.');
        }

        error_log('PHOTOMAKER PROMPT: ' . $prompt);
        error_log('PHOTOMAKER NEGATIVE: ' . $negativePrompt);
        $fields = [
            'image' => $this->imageFile($imagePath, 'face'),
            'mode' => $referenceImagePath === null ? 'text' : 'reference',
            'prompt' => $prompt,
            'negative_prompt' => $negativePrompt,
        ];
        if ($referenceImagePath !== null) {
            $fields['reference_image'] = $this->imageFile($referenceImagePath, 'reference');
        }
        $curl = curl_init($this->generateUrl);
        if ($curl === false) {
            throw new RuntimeException('تعذّر تجهيز الاتصال.');
        }
        try {
            curl_setopt_array($curl, [
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_TIMEOUT => 600,
                CURLOPT_POSTFIELDS => $fields,
            ]);
            $response = curl_exec($curl);
            if ($response === false) {
                throw new RuntimeException('تعذّر الاتصال بـPhotoMaker أو انتهت مهلة الانتظار.');
            }
            $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $contentType = curl_getinfo($curl, CURLINFO_CONTENT_TYPE);
        } finally {
            curl_close($curl);
        }
        if (in_array($status, [400, 413], true)) {
            $details = json_decode($response, true);
            $reason = is_array($details) ? ($details['error'] ?? '') : '';
            throw new RuntimeException('PhotoMaker رفض الطلب: ' . (is_string($reason) && $reason !== '' ? $reason : 'طلب غير صالح.'));
        }
        if ($status === 503) {
            throw new RuntimeException('مولّد الصور مشغول، جرّب بعد قليل.');
        }
        if ($status !== 200 || !str_starts_with((string) $contentType, 'image/png')
            || !str_starts_with($response, "\x89PNG\r\n\x1a\n")) {
            throw new RuntimeException('تعذّر توليد الصورة. رمز الاستجابة: ' . $status);
        }
        return base64_encode($response);
    }

    private function imageFile(string $path, string $name): CURLFile
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new RuntimeException('تعذّر قراءة الصورة.');
        }
        $size = filesize($path);
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($path);
        $dimensions = @getimagesize($path);
        if (!$size || $size > 5 * 1024 * 1024 || !in_array($mime, ['image/jpeg', 'image/png'], true)
            || $dimensions === false || max($dimensions[0], $dimensions[1]) > 4096
            || $dimensions[0] * $dimensions[1] > 12000000) {
            throw new RuntimeException('استخدم صورة JPG أو PNG حتى 5MB وبأبعاد مسموحة.');
        }
        return new CURLFile($path, $mime, $name . ($mime === 'image/png' ? '.png' : '.jpg'));
    }
}
