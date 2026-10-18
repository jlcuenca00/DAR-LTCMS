import { test, expect } from "@playwright/test";

const username = process.env.E2E_STAFF_USERNAME;
const password = process.env.E2E_STAFF_PASSWORD;

test("account details keep shared gutters and switch panels without losing edits", async ({
    page,
}) => {
    test.skip(!username || !password, "Requires isolated browser accounts.");
    await page.goto("/login");
    await page.locator("#username").fill(username);
    await page.locator("#password").fill(password);
    await page.locator('button[type="submit"]').click();
    await expect(page).toHaveURL(/\/staff\/dashboard/);
    // Account pages do not start the dashboard tour.
    await page.goto(
        `/staff/users?search=${encodeURIComponent(username + ".geo2")}`,
    );
    await page
        .getByRole("link", { name: /Manage$/ })
        .first()
        .click();
    const originalName = await page
        .getByLabel("Name", { exact: true })
        .inputValue();
    for (const width of [1440, 390]) {
        await page.setViewportSize({ width, height: 900 });
        const bounds = await page
            .locator(".account-workspace")
            .evaluate((root) => {
                const panel = root.getBoundingClientRect();
                const content = root
                    .closest(".staff-content-inner")
                    .getBoundingClientRect();
                return {
                    left: panel.left - content.left,
                    right: content.right - panel.right,
                };
            });
        expect(Math.abs(bounds.left)).toBeLessThan(2);
        expect(Math.abs(bounds.right)).toBeLessThan(2);
        await expect(page.locator(".account-back")).toHaveCount(0);
        const title = await page.locator(".account-header h2").boundingBox();
        expect(title.width).toBeGreaterThan(150);
        expect(title.height).toBeLessThan(65);
        await page.getByRole("tab", { name: "Details", exact: true }).click();
        await expect(page.getByLabel("Role", { exact: true })).toBeVisible();
        await expect(page.getByLabel("Linked Landowner Record", { exact: true })).toBeHidden();
        await page
            .getByLabel("Role", { exact: true })
            .selectOption("landowner");
        await expect(page.getByLabel("Linked Landowner Record", { exact: true })).toBeVisible();
        await page.getByLabel("Role", { exact: true }).selectOption("geodetic");
        await expect(page.getByLabel("Linked Landowner Record", { exact: true })).toBeHidden();
        await page
            .getByLabel("Name", { exact: true })
            .fill("Unsaved browser check");
        await expect(
            page.getByText("Unsaved changes", { exact: true }),
        ).toBeVisible();
        await page.getByRole("tab", { name: "Details", exact: true }).focus();
        await page.keyboard.press("ArrowRight");
        await expect(
            page.getByRole("tab", { name: "Security", exact: true }),
        ).toHaveAttribute("aria-selected", "true");
        await expect(
            page.getByRole("heading", {
                name: "Sign-in & security",
                exact: true,
            }),
        ).toBeVisible();
        await expect(page.getByLabel("Name", { exact: true })).toBeHidden();
        await page.keyboard.press("End");
        await expect(
            page.getByRole("heading", { name: "Account history", exact: true }),
        ).toBeVisible();
        await page.getByRole("tab", { name: "Details", exact: true }).click();
        await expect(page.getByLabel("Name", { exact: true })).toHaveValue(
            "Unsaved browser check",
        );
        await page.getByLabel("Name", { exact: true }).fill(originalName);
        await expect(
            page.getByText("Unsaved changes", { exact: true }),
        ).toBeHidden();
        expect(
            await page.evaluate(
                () => document.documentElement.scrollWidth <= innerWidth + 1,
            ),
        ).toBe(true);
    }
});
