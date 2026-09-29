// @ts-check
import { test, expect } from "@playwright/test";

test("admin can create a product for every category and supplier combination", async ({
    page,
}) => {
    test.setTimeout(300000);

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

    // Login
    await page.goto("http://127.0.0.1:8000/login");

    await page.getByLabel("Email").fill("admin@gmail.com");
    await page.getByLabel("Password").fill("password");

    await page.getByRole("button", { name: "Sign In" }).click();

    await expect(page).toHaveURL("http://127.0.0.1:8000/products", {
        timeout: 30000,
    });

    await expect(page.getByRole("heading", { name: "Products" })).toBeVisible({
        timeout: 15000,
    });

    // Create every category × supplier combination
    for (const category of categories) {
        for (const supplier of suppliers) {
            // Open Add Product directly
            await page.goto("http://127.0.0.1:8000/products/create");

            // Wait for product form
            await expect(
                page.getByRole("heading", { name: "Add Product" }),
            ).toBeVisible({
                timeout: 15000,
            });

            // Wait until category/supplier data is loaded
            const productNameInput = page.getByLabel("Product Name");

            await expect(productNameInput).toBeEnabled({
                timeout: 20000,
            });

            // Generate unique SKU
            const sku = `PW-${Date.now()}-${Math.floor(Math.random() * 10000)}`;

            // Fill form
            await productNameInput.fill(`Playwright ${category} ${supplier}`);

            await page.getByLabel("SKU").fill(sku);

            await page.getByLabel("Category").selectOption({
                label: category,
            });

            await page.getByLabel("Supplier").selectOption({
                label: supplier,
            });

            await page
                .getByLabel("Description")
                .fill(
                    `Automated Playwright product for ${category} supplied by ${supplier}.`,
                );

            await page.getByLabel("Price").fill("99.99");

            await page.getByLabel("Quantity").fill("10");

            await page
                .getByLabel("Click to upload an image JPG")
                .setInputFiles("e2e/fixtures/mahadevv.png");

            // Submit product
            const createProductButton = page.getByRole("button", {
                name: "Create Product",
            });

            await expect(createProductButton).toBeEnabled({
                timeout: 10000,
            });

            await createProductButton.click();

            // Wait for Products page
            await expect(page).toHaveURL("http://127.0.0.1:8000/products", {
                timeout: 30000,
            });

            // Verify the exact product was created
            await expect(page.getByText(sku)).toBeVisible({
                timeout: 15000,
            });

            console.log(`Created: ${category} | ${supplier} | ${sku}`);
        }
    }

    console.log(
        `Successfully created ${categories.length * suppliers.length} products.`,
    );
});
