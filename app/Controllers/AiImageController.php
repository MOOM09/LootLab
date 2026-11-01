<?php

require_once dirname(__DIR__) . '/Services/AiImageService.php';
require_once dirname(__DIR__) . '/Services/AiService.php';
require_once dirname(__DIR__) . '/Models/CustomDesign.php';
require_once dirname(__DIR__) . '/Services/DesignImageStorage.php';
class AiImageController
{
 
private array $config;

public function __construct(array $config)
{
    $this->config = $config;
}


public function index(): void
    {
        $_SESSION['ai_image_csrf'] ??= bin2hex(random_bytes(32));
        $csrfToken = $_SESSION['ai_image_csrf'];
        $error = '';
        $generatedImage = '';
        $description = '';
        $subject = 'person';
        $mode = 'text';
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        $savedDesignId = null;

        // فتح تصميم محفوظ، مع التحقق من ملكيته.
if (
    ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET'
    && isset($_GET['design_id'])
) {
    try {
        $inputId = $_GET['design_id'];

        $designId = is_string($inputId)
            ? filter_var(
                $inputId,
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1]]
            )
            : false;

        if ($designId === false) {
            http_response_code(422);
            throw new RuntimeException('رقم التصميم غير صالح.');
        }

        $database = new Database($this->config['database']);
        $designModel = new CustomDesign($database->getConnection());
        $design = $designModel->findForUser($designId, $userId);

        if (
            $design === null
            || !is_string($design['result_image'])
        ) {
            http_response_code(404);
            throw new RuntimeException(
                'التصميم غير موجود أو لا يخص هذا المستخدم.'
            );
        }

        $storage = new DesignImageStorage();
        $imagePath = $storage->pathFor($design['result_image']);

        if (!is_file($imagePath) || !is_readable($imagePath)) {
            http_response_code(404);
            throw new RuntimeException('ملف التصميم غير موجود.');
        }

        $binary = file_get_contents($imagePath);

        if ($binary === false) {
            throw new RuntimeException('تعذّر قراءة صورة التصميم.');
        }

        $generatedImage = base64_encode($binary);
        $savedDesignId = $designId;
    } catch (RuntimeException $exception) {
        $error = $exception->getMessage();
    } catch (Throwable $exception) {
        error_log((string) $exception);
        http_response_code(500);
        $error = 'تعذّر تحميل التصميم المحفوظ.';
    }
}

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
                // إعادة فتح الجلسة بعد انتهاء التوليد.
if (
    session_status() !== PHP_SESSION_ACTIVE &&
    !session_start()
) {
    throw new RuntimeException(
        'تولّدت الصورة، لكن تعذّر فتح الجلسة لحفظها.'
    );
}

try {
    if (
        $userId < 1 ||
        (int) ($_SESSION['user_id'] ?? 0) !== $userId
    ) {
        throw new RuntimeException(
            'تغيّرت جلسة الدخول أثناء التوليد. لم تُحفظ الصورة؛ سجّل الدخول مجددًا.'
        );
    }

    $storage = new DesignImageStorage();
    $filename = null;

    try {
        $database = new Database($this->config['database']);
        $connection = $database->getConnection();

        $owner = (new User($connection))->findById($userId);

        if ($owner === null || (int) $owner['is_active'] !== 1) {
            throw new RuntimeException(
                'Account is unavailable or inactive.'
            );
        }

        $filename = $storage->save($generatedImage);

        $designModel = new CustomDesign($connection);

        $savedDesignId = $designModel->createCompleted(
            $userId,
            $filename
        );
    } catch (Throwable $exception) {
        if ($filename !== null) {
            $storage->delete($filename);
        }

        error_log((string) $exception);

        throw new RuntimeException(
            'تولّدت الصورة، لكن تعذّر حفظها بحسابك. يمكنك تنزيلها من النتيجة الظاهرة.'
        );
    }
} finally {
    session_write_close();
}

if ($savedDesignId !== null) {
    // الانتقال إلى GET حتى التحديث لا يعيد التوليد.
    while (ob_get_level() > 0) {
        if (!ob_end_clean()) {
            break;
        }
    }

    header(
        'Location: '
        . rtrim($this->config['app_url'], '/')
        . '/aiImage.php?design_id='
        . $savedDesignId,
        true,
        303
    );
    exit;
}

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
