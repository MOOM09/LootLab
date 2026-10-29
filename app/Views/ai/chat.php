
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>LootLab AI</title>

    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            padding: 24px 16px;
            background: #111827;
            color: #f9fafb;
            font-family: Arial, sans-serif;
        }

        .container {
            display: grid;
            grid-template-columns: 260px minmax(0, 1fr);
            gap: 24px;
            max-width: 1100px;
            margin: auto;
        }

        aside {
            padding: 18px;
            border-radius: 14px;
            background: #1f2937;
            align-self: start;
        }

        .history-link {
            display: block;
            padding: 12px;
            margin: 8px 0;
            border-radius: 8px;
            color: white;
            text-decoration: none;
            overflow-wrap: anywhere;
        }

        .history-link:hover,
        .history-link.active {
            background: #4c3d72;
        }

        .history-link small {
            display: block;
            margin-top: 6px;
            color: #cbd5e1;
        }

        textarea {
            width: 100%;
            min-height: 100px;
            margin: 12px 0;
            padding: 14px;
            border: 1px solid #4b5563;
            border-radius: 12px;
            background: #1f2937;
            color: white;
            font: inherit;
            resize: vertical;
        }

        button {
            padding: 12px 20px;
            border: 0;
            border-radius: 10px;
            background: #a78bfa;
            color: #111827;
            font: inherit;
            font-weight: bold;
            cursor: pointer;
        }

        button:disabled {
            opacity: .6;
            cursor: wait;
        }

        .chat-message {
            margin: 16px 0;
            padding: 18px;
            border-radius: 12px;
            overflow-wrap: anywhere;
        }

        .user-message {
            background: #37305a;
            margin-left: 24px;
        }

        .assistant-message {
            background: #1f2937;
            margin-right: 24px;
        }

        .message-text {
            margin-top: 10px;
            white-space: pre-wrap;
            line-height: 1.8;
        }

        .error {
            padding: 16px;
            margin: 16px 0;
            background: #7f1d1d;
            border-radius: 10px;
        }

        .note { color: #c4b5fd; }

        @media (max-width: 700px) {
            .container { grid-template-columns: 1fr; }
            aside { max-height: 280px; overflow-y: auto; }
        }

        .history-action {
    margin: 10px 0;
}

.history-action label {
    display: block;
    margin-bottom: 6px;
    font-size: 13px;
}

.history-action input[type="text"] {
    width: 100%;
    padding: 9px;
    margin-bottom: 8px;
    border: 1px solid #64748b;
    border-radius: 8px;
    background: #111827;
    color: white;
    font: inherit;
}

.history-action button {
    padding: 8px 12px;
    font-size: 13px;
}

.delete-button {
    background: #fca5a5;
}
    </style>
</head>

<body>
<div class="container">
    <aside>
        <h2>المحادثات السابقة</h2>

        <form method="POST" action="ai.php">
            <input type="hidden" name="action" value="new_chat">
            <input
                type="hidden"
                name="csrf_token"
                value="<?= escapeHtml($_SESSION['ai_csrf']) ?>"
            >
            <button type="submit">+ محادثة جديدة</button>
        </form>

        <?php foreach ($conversations as $conversation): ?>
            <a
                class="history-link <?= $conversationId === (int) $conversation['id'] ? 'active' : '' ?>"
                href="ai.php?conversation_id=<?= (int) $conversation['id'] ?>"
            >
                <?= escapeHtml($conversation['title']) ?>
                <small>
                    <?= escapeHtml($conversation['updated_at']) ?>
                </small>
            </a>

            <form method="POST" action="ai.php" class="history-action">
    <input type="hidden" name="action" value="rename">

    <input
        type="hidden"
        name="conversation_id"
        value="<?= (int) $conversation['id'] ?>"
    >

    <input
        type="hidden"
        name="csrf_token"
        value="<?= escapeHtml($_SESSION['ai_csrf']) ?>"
    >

    <label for="title-<?= (int) $conversation['id'] ?>">
        عنوان المحادثة
    </label>

    <input
        id="title-<?= (int) $conversation['id'] ?>"
        type="text"
        name="title"
        value="<?= escapeHtml($conversation['title']) ?>"
        maxlength="150"
        required
    >

    <button type="submit">حفظ العنوان</button>
</form>

<form
    method="POST"
    action="ai.php"
    class="history-action"
    onsubmit="return confirm('حذف المحادثة وكل رسائلها نهائيًا؟');"
>
    <input type="hidden" name="action" value="delete">

    <input
        type="hidden"
        name="conversation_id"
        value="<?= (int) $conversation['id'] ?>"
    >

    <input
        type="hidden"
        name="csrf_token"
        value="<?= escapeHtml($_SESSION['ai_csrf']) ?>"
    >

    <button type="submit" class="delete-button">
        حذف المحادثة
    </button>
</form>
        <?php endforeach; ?>

        <?php if ($conversations === []): ?>
            <p>لا توجد محادثات محفوظة بعد.</p>
        <?php endif; ?>
    </aside>

    <main>
        <h1>مساعد LootLab 🧪</h1>
        <p>احكيلي شو بتحب أو لمين بدك هدية.</p>
        <p class="note">
            تجربة محلية بالمستخدم رقم 1 ومنتجات وأسعار وهمية.
        </p>

        <?php if ($error !== ''): ?>
            <div class="error" role="alert">
                <?= escapeHtml($error) ?>
            </div>
        <?php endif; ?>

        <?php foreach ($messages as $entry): ?>
            <article
                class="chat-message <?= $entry['role'] === 'user' ? 'user-message' : 'assistant-message' ?>"
            >
                <strong>
                    <?= $entry['role'] === 'user' ? 'أنت' : 'LootLab AI' ?>
                </strong>

                <div class="message-text" dir="auto"><?= escapeHtml($entry['content']) ?></div>
            </article>
        <?php endforeach; ?>

        <form method="POST" action="ai.php" id="chat-form">
            <input type="hidden" name="action" value="send">

            <input
                type="hidden"
                name="conversation_id"
                value="<?= $conversationId !== null ? (int) $conversationId : '' ?>"
            >

            <input
                type="hidden"
                name="csrf_token"
                value="<?= escapeHtml($_SESSION['ai_csrf']) ?>"
            >

            <label for="message">رسالتك</label>

            <textarea
                id="message"
                name="message"
                dir="auto"
                maxlength="1500"
                placeholder="بدي هدية لصاحبي بحب ناروتو"
                required
            ><?= escapeHtml($message) ?></textarea>

            <button type="submit" id="send-button">
                إرسال
            </button>
        </form>
    </main>
</div>

<script>
document.getElementById('chat-form').addEventListener('submit', function () {
    const button = document.getElementById('send-button');
    button.disabled = true;
    button.textContent = 'جاري تجهيز الرد...';
});
</script>
</body>
</html>