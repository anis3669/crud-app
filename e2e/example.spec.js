// @ts-check
import { test, expect } from "@playwright/test";

test("admin can create products for all categories and suppliers", async ({
    page,
}) => {
    // Allow enough time for 20 product creations
    test.setTimeout(120000);

    // Login
    await page.goto("http://127.0.0.1:8000/login");

    await page.getByLabel("Email").fill("admin@gmail.com");
    await page.getByLabel("Password").fill("password");

    await page.getByRole("button", { name: "Sign In" }).click();

    // Verify login
    await expect(page).toHaveURL("http://127.0.0.1:8000/products", {
        timeout: 20000,
    });

    await expect(page.getByRole("heading", { name: "Products" })).toBeVisible({
        timeout: 10000,
    });

    const categories = [
        "Electronics",
        "Clothing",
        "Sports",
        "Home & Kitchen",
        "Food & Beverage",
    ];

    const suppliers = [
        "Rajesh Sharma",
        "Suman Thapa",
        "Amit Gupta",
        "Ramesh Karki",
    ];

    // Create every category + supplier combination
    for (const category of categories) {
        for (const supplier of suppliers) {
            // Always start from Products page
            await page.goto("http://127.0.0.1:8000/products");

            // Wait for Products page
            await expect(
                page.getByRole("heading", { name: "Products" }),
            ).toBeVisible({
                timeout: 10000,
            });

            // Open Create Product page
            const addProductButton = page.getByRole("button", {
                name: "Add Product",
            });

            await expect(addProductButton).toBeVisible({
                timeout: 10000,
            });

            await addProductButton.click();

            // Wait for Create Product page
            await expect(
                page.getByRole("heading", { name: "Add Product" }),
            ).toBeVisible({
                timeout: 10000,
            });

            // Generate unique SKU
            const sku = `PW-${Date.now()}-${Math.floor(Math.random() * 10000)}`;

            // Wait until form data has finished loading
            const productNameInput = page.getByLabel("Product Name");

            await expect(productNameInput).toBeEnabled({
                timeout: 20000,
            });

            // Product Name
            await productNameInput.fill(`Playwright ${category} ${supplier}`);

            // SKU
            await page.getByLabel("SKU").fill(sku);

            // Category
            await page.getByLabel("Category").selectOption({
                label: category,
            });

            // Supplier
            await page.getByLabel("Supplier").selectOption({
                label: supplier,
            });

            // Description
            await page
                .getByLabel("Description")
                .fill(
                    `Automated Playwright product for ${category} supplied by ${supplier}.`,
                );

            // Price
            await page.getByLabel("Price").fill("99.99");

            // Quantity
            await page.getByLabel("Quantity").fill("10");

            // Product image
            await page
                .getByLabel("Click to upload an image JPG")
                .setInputFiles("e2e/fixtures/mahadevv.png");

            // Create product
            const createProductButton = page.getByRole("button", {
                name: "Create Product",
            });

            await expect(createProductButton).toBeEnabled({
                timeout: 10000,
            });

            await Promise.all([
                page.waitForURL("http://127.0.0.1:8000/products", {
                    timeout: 30000,
                }),
                createProductButton.click(),
            ]);

            // Verify the created product by unique SKU
            await expect(page.getByText(sku)).toBeVisible({
                timeout: 15000,
            });

            console.log(
                `Created and verified: ${category} | ${supplier} | SKU: ${sku}`,
            );
        }
    }

    console.log("All category and supplier combinations tested.");
});
