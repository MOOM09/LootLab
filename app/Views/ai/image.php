<?php
$escape = static fn (string $value): string => htmlspecialchars(
    $value,
    ENT_QUOTES | ENT_SUBSTITUTE,
    'UTF-8'
);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Loot Hub — تصميمك بالـAI</title>

    <style>
        :root {
            color-scheme: dark;
            --background: #111827;
            --panel: #1f2937;
            --text: #f9fafb;
            --muted: #cbd5e1;
            --border: #64748b;
            --accent: #a78bfa;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 30px 16px;
            background: var(--background);
            color: var(--text);
            font-family: Arial, sans-serif;
        }

        main {
            max-width: 1000px;
            margin-inline: auto;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 24px;
        }

        .panel {
            min-width: 0;
            padding: 24px;
            border-radius: 16px;
            background: var(--panel);
        }

        label {
            display: block;
            margin: 18px 0 10px;
        }

        input,
        select,
        textarea,
        button {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border);
            border-radius: 10px;
            font: inherit;
        }

        input,
        select,
        textarea {
            background: var(--background);
            color: var(--text);
        }

        textarea {
            min-height: 140px;
            resize: vertical;
            line-height: 1.7;
        }

        button {
            margin-top: 16px;
            border: 0;
            background: var(--accent);
            color: var(--background);
            font-weight: bold;
            cursor: pointer;
        }

        button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .button-secondary {
            border: 1px solid var(--border);
            background: transparent;
            color: var(--text);
        }

        .camera-actions {
            display: flex;
            gap: 12px;
        }

        .camera-actions button {
            flex: 1;
        }

        img {
            display: block;
            max-width: 100%;
            max-height: 450px;
            margin: 20px auto 0;
            border-radius: 12px;
            object-fit: contain;
        }

        .preview {
            max-height: 230px;
        }

        .camera-video {
            display: block;
            width: 100%;
            max-height: 350px;
            margin-top: 16px;
            border-radius: 12px;
            background: #000;
            object-fit: contain;
        }

        .error {
            padding: 16px;
            margin-bottom: 20px;
            border-radius: 10px;
            background: #7f1d1d;
        }

        .note {
            color: var(--muted);
            font-size: 14px;
            line-height: 1.7;
        }

        a {
            color: #c4b5fd;
        }

        :is(button, input, select, textarea, a):focus-visible {
            outline: 2px solid #c4b5fd;
            outline-offset: 3px;
        }

        [hidden] {
            display: none !important;
        }

        @media (max-width: 650px) {
            .grid {
                grid-template-columns: 1fr;
            }

            .panel {
                padding: 18px;
            }
        }
    </style>
</head>

<body>
<main>
    <h1>تصميمك بالـAI 🧪</h1>

    <p class="note">
        ارفع صورة واضحة لوجهك أو التقطها بالكاميرا،
        واكتب التصميم اللي بدك إياه بالعربي أو الإنجليزي.
    </p>

    <?php if ($error !== ''): ?>
        <div class="error" role="alert">
            <?= $escape($error) ?>
        </div>

        <p class="note">
            اختار صورتك مرة ثانية بعد تصحيح الخطأ.
        </p>
    <?php endif; ?>

    <div class="grid">
        <section class="panel">
            <h2>صمّم شخصيتك</h2>

            <form
                id="image-form"
                method="POST"
                action="aiImage.php"
                enctype="multipart/form-data"
            >
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= $escape($csrfToken) ?>"
                >

                <input type="hidden" name="mode" value="text">

                <label for="image">
                    صورتك — JPG أو PNG، حتى 5MB
                </label>

                <input
                    id="image"
                    name="image"
                    type="file"
                    accept="image/png,image/jpeg"
                    required
                >

                <button
                    id="open-camera"
                    class="button-secondary"
                    type="button"
                    aria-controls="camera-box"
                    aria-expanded="false"
                    hidden
                >
                    📷 افتح الكاميرا
                </button>

                <div id="camera-box" hidden>
                    <video
                        id="camera-video"
                        class="camera-video"
                        aria-label="معاينة الكاميرا"
                        autoplay
                        muted
                        playsinline
                    ></video>

                    <div class="camera-actions">
                        <button
                            id="capture-photo"
                            type="button"
                            disabled
                        >
                            التقط الصورة
                        </button>

                        <button
                            id="close-camera"
                            class="button-secondary"
                            type="button"
                        >
                            إغلاق الكاميرا
                        </button>
                    </div>
                </div>

                <p
                    id="camera-message"
                    class="note"
                    role="status"
                ></p>

                <img
                    id="preview"
                    class="preview"
                    alt="معاينة صورتك"
                    hidden
                >

                <label for="subject">
                    الشخص الموجود في صورتك
                </label>

                <select id="subject" name="subject" required>
                    <option
                        value="person"
                        <?= $subject === 'person' ? 'selected' : '' ?>
                    >
                        بدون تحديد
                    </option>

                    <option
                        value="man"
                        <?= $subject === 'man' ? 'selected' : '' ?>
                    >
                        رجل
                    </option>

                    <option
                        value="woman"
                        <?= $subject === 'woman' ? 'selected' : '' ?>
                    >
                        امرأة
                    </option>
                </select>

                <label for="description">
                    كيف بدك يكون تصميمك؟
                </label>

                <textarea
                    id="description"
                    name="description"
                    maxlength="1000"
                    rows="5"
                    dir="auto"
                    placeholder="مثال: حوّلني لشخصية أنمي بلبس ناروتو بقرية كونوها"
                    required
                ><?= $escape($description) ?></textarea>

                <button id="generate-button" type="submit">
                    ولّد تصميمي
                </button>

                <p id="status" class="note" role="status"></p>

                <noscript>
                    <p class="note">
                        يمكنك رفع صورة والتوليد بدون JavaScript.
                        تشغيل الكاميرا والمعاينة يحتاجان JavaScript.
                    </p>
                </noscript>
            </form>
        </section>

        <section class="panel">
            <h2>النتيجة</h2>

            <?php if ($generatedImage !== ''): ?>
                <img
                    src="data:image/png;base64,<?= $escape($generatedImage) ?>"
                    alt="التصميم الناتج بالذكاء الاصطناعي"
                >

                <p>
                    <a
                        href="data:image/png;base64,<?= $escape($generatedImage) ?>"
                        download="loothub-design.png"
                    >
                        تنزيل تصميمي ↓
                    </a>
                </p>
            <?php else: ?>
                <p class="note">
                    تصميمك رح يظهر هون بعد التوليد.
                </p>
            <?php endif; ?>
        </section>
    </div>
</main>

<script>
(() => {
    'use strict';

    const MAX_FILE_SIZE = 5 * 1024 * 1024;
    const MAX_CAPTURE_SIZE = 1280;
    const ALLOWED_TYPES = ['image/jpeg', 'image/png'];

    const form = document.getElementById('image-form');
    const imageInput = document.getElementById('image');
    const preview = document.getElementById('preview');
    const generateButton = document.getElementById('generate-button');
    const statusMessage = document.getElementById('status');

    const openCameraButton = document.getElementById('open-camera');
    const captureButton = document.getElementById('capture-photo');
    const closeCameraButton = document.getElementById('close-camera');
    const cameraBox = document.getElementById('camera-box');
    const video = document.getElementById('camera-video');
    const cameraMessage = document.getElementById('camera-message');

    let previewUrl = null;
    let cameraStream = null;
    let cameraRequestId = 0;
    let isSubmitting = false;

    function clearPreview() {
        preview.hidden = true;
        preview.removeAttribute('src');

        if (previewUrl) {
            URL.revokeObjectURL(previewUrl);
            previewUrl = null;
        }
    }

    function updatePreview() {
        clearPreview();
        imageInput.setCustomValidity('');
        statusMessage.textContent = '';

        const file = imageInput.files[0];

        if (!file) {
            return;
        }

        const isValid = ALLOWED_TYPES.includes(file.type)
            && file.size > 0
            && file.size <= MAX_FILE_SIZE;

        if (!isValid) {
            imageInput.setCustomValidity(
                'اختار صورة JPG أو PNG حتى 5MB.'
            );

            imageInput.reportValidity();
            return;
        }

        previewUrl = URL.createObjectURL(file);
        preview.src = previewUrl;
        preview.hidden = false;
    }

    function stopCamera() {
        cameraRequestId++;

        if (cameraStream) {
            cameraStream.getTracks().forEach(track => track.stop());
            cameraStream = null;
        }

        video.srcObject = null;
        cameraBox.hidden = true;
        captureButton.disabled = true;
        openCameraButton.disabled = isSubmitting;
        openCameraButton.setAttribute('aria-expanded', 'false');
    }

    function getCameraError(error) {
        const messages = {
            NotAllowedError:
                'اسمح باستخدام الكاميرا من إعدادات الموقع.',
            NotFoundError:
                'ما لقينا كاميرا متصلة بالجهاز.',
            NotReadableError:
                'تعذّر فتح الكاميرا؛ ممكن برنامج ثاني يستخدمها.'
        };

        return messages[error.name] || 'تعذّر تشغيل الكاميرا.';
    }

    async function openCamera() {
        if (isSubmitting) {
            return;
        }

        if (!navigator.mediaDevices?.getUserMedia) {
            cameraMessage.textContent =
                'الكاميرا تحتاج فتح الموقع عبر localhost أو HTTPS.';
            return;
        }

        stopCamera();

        const requestId = cameraRequestId;

        openCameraButton.disabled = true;
        openCameraButton.setAttribute('aria-expanded', 'true');
        cameraBox.hidden = false;
        cameraMessage.textContent =
            'اسمح للمتصفح باستخدام الكاميرا.';

        try {
            const stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: 'user',
                    width: { ideal: 1280 },
                    height: { ideal: 720 }
                },
                audio: false
            });

            // يمنع بقاء الكاميرا مفتوحة إذا أُغلق الطلب أثناء الإذن.
            if (requestId !== cameraRequestId) {
                stream.getTracks().forEach(track => track.stop());
                return;
            }

            cameraStream = stream;
            video.srcObject = stream;

            await video.play();

            if (requestId !== cameraRequestId) {
                return;
            }

            captureButton.disabled = video.readyState < 2;
            cameraMessage.textContent =
                'خلّي وجهك واضحًا ومقابل الكاميرا، ثم التقط الصورة.';
        } catch (error) {
            if (requestId !== cameraRequestId) {
                return;
            }

            stopCamera();
            cameraMessage.textContent = getCameraError(error);
        }
    }

    function createPhotoBlob() {
        const canvas = document.createElement('canvas');

        const ratio = Math.min(
            1,
            MAX_CAPTURE_SIZE / Math.max(
                video.videoWidth,
                video.videoHeight
            )
        );

        canvas.width = Math.round(video.videoWidth * ratio);
        canvas.height = Math.round(video.videoHeight * ratio);

        const context = canvas.getContext('2d');

        if (!context) {
            throw new Error('Canvas unavailable');
        }

        context.drawImage(video, 0, 0, canvas.width, canvas.height);

        return new Promise((resolve, reject) => {
            canvas.toBlob(blob => {
                if (blob) {
                    resolve(blob);
                } else {
                    reject(new Error('Capture failed'));
                }
            }, 'image/jpeg', 0.9);
        });
    }

    async function capturePhoto() {
        if (
            !cameraStream
            || video.readyState < 2
            || !video.videoWidth
            || !video.videoHeight
        ) {
            return;
        }

        const requestId = cameraRequestId;
        captureButton.disabled = true;

        try {
            const blob = await createPhotoBlob();

            if (requestId !== cameraRequestId) {
                return;
            }

            if (blob.size > MAX_FILE_SIZE) {
                throw new Error('Photo too large');
            }

            const photo = new File([blob], 'camera-face.jpg', {
                type: 'image/jpeg',
                lastModified: Date.now()
            });

            const transfer = new DataTransfer();
            transfer.items.add(photo);

            imageInput.files = transfer.files;
            imageInput.dispatchEvent(new Event('change', {
                bubbles: true
            }));

            cameraMessage.textContent =
                'تم التقاط الصورة. اكتب وصفك واضغط توليد.';
        } catch (error) {
            if (requestId !== cameraRequestId) {
                return;
            }

            cameraMessage.textContent =
                'تعذّر التقاط الصورة. حاول مرة ثانية أو ارفع صورة.';
            captureButton.disabled = !cameraStream;
        }
    }

    function handleSubmit(event) {
        if (isSubmitting) {
            event.preventDefault();
            return;
        }

        if (!form.checkValidity()) {
            event.preventDefault();
            form.reportValidity();
            return;
        }

        isSubmitting = true;
        stopCamera();

        generateButton.disabled = true;
        generateButton.textContent = 'جاري التوليد...';
        cameraMessage.textContent = '';

        statusMessage.textContent =
            'جاري تجهيز الوصف وتوليد التصميم. ممكن يحتاج عدة دقائق.';
    }

    openCameraButton.hidden = false;

    openCameraButton.addEventListener('click', openCamera);
    captureButton.addEventListener('click', capturePhoto);

    closeCameraButton.addEventListener('click', () => {
        stopCamera();
        cameraMessage.textContent = '';
    });

    video.addEventListener('loadeddata', () => {
        captureButton.disabled = !cameraStream || !video.videoWidth;
    });

    imageInput.addEventListener('change', () => {
        stopCamera();
        cameraMessage.textContent = '';
        updatePreview();
    });

    form.addEventListener('submit', handleSubmit);

    window.addEventListener('pagehide', () => {
        stopCamera();
        clearPreview();
    });

    window.addEventListener('pageshow', () => {
        isSubmitting = false;
        generateButton.disabled = false;
        generateButton.textContent = 'ولّد تصميمي';
        openCameraButton.disabled = false;
        statusMessage.textContent = '';
        updatePreview();
    });
})();
</script>
</body>
</html>