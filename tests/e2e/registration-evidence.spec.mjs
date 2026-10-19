import { test, expect } from '@playwright/test';
import { readFileSync } from 'node:fs';

async function evidencePage(page) {
    await page.route('**/evidence-fixture', route => route.fulfill({
        contentType: 'text/html', body: `<meta name="viewport" content="width=device-width, initial-scale=1">
        <form><section data-registration-evidence>
        ${['id_front', 'id_back', 'selfie'].map(kind => `<label>${kind}<input type="file" data-evidence-file="${kind}"></label><img data-evidence-preview="${kind}" alt="${kind} preview" hidden>`).join('')}
        <button type="button" data-selfie-start>Open selfie camera</button>
        <video data-selfie-video autoplay playsinline muted hidden></video>
        <div data-selfie-controls hidden><button type="button" data-selfie-capture>Capture selfie</button><button type="button" data-selfie-stop>Close camera</button></div>
        <p role="status" data-selfie-status></p></section></form>`,
    }));
    await page.goto('/evidence-fixture');
    await page.addScriptTag({ content: readFileSync('resources/js/registration-evidence.js', 'utf8') });
}

test('camera starts on request, captures a file, and stops the video track', async ({ page }) => {
    await evidencePage(page);
    await page.evaluate(() => {
        window.cameraCalls = [];
        Object.defineProperty(navigator, 'mediaDevices', { configurable: true, value: {
            getUserMedia: async constraints => {
                window.cameraCalls.push(constraints);
                const canvas = document.createElement('canvas');
                canvas.width = 400; canvas.height = 400;
                const context = canvas.getContext('2d');
                context.fillStyle = 'blue';
                context.fillRect(0, 0, 400, 400);
                const stream = canvas.captureStream(10);
                window.cameraTrack = stream.getVideoTracks()[0];
                window.cameraTimer = setInterval(() => context.fillRect(0, 0, 400, 400), 50);
                return stream;
            },
        } });
    });
    expect(await page.evaluate(() => window.cameraCalls.length)).toBe(0);
    await page.getByRole('button', { name: 'Open selfie camera' }).click();
    const video = page.locator('[data-selfie-video]');
    await expect(video).toBeVisible();
    await expect.poll(() => video.evaluate(node => node.videoWidth)).toBe(400);
    await page.getByRole('button', { name: 'Capture selfie' }).click();
    await expect(page.locator('[data-evidence-preview="selfie"]')).toBeVisible();
    await expect(video).toBeHidden();
    expect(await page.locator('[data-evidence-file="selfie"]').evaluate(input => input.files[0].type)).toBe('image/jpeg');
    expect(await page.evaluate(() => window.cameraTrack.readyState)).toBe('ended');
    expect(await page.evaluate(() => window.cameraCalls[0].audio)).toBe(false);
    await expect(page.getByRole('status')).toContainText('Selfie captured');
    await page.evaluate(() => clearInterval(window.cameraTimer));
});

test('camera denial offers upload fallback and invalid uploads do not get a preview', async ({ page }) => {
    await evidencePage(page);
    await page.evaluate(() => Object.defineProperty(navigator, 'mediaDevices', { configurable: true, value: {
        getUserMedia: async () => { throw new DOMException('Denied', 'NotAllowedError'); },
    } }));
    await page.getByRole('button', { name: 'Open selfie camera' }).click();
    await expect(page.getByRole('status')).toContainText('upload a clear selfie');
    const input = page.locator('[data-evidence-file="id_front"]');
    await input.setInputFiles({ name: 'bad.svg', mimeType: 'image/svg+xml', buffer: Buffer.from('<svg></svg>') });
    expect(await input.evaluate(node => node.validity.valid)).toBe(false);
    await expect(page.locator('[data-evidence-preview="id_front"]')).toBeHidden();
    await input.setInputFiles({ name: 'id.png', mimeType: 'image/png', buffer: Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y9Zl1sAAAAASUVORK5CYII=', 'base64') });
    expect(await input.evaluate(node => node.validity.valid)).toBe(true);
    await expect(page.locator('[data-evidence-preview="id_front"]')).toBeVisible();
    expect(await page.evaluate(() => localStorage.length)).toBe(0);
});
