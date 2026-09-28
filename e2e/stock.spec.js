// @ts-check
import { test, expect } from "@playwright/test";

test("admin can adjust product stock successfully", async ({ page }) => {
    // Login
    await page.goto("http://127.0.0.1:8000/login");

    await page.getByLabel("Email").fill("admin@gmail.com");
    await page.getByLabel("Password").fill("password");
    await page.getByRole("button", { name: "Sign In" }).click();

    // Open Stock
    await expect(page).toHaveURL("http://127.0.0.1:8000/products");

    await page
        .getByRole("button", {
            name: "Stock",
            exact: true,
        })
        .click();

    await expect(page).toHaveURL("http://127.0.0.1:8000/inventory");

    await expect(page.getByRole("heading", { name: "Stock" })).toBeVisible();

    // Find the specific product row
    const productRow = page.getByRole("row").filter({
        hasText: "Electric Coffee Maker",
    });

    await expect(productRow).toBeVisible();

    // Get current stock before adjustment
    const stockCell = productRow.getByRole("cell").filter({
        hasText: /In stock|Low stock|Out of stock/,
    });

    const stockText = await stockCell.innerText();
    const currentStock = Number(stockText.match(/\d+/)?.[0]);

    expect(currentStock).toBeGreaterThanOrEqual(5);

    // Open adjustment modal for this product
    await productRow
        .getByRole("button", {
            name: "Adjust",
        })
        .click();

    // Verify modal
    await expect(
        page.getByRole("heading", {
            name: "Adjust stock",
        }),
    ).toBeVisible();

    // Select "Stock out"
    const stockOutRadio = page.getByRole("radio", {
        name: /Stock out Remove units from stock/,
    });

    await stockOutRadio.locator("..").click();

    // Verify Stock out was selected
    await expect(stockOutRadio).toBeChecked();

    // Quantity
    await page.getByLabel("Quantity").fill("5");

    // Reason
    await page.getByLabel("Reason").fill("Playwright stock adjustment test");

    // Save adjustment
    await page
        .getByRole("button", {
            name: "Save adjustment",
        })
        .click();

    // Modal should close after successful submission
    await expect(
        page.getByRole("heading", {
            name: "Adjust stock",
        }),
    ).not.toBeVisible();

    // Verify stock decreased by 5
    await expect(productRow).toContainText(String(currentStock - 5));
});
