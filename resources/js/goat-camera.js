/**
 * Goat Camera Capture Module
 *
 * Provides camera capture functionality using the MediaDevices getUserMedia API.
 * Captured frames are converted to File objects and assigned to the target input.
 *
 * Usage (from Blade inline script):
 *   GoatCamera.open('targetInputId', 'videoElId', 'previewImgId')
 */
window.GoatCamera = (function () {
    let stream = null;
    let activeTargetInput = null;
    let activePreviewImg = null;

    function stopStream() {
        if (stream) {
            stream.getTracks().forEach((t) => t.stop());
            stream = null;
        }
    }

    async function open(config) {
        activeTargetInput = config.targetInputId;
        activePreviewImg = config.previewImgId;

        const modal = document.getElementById(config.modalId);
        const video = document.getElementById(config.videoId);
        const errorEl = document.getElementById(config.errorId);

        if (!modal || !video) return;

        errorEl?.classList.add('hidden');
        modal.classList.remove('hidden');

        try {
            stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: 'environment', width: { ideal: 1280 }, height: { ideal: 720 } },
                audio: false,
            });
            video.srcObject = stream;
            await video.play();
        } catch (err) {
            if (errorEl) {
                errorEl.textContent = 'Camera access denied or unavailable. Please use the upload option instead.';
                errorEl.classList.remove('hidden');
            }
            stopStream();
        }
    }

    function capture(config) {
        const video = document.getElementById(config.videoId);
        const canvas = document.getElementById(config.canvasId);
        if (!video || !canvas) return;

        const w = video.videoWidth || 640;
        const h = video.videoHeight || 480;
        canvas.width = w;
        canvas.height = h;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0, w, h);

        canvas.toBlob(
            (blob) => {
                if (!blob) return;
                const file = new File([blob], 'capture-' + Date.now() + '.jpg', { type: 'image/jpeg' });
                const dt = new DataTransfer();
                dt.items.add(file);

                const input = document.getElementById(activeTargetInput);
                if (input) {
                    input.files = dt.files;
                    // Trigger change event for any listeners
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                }

                // Show preview
                if (activePreviewImg) {
                    const preview = document.getElementById(activePreviewImg);
                    if (preview) {
                        preview.src = URL.createObjectURL(blob);
                        preview.classList.remove('hidden');
                    }
                }

                close(config);
            },
            'image/jpeg',
            0.9
        );
    }

    function close(config) {
        const modal = document.getElementById(config.modalId);
        if (modal) modal.classList.add('hidden');
        stopStream();
    }

    return { open, capture, close, stopStream };
})();
