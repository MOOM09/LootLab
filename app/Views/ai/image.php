<?php
$pageTitle = 'AI Image Studio';
$userHideAiLink = true;
$userExtraCss = '/css/ai-studio.css';

$escape = static fn ($value): string => htmlspecialchars(
    (string) $value,
    ENT_QUOTES | ENT_SUBSTITUTE,
    'UTF-8'
);

$userBaseUrl = rtrim((string) ($_ENV['APP_URL'] ?? ''), '/');

require __DIR__ . '/../layouts/user-header.php';
?>

<div class="ai-studio" dir="ltr">
    <!-- Studio heading -->
    <div class="ai-studio-heading">
        <div>
            <span class="ai-kicker">LOOT HUB / CREATIVE LAB</span>
            <h1>Make it <span>yours.</span> <small>✦</small></h1>
            <p>
                Turn your photo into a one-of-a-kind design with LOOTE AI.
                Then wear it, gift it, or save it.
            </p>
        </div>

        <a class="ai-outline" href="<?= $escape($userBaseUrl . '/ai.php') ?>">
            ✦ Chat with LOOTE
        </a>
    </div>

    <?php if ($error !== ''): ?>
        <div class="ai-generation-error" role="alert">
            <?= $escape($error) ?>
        </div>
    <?php endif; ?>

    <div class="ai-studio-grid">
        <!-- Step 01: Design input -->
        <section class="ai-studio-card ai-input-card">
            <div class="ai-card-title">
                <span class="ai-step">01</span>
                <div>
                    <h2>Create your design</h2>
                    <p>Upload a portrait and tell LOOTE your idea.</p>
                </div>
            </div>

            <form
                id="image-form"
                method="POST"
                action="<?= $escape($userBaseUrl . '/aiImage.php') ?>"
                enctype="multipart/form-data"
            >
                <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                <input type="hidden" name="mode" value="text">

                <label for="image">
                    Your photo <small>(JPG / PNG, max 5 MB)</small>
                </label>
                <input
                    id="image"
                    name="image"
                    type="file"
                    accept="image/jpeg,image/png"
                    required
                >

                <button
                    id="open-camera"
                    class="ai-outline ai-camera-btn"
                    type="button"
                    aria-controls="camera-box"
                    aria-expanded="false"
                    hidden
                >
                    📷 Use camera instead
                </button>

                <div id="camera-box" hidden>
                    <video
                        id="camera-video"
                        class="camera-video"
                        aria-label="Camera preview"
                        autoplay
                        muted
                        playsinline
                    ></video>

                    <div class="ai-camera-actions">
                        <button id="capture-photo" class="ai-primary" type="button" disabled>
                            Take photo
                        </button>
                        <button id="close-camera" class="ai-outline" type="button">
                            Close camera
                        </button>
                    </div>
                </div>

                <p id="camera-message" class="ai-help" role="status"></p>
                <img id="preview" class="ai-photo-preview" alt="Uploaded image preview" hidden>

                <label for="subject">Person in photo</label>
                <select id="subject" name="subject" required>
                    <option value="person" <?= $subject === 'person' ? 'selected' : '' ?>>
                        Person / no preference
                    </option>
                    <option value="man" <?= $subject === 'man' ? 'selected' : '' ?>>Man</option>
                    <option value="woman" <?= $subject === 'woman' ? 'selected' : '' ?>>Woman</option>
                </select>

                <label for="description">Describe your idea</label>
                <textarea
                    id="description"
                    name="description"
                    maxlength="1000"
                    rows="5"
                    placeholder="Example: Transform me into an anime hero wearing a futuristic blue jacket, cinematic background…"
                    required
                ><?= $escape($description) ?></textarea>

                <button id="generate-button" class="ai-primary" type="submit">
                    ✦ Generate my design
                </button>

                <p id="status" class="ai-help" role="status"></p>
                <noscript>
                    <p class="ai-help">JavaScript is required for the live progress display.</p>
                </noscript>
            </form>
        </section>

        <!-- Step 02: Generated design -->
        <section class="ai-studio-card ai-result-card">
            <div class="ai-card-title">
                <span class="ai-step">02</span>
                <div>
                    <h2>Your creation</h2>
                    <p>Designed by you, powered by AI.</p>
                </div>
            </div>

            <?php if ($generatedImage !== ''): ?>
                <div class="ai-result-frame">
                    <img
                        id="generated-design"
                        src="data:image/png;base64,<?= $escape($generatedImage) ?>"
                        alt="Your generated AI artwork"
                    >
                </div>

                <div class="ai-result-actions">
                    <a
                        class="ai-outline"
                        href="data:image/png;base64,<?= $escape($generatedImage) ?>"
                        download="loothub-ai-design.png"
                    >
                        ↓ Download image
                    </a>
                    <a
                        class="ai-outline"
                        id="ai-profile-link"
                        href="<?= $escape($userBaseUrl . '/profile') ?>"
                    >
                        ✓ Add to my profile
                    </a>
                </div>

                <p class="ai-help">
                    ✓ Saved to your profile automatically. You can also add a printed
                    product to the cart below.
                </p>
            <?php else: ?>
                <div class="ai-empty-result">
                    <span>✦</span>
                    <h3>Your imagination starts here</h3>
                    <p>Upload a photo, describe a style, and your design will appear here.</p>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <?php if ($generatedImage !== '' && !empty($savedDesignId)): ?>
        <!-- Step 03: 3D product customizer -->
        <section
            class="ai-studio-card ai-product-customizer"
            id="ai-products"
            data-design-id="<?= (int) $savedDesignId ?>"
        >
            <div class="ai-card-title">
                <span class="ai-step">03</span>
                <div>
                    <h2>Wear your creation</h2>
                    <p>See your art printed on a product. Choose a style, then add it to the cart.</p>
                </div>
            </div>

            <div class="ai-options" role="group" aria-label="Choose product">
                <button type="button" data-model="hoodie-black" aria-pressed="true">Black hoodie</button>
                <button type="button" data-model="hoodie-white" aria-pressed="false">White hoodie</button>
                <button type="button" data-model="tshirt-black" aria-pressed="false">Black T-shirt</button>
                <button type="button" data-model="tshirt-white" aria-pressed="false">White T-shirt</button>
                <button type="button" data-model="mug-white" aria-pressed="false">White mug</button>
            </div>

            <div class="ai-preview-layout">
                <div>
                    <div
                        class="ai-stage"
                        id="ai-stage"
                        aria-label="Interactive 3D product preview"
                    ></div>

                    <!-- Print positioning controls -->
                    <div class="ai-controls">
                        <label for="ai-print-size">
                            Print Size
                            <input id="ai-print-size" type="range" min="40" max="100" value="75">
                        </label>
                        <label for="ai-print-x">
                            Left / Right
                            <input id="ai-print-x" type="range" min="-100" max="100" value="0">
                        </label>
                        <label for="ai-print-y">
                            Up / Down
                            <input id="ai-print-y" type="range" min="-100" max="100" value="0">
                        </label>
                    </div>

                    <p id="ai-model-status" class="ai-help" role="status">Loading preview…</p>
                    <p class="ai-credit" id="ai-model-credit"></p>
                </div>

                <div class="ai-purchase-panel">
                    <span class="ai-kicker">CUSTOM-MADE FOR YOU</span>
                    <h3 id="ai-product-name">Black hoodie</h3>
                    <p class="ai-large-price">
                        <strong id="ai-product-price">10.00</strong> JOD
                    </p>
                    <p>One unique piece featuring your personal AI design.</p>

                    <div class="ai-purchase-actions">
                        <button class="ai-primary" type="button" id="ai-add-cart">
                            🛒 Add to cart
                        </button>
                        <a class="ai-outline" href="<?= $escape($userBaseUrl . '/profile') ?>">
                            ✓ Add to my profile
                        </a>
                    </div>

                    <p id="ai-cart-feedback" role="status" class="ai-help"></p>
                    <p class="ai-help">
                        Secure checkout · Your AI design stays attached to this item.
                    </p>
                </div>
            </div>
        </section>

        <script type="module" src="<?= $escape($userBaseUrl . '/js/ai-products.js') ?>"></script>
        <script src="<?= $escape($userBaseUrl . '/js/ai-studio-cart.js') ?>" defer></script>
    <?php endif; ?>
</div>

<!-- AI generation progress overlay -->
<div
    id="ai-progress-layer"
    class="ai-progress-overlay"
    role="status"
    aria-live="polite"
    hidden
>
    <div class="ai-progress-modal">
        <div class="ai-progress-logo">✦</div>
        <span class="ai-kicker">LOOTE IS CREATING</span>
        <h2>Making your design…</h2>
        <div class="ai-progress-number" id="ai-progress-number">0%</div>
        <div class="ai-progress-track">
            <div id="ai-progress-bar"></div>
        </div>
        <p id="ai-progress-phase">Preparing your prompt…</p>
        <small>Estimated progress — generation time depends on the AI service.</small>
    </div>
</div>
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

    async function handleSubmit(event) {
        event.preventDefault();
        if (isSubmitting) return;
        if (!form.checkValidity()) {form.reportValidity();return;}
        isSubmitting = true;
        stopCamera();
        generateButton.disabled = true;
        generateButton.textContent = 'Generating your design…';
        const layer = document.getElementById('ai-progress-layer');
        const bar = document.getElementById('ai-progress-bar');
        const number = document.getElementById('ai-progress-number');
        const phase = document.getElementById('ai-progress-phase');
        layer.hidden = false;
        let progress = 0;
        let timer = setInterval(() => {
            // This is an animated estimate, not backend generation telemetry.
            progress = Math.min(95, progress + (progress < 65 ? 2 : .3));
            number.textContent = `${Math.floor(progress)}%`;
            bar.style.width = `${progress}%`;
            phase.textContent = progress < 30 ? 'Preparing your prompt…' : progress < 70 ? 'Creating your AI artwork…' : 'Finishing touches…';
        }, 600);
        try {
            const response = await fetch(form.action, {
                method: 'POST', body: new FormData(form), credentials: 'same-origin',
                headers: {'Accept': 'text/html'}
            });
            const html = await response.text();
            const result = new DOMParser().parseFromString(html, 'text/html');
            const errorBox = result.querySelector('.ai-generation-error');
            if (!response.ok || errorBox) {
                throw new Error(errorBox?.textContent?.trim() || 'Could not create the image. Please try again.');
            }
            if (!response.url.includes('design_id=')) {
                throw new Error('The image was not saved. Please check your connection and try again.');
            }
            clearInterval(timer);
            number.textContent = '100%';bar.style.width = '100%';
            phase.textContent = 'Your design is ready!';
            window.setTimeout(() => window.location.assign(response.url), 500);
        } catch (error) {
            clearInterval(timer);
            layer.hidden = true;
            statusMessage.textContent = error.message || 'Generation failed. Please try again.';
            statusMessage.classList.add('ai-generation-error');
            generateButton.disabled = false;
            generateButton.textContent = 'Generate my design';
            isSubmitting = false;
        }
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
        generateButton.textContent = 'Generate my design';
        openCameraButton.disabled = false;
        statusMessage.textContent = '';
        updatePreview();
    });
})();
</script>
<?php require __DIR__ . '/../layouts/user-footer.php'; ?>
