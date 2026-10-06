// Install before navigation, let the page load normally, then pause its timers.
export async function openWithDraftClock(page, path) {
    await page.clock.install({ time: new Date('2026-01-01T00:00:00Z') });
    await page.goto(path);
    await page.clock.pauseAt(new Date('2026-01-01T00:01:00Z'));
}
