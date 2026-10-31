
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
(() => {
    'use strict';

    const chatForm = document.getElementById('chat-form');
    const sendButton = document.getElementById('send-button');
    const messageInput = document.getElementById('message');

    if (!chatForm || !sendButton || !messageInput) {
        return;
    }

    let sending = false;

    function setSending(value) {
        sending = value;
        sendButton.disabled = value;
        messageInput.readOnly = value;
        sendButton.textContent = value ? 'جاري الرد...' : 'إرسال';
        chatForm.setAttribute('aria-busy', String(value));
    }

    function addMessage(role, text) {
        const article = document.createElement('article');
        article.className = 'chat-message ' + (
            role === 'user' ? 'user-message' : 'assistant-message'
        );

        const heading = document.createElement('strong');
        heading.textContent = role === 'user' ? 'أنت' : 'Loot Hub AI';

        const content = document.createElement('div');
        content.className = 'message-text';
        content.dir = 'auto';
        content.textContent = text;

        article.append(heading, content);
        chatForm.before(article);

        return { article, content };
    }

    function showError(message) {
        const alert = document.createElement('div');
        alert.className = 'error';
        alert.setAttribute('role', 'alert');
        alert.textContent = message;
        chatForm.before(alert);
    }

    async function refreshHistory(url) {
        try {
            const response = await fetch(url, {
                cache: 'no-store'
            });

            if (!response.ok) {
                return;
            }

            const html = await response.text();
            const page = new DOMParser().parseFromString(html, 'text/html');
            const newAside = page.querySelector('aside');
            const currentAside = document.querySelector('aside');

            if (newAside && currentAside) {
                currentAside.replaceWith(
                    document.importNode(newAside, true)
                );
            }
        } catch {
            // فشل تحديث القائمة لا يعني فشل حفظ الرد.
        }
    }

    messageInput.addEventListener('keydown', (event) => {
        if (
            event.key !== 'Enter' ||
            event.shiftKey ||
            event.isComposing
        ) {
            return;
        }

        event.preventDefault();

        if (!sending) {
            chatForm.requestSubmit();
        }
    });

    chatForm.addEventListener('submit', async (event) => {
        event.preventDefault();

        if (sending || !chatForm.reportValidity()) {
            return;
        }

        const message = messageInput.value.trim();

        if (!message) {
            return;
        }

        const formData = new FormData(chatForm);
        formData.set('message', message);
        formData.set('stream', '1');

        setSending(true);
        document.querySelectorAll('main > .error').forEach(
            (element) => element.remove()
        );

        const userMessage = addMessage('user', message);
        const assistant = addMessage('assistant', 'بانتظار الرد...');

        userMessage.article.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });

        let reply = '';
        let completed = false;
        let conversationId = null;
        let displayedReply = '';
        let animationFrame = null;
        let lastPaint = 0;
        let resolveTyping = null;

        function animateReply(time) {
            const followBottom =
                window.innerHeight + window.scrollY >=
                document.documentElement.scrollHeight - 160;

            if (time - lastPaint >= 25) {
                const remaining = Array.from(
                    reply.slice(displayedReply.length)
                );

                if (remaining.length > 0) {
                    displayedReply += remaining.slice(0, 2).join('');
                    assistant.content.textContent = displayedReply;

                    if (followBottom) {
                        window.scrollTo({
                            top: document.documentElement.scrollHeight
                        });
                    }
                }

                lastPaint = time;
            }

            if (displayedReply !== reply) {
                animationFrame = requestAnimationFrame(animateReply);
            } else {
                animationFrame = null;

                if (resolveTyping !== null) {
                    const resolve = resolveTyping;
                    resolveTyping = null;
                    resolve();
                }
            }
        }

        function startTyping() {
            if (animationFrame === null && displayedReply !== reply) {
                animationFrame = requestAnimationFrame(animateReply);
            }
        }

        function waitForTyping() {
            if (displayedReply === reply) {
                return Promise.resolve();
            }

            return new Promise((resolve) => {
                resolveTyping = resolve;
                startTyping();
            });
        }

        function stopTyping() {
            if (animationFrame !== null) {
                cancelAnimationFrame(animationFrame);
                animationFrame = null;
            }
        }

        function handleLine(line) {
            if (!line.trim()) {
                return;
            }

            const item = JSON.parse(line);

            if (item.type === 'delta') {
                if (typeof item.text !== 'string') {
                    throw new Error('وصل جزء غير صالح من الرد.');
                }

                reply += item.text;
                startTyping();
            } else if (item.type === 'error') {
                throw new Error(item.message || 'تعذّر إكمال الرد.');
            } else if (item.type === 'done') {
                const id = Number(item.conversation_id);

                if (!Number.isSafeInteger(id) || id < 1) {
                    throw new Error('وصل رقم محادثة غير صالح.');
                }

                completed = true;
                conversationId = id;
            }
        }

        try {
           const response = await fetch(chatForm.getAttribute('action'), {
                method: 'POST',
                body: formData
            });

            if (
                !response.ok ||
                !response.headers.get('content-type')
                    ?.includes('application/x-ndjson') ||
                !response.body
            ) {
                throw new Error(
                    'تعذّر بدء الرد. تأكد من تسجيل الدخول وحدّث الصفحة.'
                );
            }

            const reader = response.body.getReader();
            const decoder = new TextDecoder();
            let buffer = '';
            let reachedEnd = false;

            try {
                while (true) {
                    const { value, done } = await reader.read();

                    if (done) {
                        reachedEnd = true;
                        buffer += decoder.decode();
                        break;
                    }

                    buffer += decoder.decode(value, { stream: true });

                    let position;

                    while ((position = buffer.indexOf('\n')) !== -1) {
                        const line = buffer.slice(0, position);
                        buffer = buffer.slice(position + 1);
                        handleLine(line);
                    }
                }

                if (buffer.trim()) {
                    handleLine(buffer);
                }
            } finally {
                if (!reachedEnd) {
                    try {
                        await reader.cancel();
                    } catch {
                        // الاتصال قد يكون مغلقًا بالفعل.
                    }
                }

                reader.releaseLock();
            }

            if (!completed) {
                throw new Error(
                    'انقطع الاتصال قبل تأكيد الحفظ. حدّث الصفحة للتحقق.'
                );
            }

            chatForm.elements.namedItem('conversation_id').value =
                conversationId;

            const url = new URL(window.location.href);
            url.searchParams.set('conversation_id', conversationId);
            window.history.replaceState(null, '', url);

            messageInput.value = '';

            // يظل الإرسال معطّلًا حتى تنتهي الكتابة المرئية.
            await waitForTyping();

            // تحديث القائمة مستقل عن إتاحة إرسال الرسالة التالية.
            void refreshHistory(url);
        } catch (error) {
            stopTyping();

            if (reply) {
                assistant.content.textContent = reply;
            } else {
                assistant.article.remove();
            }

            showError(
                error instanceof Error
                    ? error.message
                    : 'تعذّر استقبال الرد.'
            );
        } finally {
            stopTyping();
            setSending(false);
        }
    });

    window.addEventListener('load', () => {
        const messages = document.querySelectorAll('.chat-message');

        if (messages.length > 0) {
            messages[messages.length - 1].scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
})();
</script>
</body>
</html>