// @ts-check
import { test, expect } from "@playwright/test";

test("admin can create an invoice successfully", async ({ page }) => {
    // Login
    await page.goto("http://127.0.0.1:8000/login");

    await page.getByLabel("Email").fill("admin@gmail.com");
    await page.getByLabel("Password").fill("password");
    await page.getByRole("button", { name: "Sign In" }).click();

    await expect(page).toHaveURL("http://127.0.0.1:8000/products");

    // Open invoices
    await page.getByRole("button", { name: "Invoices" }).click();

    await expect(page).toHaveURL("http://127.0.0.1:8000/invoices");
    await expect(page.getByRole("heading", { name: "Invoices" })).toBeVisible();

    // Open create invoice page
    await page.getByRole("button", { name: /Create Invoice/ }).click();

    await expect(page).toHaveURL("http://127.0.0.1:8000/invoices/create");

    // Customer information
    await page.getByLabel("Customer Name").fill("Playwright Test Customer");

    await page.getByLabel("Email").fill("playwright@test.com");
    await page.getByLabel("Phone").fill("9800000000");

    // Product
    const productSelect = page.getByRole("combobox");

    await expect(productSelect).toBeVisible();
    await expect(productSelect).toBeEnabled();

    const productOption = productSelect.locator("option").filter({
        hasText: "Playwright Food & Beverage Ramesh Karki",
    });

    await expect(productOption).toHaveCount(1);

    const productId = await productOption.getAttribute("value");

    expect(productId).not.toBeNull();

    await productSelect.selectOption(productId);

    // Quantity
    await page.getByLabel("Quantity").fill("2");

    // Add product
    await page.getByRole("button", { name: "Add Product" }).click();

    // Tax and discount
    await page.getByLabel("Tax Percentage").fill("0");
    await page.getByLabel("Discount Percentage").fill("0");

    // Create invoice
    await page.getByRole("button", { name: "Create Invoice" }).click();

    // Verify successful creation
    await expect(page).toHaveURL("http://127.0.0.1:8000/invoices", {
        timeout: 30000,
    });

    await expect(page.getByRole("heading", { name: "Invoices" })).toBeVisible();
});
