<?php

require_once dirname(__DIR__) . '/Services/AiService.php';
require_once dirname(__DIR__) . '/Services/AiDatabase.php';
require_once dirname(__DIR__) . '/Models/AiConversation.php';
require_once dirname(__DIR__) . '/Core/Controller.php';
class AiController extends Controller
{
    public function index(): void
    {
        $userId = 1;

        $isStream =
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['action'] ?? '') === 'send'
    && ($_POST['stream'] ?? '') === '1';

$emit = static function (array $event): void {
    echo json_encode(
        $event,
        JSON_UNESCAPED_UNICODE
        | JSON_INVALID_UTF8_SUBSTITUTE
        | JSON_THROW_ON_ERROR
    ) . "\n";

    flush();
};

if ($isStream) {
    ini_set('zlib.output_compression', '0');

    while (ob_get_level() > 0) {
        if (!ob_end_clean()) {
            break;
        }
    }

    header('Content-Type: application/x-ndjson; charset=utf-8');
    header('Cache-Control: no-cache, no-transform');
    header('X-Accel-Buffering: no');
}

$_SESSION['ai_csrf'] ??= bin2hex(random_bytes(32));
$_SESSION['ai_raw_replies'] ??= [];

$error = '';
$message = '';
$messages = [];
$conversations = [];
$conversationId = null;
$model = null;



try {
    $database = new AiDatabase();
    $model = new AiConversation($database->getConnection());
} catch (Throwable $exception) {
    $error = 'تعذّر الاتصال بقاعدة البيانات.';
    http_response_code(503);
}

if ($model !== null) {
    $isPost = $_SERVER['REQUEST_METHOD'] === 'POST';

    // نأخذ رقم المحادثة من الفورم أو الرابط.
    $requestedId = $isPost
        ? ($_POST['conversation_id'] ?? '')
        : ($_GET['conversation_id'] ?? '');

    try {
        if ($requestedId !== '') {
            $validatedId = is_string($requestedId)
                ? filter_var(
                    $requestedId,
                    FILTER_VALIDATE_INT,
                    ['options' => ['min_range' => 1]]
                )
                : false;

            if ($validatedId === false) {
                throw new RuntimeException('رقم المحادثة غير صالح.');
            }

            $conversationId = $validatedId;

            // الدالة تتحقق أيضًا إن المحادثة لنفس المستخدم.
            $messages = $model->getMessages(
                $conversationId,
                $userId
            );
        }

        if ($isPost) {
            $token = $_POST['csrf_token'] ?? '';

            if (
                !is_string($token)
                || !hash_equals($_SESSION['ai_csrf'], $token)
            ) {
                throw new RuntimeException(
                    'الطلب غير صالح. حدّث الصفحة.'
                );
            }

            $action = $_POST['action'] ?? 'send';

            if ($action === 'new_chat') {
                // نفتح صفحة فارغة، بدون حذف المحادثة القديمة.
                header('Location: ai.php');
                exit;
            }
if ($action === 'rename') {
    if ($conversationId === null) {
        throw new RuntimeException('اختار محادثة أولًا.');
    }

    $title = $_POST['title'] ?? '';

    if (!is_string($title)) {
        throw new RuntimeException('العنوان غير صالح.');
    }

    $model->rename($conversationId, $userId, $title);

    header('Location: ai.php?conversation_id=' . $conversationId);
    exit;
}

if ($action === 'delete') {
    if ($conversationId === null) {
        throw new RuntimeException('اختار محادثة أولًا.');
    }

    $model->delete($conversationId, $userId);

    // نمسح الردود المؤقتة التابعة للمحادثة المحذوفة.
    foreach ($messages as $entry) {
        unset($_SESSION['ai_raw_replies'][$entry['id']]);
    }

    header('Location: ai.php');
    exit;
}
            if ($action !== 'send') {
                throw new RuntimeException('العملية غير صالحة.');
            }

            $input = $_POST['message'] ?? '';

            if (!is_string($input)) {
                throw new RuntimeException('الرسالة غير صالحة.');
            }

            $message = trim($input);

            if ($message === '' || mb_strlen($message) > 1500) {
                throw new RuntimeException(
                    'اكتب رسالة من 1 إلى 1500 حرف.'
                );
            }

            // تحويل آخر 20 رسالة محفوظة لصيغة Gemini.
            $history = [];

            foreach (array_slice($messages, -20) as $entry) {
                $cachedReply = $_SESSION['ai_raw_replies']
                    [$entry['id']] ?? null;

                if (
                    $entry['role'] === 'assistant'
                    && is_array($cachedReply)
                ) {
                    // نحافظ على رد النموذج الكامل وتوقيعه في الجلسة.
                    $history[] = $cachedReply;
                } else {
                    $history[] = [
                        'role' => $entry['role'] === 'user'
                            ? 'user'
                            : 'model',
                        'parts' => [
                            ['text' => $entry['content']]
                        ]
                    ];
                }
            }

           $catalog = new AiProductCatalog();
$products = $catalog->getProducts();

$ai = new AiService($_ENV['GEMINI_API_KEY']);
$onText = $isStream
    ? static function (string $text) use ($emit): void {
        $emit([
            'type' => 'delta',
            'text' => $text,
        ]);
    }
    : null;

$response = $ai->sendMessage(
    $message,
    $history,
    $products,
    $onText
);

            if (isset($response['error'])) {
                throw new RuntimeException(
                    'خدمة الذكاء الاصطناعي غير متاحة حاليًا.'
                );
            }

            $modelContent =
                $response['candidates'][0]['content'] ?? [];

            $reply = '';

            foreach ($modelContent['parts'] ?? [] as $part) {
                if (isset($part['text']) && empty($part['thought'])) {
                    $reply .= $part['text'];
                }
            }

            if (trim($reply) === '') {
                throw new RuntimeException(
                    'لم يصل جواب نصّي. جرّب سؤالًا آخر.'
                );
            }

            // حفظ رسالة المستخدم والرد معًا.
            $conversationId = $model->saveExchange(
                $userId,
                $conversationId,
                $message,
                $reply
            );

            $savedMessages = $model->getMessages(
                $conversationId,
                $userId
            );

            $lastMessage = end($savedMessages);

            if ($lastMessage !== false) {
                $_SESSION['ai_raw_replies']
                    [$lastMessage['id']] = $modelContent;

                // تحديد حجم الذاكرة المؤقتة.
                $_SESSION['ai_raw_replies'] = array_slice(
                    $_SESSION['ai_raw_replies'],
                    -100,
                    null,
                    true
                );
            }

            if ($isStream) {
    $emit([
        'type' => 'done',
        'conversation_id' => $conversationId,
    ]);

    exit;
}


            // Redirect حتى Refresh ما يعيد إرسال الرسالة.
            header(
                'Location: ai.php?conversation_id='
                . $conversationId
            );
            exit;
        }
    }
   catch (RuntimeException $exception) {
    $error = $exception instanceof PDOException
        ? 'تعذّر حفظ المحادثة. الرد الظاهر لم يُحفظ.'
        : $exception->getMessage();

    if ($isStream) {
        $emit([
            'type' => 'error',
            'message' => $error,
        ]);

        exit;
    }
} catch (Throwable $exception) {
    $error = 'حدث خطأ أثناء معالجة الرسالة.';

    if ($isStream) {
        $emit([
            'type' => 'error',
            'message' => $error,
        ]);

        exit;
    }
}

    try {
        $conversations = $model->getAll($userId);
    } catch (Throwable $exception) {
        $error = 'تعذّر تحميل قائمة المحادثات.';
    }
}
if ($isStream) {
    $emit([
        'type' => 'error',
        'message' => $error ?: 'تعذّرت معالجة الرسالة.',
    ]);

    exit;
}

        require dirname(__DIR__) . '/Views/ai/chat.php';
    }
}

function escapeHtml(string $value): string
{
    return htmlspecialchars(
        $value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}
require_once dirname(__DIR__) . '/Services/AiProductCatalog.php';