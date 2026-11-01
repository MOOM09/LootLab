<?php
$pageTitle = 'LOOTE AI';
$userHideAiLink = true;
$userExtraCss = '/css/ai-studio.css';
require __DIR__ . '/../layouts/user-header.php';

// Translate UI labels only; never alter messages or AI-generated content.
$chatIsArabic = $userLanguage === 'ar';
$chatText = static fn (string $en, string $ar): string => $chatIsArabic ? $ar : $en;
?>
<div class="ai-chat-shell" dir="<?= $chatIsArabic ? 'rtl' : 'ltr' ?>"><div class="loote-chat-banner" dir="<?= $chatIsArabic ? 'rtl' : 'ltr' ?>"><div><span class="ai-kicker">YOUR SHOPPING SIDEKICK</span><h1>Meet LOOTE <span>✦</span></h1><p>Discover products, get inspired, and find your next favorite.</p></div><a class="ai-outline" href="<?= htmlspecialchars($userAppUrl . '/aiImage.php', ENT_QUOTES, 'UTF-8') ?>">✦ AI Image Studio</a></div>
<div class="container">
    <aside>
        <h2><?= $chatText('Previous Conversations', 'المحادثات السابقة') ?></h2>

        <form method="POST" action="ai.php">
            <input type="hidden" name="action" value="new_chat">
            <input
                type="hidden"
                name="csrf_token"
                value="<?= escapeHtml($_SESSION['ai_csrf']) ?>"
            >
            <button type="submit"><?= $chatText('+ New Conversation', '+ محادثة جديدة') ?></button>
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
        <?= $chatText('Conversation Title', 'عنوان المحادثة') ?>
    </label>

    <input
        id="title-<?= (int) $conversation['id'] ?>"
        type="text"
        name="title"
        value="<?= escapeHtml($conversation['title']) ?>"
        maxlength="150"
        required
    >

    <button type="submit"><?= $chatText('Save Title', 'حفظ العنوان') ?></button>
</form>

<form
    method="POST"
    action="ai.php"
    class="history-action"
    onsubmit="return confirm(<?= htmlspecialchars(json_encode($chatText('Permanently delete this conversation and all messages?', 'حذف المحادثة وكل رسائلها نهائيًا؟'), JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>);"
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
        <?= $chatText('Delete Conversation', 'حذف المحادثة') ?>
    </button>
</form>
        <?php endforeach; ?>

        <?php if ($conversations === []): ?>
            <p><?= $chatText('No saved conversations yet.', 'لا توجد محادثات محفوظة بعد.') ?></p>
        <?php endif; ?>
    </aside>

    <section class="loote-chat-main">
        <h1>LOOTE — Your AI shopping assistant ✦</h1>
        <p><?= $chatText('Tell me what you love or who you are shopping for.', 'احكيلي شو بتحب أو لمين بدك هدية.') ?></p>
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
                    <?= $entry['role'] === 'user' ? $chatText('You', 'أنت') : 'LOOTE AI' ?>
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

            <label for="message"><?= $chatText('Your Message', 'رسالتك') ?></label>

            <textarea
                id="message"
                name="message"
                dir="auto"
                maxlength="1500"
                placeholder="<?= htmlspecialchars($chatText('I need a gift for my friend who loves Naruto', 'بدي هدية لصاحبي بحب ناروتو'), ENT_QUOTES, 'UTF-8') ?>"
                required
            ><?= escapeHtml($message) ?></textarea>

            <button type="submit" id="send-button">
                <?= $chatText('Send', 'إرسال') ?>
            </button>
        </form>
    </section>
</div>
<script>
(() => {
    'use strict';

    const chatForm = document.getElementById('chat-form');
    const sendButton = document.getElementById('send-button');
    const messageInput = document.getElementById('message');

    // Chat-only strings are selected from the server's saved language preference.
    const chatCopy = <?= json_encode([
        'sending' => $chatText('Responding...', 'جاري الرد...'),
        'send' => $chatText('Send', 'إرسال'),
        'you' => $chatText('You', 'أنت'),
        'waiting' => $chatText('Waiting for a response...', 'بانتظار الرد...'),
        'invalidChunk' => $chatText('Invalid response chunk received.', 'وصل جزء غير صالح من الرد.'),
        'replyError' => $chatText('Could not complete the response.', 'تعذّر إكمال الرد.'),
        'invalidId' => $chatText('Invalid conversation ID received.', 'وصل رقم محادثة غير صالح.'),
        'startError' => $chatText('Could not start the response. Please sign in and refresh the page.', 'تعذّر بدء الرد. تأكد من تسجيل الدخول وحدّث الصفحة.'),
        'connectionError' => $chatText('Connection ended before saving was confirmed. Refresh to check.', 'انقطع الاتصال قبل تأكيد الحفظ. حدّث الصفحة للتحقق.'),
        'receiveError' => $chatText('Could not receive the response.', 'تعذّر استقبال الرد.'),
    ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

    if (!chatForm || !sendButton || !messageInput) {
        return;
    }

    let sending = false;

    function setSending(value) {
        sending = value;
        sendButton.disabled = value;
        messageInput.readOnly = value;
        sendButton.textContent = value ? chatCopy.sending : chatCopy.send;
        chatForm.setAttribute('aria-busy', String(value));
    }

    function addMessage(role, text) {
        const article = document.createElement('article');
        article.className = 'chat-message ' + (
            role === 'user' ? 'user-message' : 'assistant-message'
        );

        const heading = document.createElement('strong');
        heading.textContent = role === 'user' ? chatCopy.you : 'LOOTE AI';

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
        const assistant = addMessage('assistant', chatCopy.waiting);

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
                    throw new Error(chatCopy.invalidChunk);
                }

                reply += item.text;
                startTyping();
            } else if (item.type === 'error') {
                throw new Error(item.message || chatCopy.replyError);
            } else if (item.type === 'done') {
                const id = Number(item.conversation_id);

                if (!Number.isSafeInteger(id) || id < 1) {
                    throw new Error(chatCopy.invalidId);
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
                    chatCopy.startError
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
                    chatCopy.connectionError
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
                    : chatCopy.receiveError
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

</div>
<?php require __DIR__ . '/../layouts/user-footer.php'; ?>
