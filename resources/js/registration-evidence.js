function initRegistrationEvidence() {
    const root = document.querySelector('[data-registration-evidence]');
    if (!root) return;
    const form = root.closest('form');
    const video = root.querySelector('[data-selfie-video]');
    const controls = root.querySelector('[data-selfie-controls]');
    const start = root.querySelector('[data-selfie-start]');
    const capture = root.querySelector('[data-selfie-capture]');
    const status = root.querySelector('[data-selfie-status]');
    const selfie = root.querySelector('[data-evidence-file="selfie"]');
    const previews = new Map();
    let stream = null;
    let generation = 0;
    const stop = () => {
        generation += 1;
        stream?.getTracks().forEach(track => track.stop());
        stream = null;
        video.srcObject = null;
        video.hidden = true;
        controls.hidden = true;
        start.disabled = false;
    };
    root.querySelectorAll('[data-evidence-file]').forEach(input => {
        input.addEventListener('change', () => {
            const kind = input.dataset.evidenceFile;
            const preview = root.querySelector(`[data-evidence-preview="${kind}"]`);
            if (previews.has(kind)) URL.revokeObjectURL(previews.get(kind));
            previews.delete(kind);
            preview.hidden = true;
            preview.removeAttribute('src');
            input.setCustomValidity('');
            const file = input.files?.[0];
            if (!file) return;
            if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 2 * 1024 * 1024) {
                input.setCustomValidity('Choose a JPG, PNG, or WebP image no larger than 2 MB.');
                input.reportValidity();
                return;
            }
            const url = URL.createObjectURL(file);
            previews.set(kind, url);
            preview.src = url;
            preview.hidden = false;
            if (kind === 'selfie') stop();
        });
    });
    start.addEventListener('click', async () => {
        if (!navigator.mediaDevices?.getUserMedia) {
            status.textContent = 'Camera access is unavailable. Upload a clear selfie using the field above.';
            return;
        }
        stop();
        const attempt = generation;
        start.disabled = true;
        status.textContent = 'Requesting camera access…';
        try {
            const opened = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user', width: { ideal: 1280 } }, audio: false });
            if (generation !== attempt) { opened.getTracks().forEach(track => track.stop()); return; }
            stream = opened;
            video.srcObject = stream;
            video.hidden = false;
            controls.hidden = false;
            await video.play();
            status.textContent = 'Face the camera, then capture your selfie. You can retake it before submitting.';
        } catch {
            stop();
            status.textContent = 'Unable to open the camera. Check permission or upload a clear selfie above.';
        }
    });
    capture.addEventListener('click', () => {
        if (!video.videoWidth || !video.videoHeight) {
            status.textContent = 'Wait for the camera image before capturing.';
            return;
        }
        const attempt = generation;
        const canvas = document.createElement('canvas');
        const scale = Math.min(1, 1280 / Math.max(video.videoWidth, video.videoHeight));
        canvas.width = Math.round(video.videoWidth * scale);
        canvas.height = Math.round(video.videoHeight * scale);
        canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
        capture.disabled = true;
        canvas.toBlob(blob => {
            capture.disabled = false;
            if (generation !== attempt) return;
            if (!blob) { status.textContent = 'Capture failed. Please try again.'; return; }
            const transfer = new DataTransfer();
            transfer.items.add(new File([blob], 'selfie.jpg', { type: 'image/jpeg' }));
            selfie.files = transfer.files;
            selfie.dispatchEvent(new Event('change', { bubbles: true }));
            status.textContent = 'Selfie captured. Review the preview or open the camera to retake it.';
        }, 'image/jpeg', 0.85);
    });
    root.querySelector('[data-selfie-stop]').addEventListener('click', () => {
        stop();
        status.textContent = 'Camera closed. You can upload a selfie or open it again.';
    });
    form.addEventListener('submit', stop);
    window.addEventListener('pagehide', () => {
        stop();
        previews.forEach(url => URL.revokeObjectURL(url));
    });
    window.addEventListener('pageshow', event => {
        if (event.persisted) root.querySelectorAll('[data-evidence-file]').forEach(input => input.dispatchEvent(new Event('change')));
    });
    document.addEventListener('visibilitychange', () => { if (document.hidden) stop(); });
}
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initRegistrationEvidence, { once: true });
else initRegistrationEvidence();
