<?php

require_once dirname(__DIR__) . '/Services/AiImageService.php';
require_once dirname(__DIR__) . '/Services/AiService.php';

class AiImageController
{
    public function index(): void
    {
        $_SESSION['ai_image_csrf'] ??= bin2hex(random_bytes(32));
        $csrfToken = $_SESSION['ai_image_csrf'];
        $error = '';
        $generatedImage = '';
        $description = '';
        $subject = 'person';
        $mode = 'text';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                // PHP discards POST and FILES when post_max_size is exceeded.
                if ($_POST === [] && $_FILES === [] && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
                    throw new RuntimeException('حجم الطلب أكبر من المسموح في PHP. استخدم صورًا أصغر أو ارفع post_max_size إلى 16M.');
                }
                $token = $_POST['csrf_token'] ?? '';
                if (!is_string($token) || !hash_equals($csrfToken, $token)) {
                    throw new RuntimeException('حدّث الصفحة وحاول مرة ثانية.');
                }
                $modeInput = $_POST['mode'] ?? 'text';
                if (!is_string($modeInput) || !in_array($modeInput, ['text', 'reference'], true)) {
                    throw new RuntimeException('اختار طريقة تصميم متاحة.');
                }
                $mode = $modeInput;
                $input = $_POST['description'] ?? '';
                if (!is_string($input)) {
                    throw new RuntimeException('الوصف غير صالح.');
                }
                $description = trim($input);
                if (mb_strlen($description) > 1000 || ($mode === 'text' && $description === '')) {
                    throw new RuntimeException('اكتب وصفًا من 1 إلى 1000 حرف؛ بوضع الصورة المرجعية الوصف اختياري.');
                }
                $subjectInput = $_POST['subject'] ?? '';
                if (!is_string($subjectInput) || !in_array($subjectInput, ['man', 'woman', 'person'], true)) {
                    throw new RuntimeException('اختار نوع الشخص.');
                }
                $subject = $subjectInput;
                $path = $this->uploadedImage('image', 'صورتك');
                $referencePath = $mode === 'reference'
                    ? $this->uploadedImage('reference_image', 'الصورة المرجعية')
                    : null;
                $apiKey = $_ENV['GEMINI_API_KEY'] ?? '';
                if (!is_string($apiKey) || trim($apiKey) === '') {
                    throw new RuntimeException('مفتاح Gemini غير مضبوط.');
                }
                // Only the character/reference image goes to Gemini, not the face upload.
                $brief = $description !== '' ? $description
                    : 'Match the attached character image: clothing, pose, composition, background and visual style.';
                session_write_close();
                set_time_limit(720);
                $ai = new AiService($apiKey);
                $prepared = $ai->prepareImagePrompt($brief, $subject, $referencePath);
               $service = new AiImageService(
    $_ENV['PHOTOMAKER_GENERATE_URL'] ?? ''
);
                $generatedImage = $service->generate(
                    $path,
                    $prepared['prompt'],
                    $prepared['negative_prompt'],
                    $referencePath
                );
            } catch (RuntimeException $exception) {
                $error = $exception->getMessage();
            } catch (Throwable $exception) {
                error_log((string) $exception);
                $error = 'حدث خطأ أثناء توليد الصورة.';
            }
        }
        require dirname(__DIR__) . '/Views/ai/image.php';
    }

    private function uploadedImage(string $field, string $label): string
    {
        $file = $_FILES[$field] ?? null;
        if (!is_array($file) || !is_int($file['error'] ?? null) || $file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException($label . ': اختار صورة JPG أو PNG حتى 5MB ضمن حد الرفع المسموح.');
        }
        $path = $file['tmp_name'] ?? '';
        if (!is_string($path) || !is_uploaded_file($path)) {
            throw new RuntimeException($label . ': الملف المرفوع غير صالح.');
        }
        $size = filesize($path);
        if ($size === false || $size === 0 || $size > 5 * 1024 * 1024) {
            throw new RuntimeException($label . ': الحجم لازم يكون حتى 5MB.');
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($path);
        $dimensions = @getimagesize($path);
        if (!in_array($mime, ['image/jpeg', 'image/png'], true) || $dimensions === false) {
            throw new RuntimeException($label . ': ارفع صورة PNG أو JPG فقط.');
        }
        if (max($dimensions[0], $dimensions[1]) > 4096 || $dimensions[0] * $dimensions[1] > 12000000) {
            throw new RuntimeException($label . ': صغّر أبعاد الصورة وحاول مرة ثانية.');
        }
        return $path;
    }
}
