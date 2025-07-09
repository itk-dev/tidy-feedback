import { test, expect } from "@playwright/test";

test("Send feedback", async ({ page }) => {
    await page.goto("/tidy-feedback/test");

    await page.getByRole("button", { name: "Feedback" }).click();

    await expect(
        page.getByRole("button", { name: "Feedback", exact: true }),
    ).not.toBeVisible();

    await page.mouse.move(300, 300);
    await page.mouse.down();
    await page.mouse.move(10, 30);
    await page.mouse.up();

    await expect(
        page.getByRole("heading", { name: "Your feedback" }),
    ).toBeVisible();
    await page.getByPlaceholder("Subject").fill("My first feedback");
    await page.getByPlaceholder("Your email address").fill("user@example.com");
    await page.getByPlaceholder("Description").fill("Something's not right …");

    await page.mouse.move(600, 500);
    await page.mouse.down();
    await page.mouse.move(500, 600);
    await page.mouse.move(400, 100);
    await page.mouse.up();

    await page.getByRole("button", { name: "Submit feedback" }).click();

    await expect(page.getByText("Feedback created")).toBeVisible();
    await expect(
        page.getByRole("heading", { name: "Your feedback" }),
    ).not.toBeVisible();

    await expect(
        page.getByRole("button", { name: "Feedback", exact: true }),
    ).toBeVisible();
});
