// @ts-check
import { test, expect } from "@playwright/test";

test("admin can adjust product stock successfully", async ({ page }) => {
    await page.goto("http://127.0.0.1:8000/login");

    // Login
    await page.getByLabel("Email").fill("admin@gmail.com");
    await page.getByLabel("Password").fill("password");
    await page.getByRole("button", { name: "Sign In" }).click();

    // Open Stock
    await expect(page).toHaveURL("http://127.0.0.1:8000/products");

    await page.getByRole("button", { name: "Stock", exact: true }).click();

    await expect(page).toHaveURL("http://127.0.0.1:8000/inventory");
    await expect(page.getByRole("heading", { name: "Stock" })).toBeVisible();

    // Open adjustment
    await page.getByRole("button", { name: "Adjust" }).first().click();
    // Adjust stock
    await expect(page.getByText("Adjust stock")).toBeVisible();

    // Stock in
    await page.getByRole("radio", { name: /Stock in/ }).check();

    // Quantity
    await page.getByLabel("Quantity").fill("5");

    // Reason
    await page.getByLabel("Reason").fill("Playwright stock adjustment test");

    // Save
    await page.getByRole("button", { name: "Save adjustment" }).click();

    // Verify stock increased from 50 to 55
    const firstProductRow = page.getByRole("row").filter({
        hasText: "Electric Coffee Maker",
    });

    await expect(firstProductRow).toContainText("55");
});
