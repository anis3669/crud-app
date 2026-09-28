// @ts-check
import { test, expect } from "@playwright/test";

test("admin can create an invoice successfully", async ({ page }) => {
    await page.goto("http://127.0.0.1:8000/login");

    // Login
    await page.getByLabel("Email").fill("admin@gmail.com");
    await page.getByLabel("Password").fill("password");
    await page.getByRole("button", { name: "Sign In" }).click();

    // Open invoices
    await expect(page).toHaveURL("http://127.0.0.1:8000/products");
    await page.getByRole("button", { name: "Invoices" }).click();

    await expect(page).toHaveURL("http://127.0.0.1:8000/invoices");
    await expect(page.getByRole("heading", { name: "Invoices" })).toBeVisible();

    // Create invoice
    await page.getByRole("button", { name: /Create Invoice/ }).click();

    await expect(page).toHaveURL("http://127.0.0.1:8000/invoices/create");

    // Customer information
    await page.getByLabel("Customer Name").fill("Playwright Test Customer");
    await page.getByLabel("Email").fill("playwright@test.com");
    await page.getByLabel("Phone").fill("9800000000");

    // Select a product
    const productSelect = page.getByRole("combobox");

    // Wait for products to load
    await expect(productSelect).toBeVisible({ timeout: 15000 });
    await expect(productSelect).toBeEnabled({ timeout: 15000 });

    // Select the product
    await productSelect.selectOption({
        label: "Playwright Food & Beverage Ramesh Karki — NPR 99.99 — Stock: 9",
    });
    // Quantity
    await page.getByLabel("Quantity").fill("2");

    // Add product
    await page.getByRole("button", { name: "Add Product" }).click();

    // Tax and discount
    await page.getByLabel("Tax Percentage").fill("0");
    await page.getByLabel("Discount Percentage").fill("0");

    // Create invoice
    await page.getByRole("button", { name: "Create Invoice" }).click();

    // Successful creation
    await expect(page).toHaveURL("http://127.0.0.1:8000/invoices", {
        timeout: 30000,
    });

    await expect(page.getByRole("heading", { name: "Invoices" })).toBeVisible();

    console.log("Invoice created successfully.");
});
