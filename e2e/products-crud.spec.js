// @ts-check
import { test, expect } from "@playwright/test";

const BASE_URL = "http://127.0.0.1:8000";

test("admin can view products", async ({ page }) => {
    // Login
    await page.goto(`${BASE_URL}/login`);

    await page.getByLabel("Email").fill("admin@gmail.com");
    await page.getByLabel("Password").fill("password");
    await page.getByRole("button", { name: "Sign In" }).click();

    // Verify successful login
    await expect(page).toHaveURL(`${BASE_URL}/products`, {
        timeout: 15000,
    });

    // Verify Products page
    await expect(page.getByRole("heading", { name: "Products" })).toBeVisible();

    // Verify product search
    await expect(
        page.getByRole("textbox", { name: "Search products..." }),
    ).toBeVisible();

    // Verify Add Product button
    await expect(
        page.getByRole("button", { name: "Add Product" }),
    ).toBeVisible();
});

test("admin can edit a product", async ({ page }) => {
    test.setTimeout(60000);

    // Login
    await page.goto(`${BASE_URL}/login`);

    await page.getByLabel("Email").fill("admin@gmail.com");
    await page.getByLabel("Password").fill("password");
    await page.getByRole("button", { name: "Sign In" }).click();

    // Verify successful login
    await expect(page).toHaveURL(`${BASE_URL}/products`, {
        timeout: 15000,
    });

    // Open Create Product page
    await page.goto(`${BASE_URL}/products/create`);

    await expect(
        page.getByRole("heading", { name: "Add Product" }),
    ).toBeVisible({
        timeout: 15000,
    });

    // Generate unique test data
    const timestamp = Date.now();
    const sku = `PW-EDIT-${timestamp}`;
    const originalName = `Playwright Edit Test ${timestamp}`;
    const updatedName = `Playwright Edit Test Updated ${timestamp}`;

    // Fill product details
    await page.getByLabel("Product Name").fill(originalName);

    await page.getByLabel("SKU").fill(sku);

    await page.getByLabel("Category").selectOption({
        label: "Electronics",
    });

    await page.getByLabel("Supplier").selectOption({
        label: "Rajesh Sharma",
    });

    await page
        .getByLabel("Description")
        .fill("Product created for Playwright edit testing.");

    await page.getByLabel("Price").fill("99.99");

    await page.getByLabel("Quantity").fill("10");

    // Upload product image
    await page
        .getByLabel("Click to upload an image JPG")
        .setInputFiles("e2e/fixtures/mahadevv.png");

    // Create product
    const createProductButton = page.getByRole("button", {
        name: "Create Product",
    });

    await expect(createProductButton).toBeEnabled();

    await createProductButton.click();

    // Verify product was created successfully
    await expect(page).toHaveURL(`${BASE_URL}/products`, {
        timeout: 30000,
    });

    await expect(page.getByText(sku)).toBeVisible({
        timeout: 15000,
    });

    // Locate the newly created product
    const productRow = page.getByRole("row", {
        name: new RegExp(originalName),
    });

    await expect(productRow).toBeVisible({
        timeout: 15000,
    });

    // Open Edit Product
    await productRow.getByLabel("Edit product").click();

    // Verify Edit Product page
    await expect(
        page.getByRole("heading", { name: "Edit Product" }),
    ).toBeVisible({
        timeout: 15000,
    });

    // Update product name
    await page.getByLabel("Product Name").fill(updatedName);

    // Update price
    await page.getByLabel("Price").fill("100.07");

    // Current Stock is readonly on the Edit Product page,
    // so quantity is intentionally not modified here.

    // Update category
    await page.getByLabel("Category").selectOption({
        label: "Clothing",
    });

    // Update supplier
    await page.getByLabel("Supplier").selectOption({
        label: "Suman Thapa",
    });

    // Update product
    const updateProductButton = page.getByRole("button", {
        name: "Update Product",
    });

    await expect(updateProductButton).toBeEnabled();

    await updateProductButton.click();

    // Verify returned to Products page
    await expect(page).toHaveURL(`${BASE_URL}/products`, {
        timeout: 30000,
    });

    // Verify updated product is visible
    await expect(page.getByText(updatedName)).toBeVisible({
        timeout: 15000,
    });

    // Verify original product name is no longer visible
    await expect(page.getByText(originalName)).not.toBeVisible();
});
test("admin can delete a product", async ({ page }) => {
    test.setTimeout(60000);

    // Login
    await page.goto(`${BASE_URL}/login`);

    await page.getByLabel("Email").fill("admin@gmail.com");
    await page.getByLabel("Password").fill("password");
    await page.getByRole("button", { name: "Sign In" }).click();

    // Verify successful login
    await expect(page).toHaveURL(`${BASE_URL}/products`, {
        timeout: 15000,
    });

    // Create a product specifically for deletion
    await page.goto(`${BASE_URL}/products/create`);

    await expect(
        page.getByRole("heading", { name: "Add Product" }),
    ).toBeVisible({
        timeout: 15000,
    });

    const timestamp = Date.now();
    const sku = `PW-DELETE-${timestamp}`;
    const productName = `Playwright Delete Test ${timestamp}`;

    await page.getByLabel("Product Name").fill(productName);

    await page.getByLabel("SKU").fill(sku);

    await page.getByLabel("Category").selectOption({
        label: "Electronics",
    });

    await page.getByLabel("Supplier").selectOption({
        label: "Rajesh Sharma",
    });

    await page
        .getByLabel("Description")
        .fill("Product created for Playwright delete testing.");

    await page.getByLabel("Price").fill("99.99");

    await page.getByLabel("Quantity").fill("10");

    await page
        .getByLabel("Click to upload an image JPG")
        .setInputFiles("e2e/fixtures/mahadevv.png");

    await page
        .getByRole("button", {
            name: "Create Product",
        })
        .click();

    // Verify product was created
    await expect(page).toHaveURL(`${BASE_URL}/products`, {
        timeout: 30000,
    });

    await expect(page.getByText(sku)).toBeVisible({
        timeout: 15000,
    });

    // Locate the product row
    const productRow = page.getByRole("row", {
        name: new RegExp(productName),
    });

    await expect(productRow).toBeVisible({
        timeout: 15000,
    });

    // Click Delete
    await productRow.getByLabel("Delete product").click();
    // Verify delete confirmation modal
    await expect(
        page.getByRole("heading", { name: "Delete Product?" }),
    ).toBeVisible({
        timeout: 10000,
    });

    await expect(
        page.getByRole("button", { name: "Delete Product", exact: true }),
    ).toBeVisible({
        timeout: 10000,
    });
    // Confirm deletion
    await page
        .getByRole("button", {
            name: "Delete Product",
            exact: true,
        })
        .click();

    // Verify returned to Products page
    await expect(page).toHaveURL(`${BASE_URL}/products`, {
        timeout: 30000,
    });

    // Verify delete confirmation modal is closed
    await expect(
        page.getByRole("heading", { name: "Delete Product?" }),
    ).not.toBeVisible({
        timeout: 15000,
    });

    // Verify deleted product is no longer visible in the products table
    await expect(
        page.getByRole("row", {
            name: new RegExp(productName),
        }),
    ).not.toBeVisible({
        timeout: 15000,
    });
});

// bulk delete
test("admin can bulk delete products", async ({ page }) => {
    test.setTimeout(90000);

    // Login
    await page.goto(`${BASE_URL}/login`);

    await page.getByLabel("Email").fill("admin@gmail.com");
    await page.getByLabel("Password").fill("password");
    await page.getByRole("button", { name: "Sign In" }).click();

    // Verify successful login
    await expect(page).toHaveURL(`${BASE_URL}/products`, {
        timeout: 15000,
    });

    // Create two products specifically for bulk deletion
    const timestamp = Date.now();

    const products = [
        {
            name: `Playwright Bulk Delete Test 1 ${timestamp}`,
            sku: `PW-BULK-DELETE-1-${timestamp}`,
        },
        {
            name: `Playwright Bulk Delete Test 2 ${timestamp}`,
            sku: `PW-BULK-DELETE-2-${timestamp}`,
        },
    ];

    // Create both products
    for (const product of products) {
        await page.goto(`${BASE_URL}/products/create`);

        await expect(
            page.getByRole("heading", { name: "Add Product" }),
        ).toBeVisible({
            timeout: 15000,
        });

        await page.getByLabel("Product Name").fill(product.name);

        await page.getByLabel("SKU").fill(product.sku);

        await page.getByLabel("Category").selectOption({
            label: "Electronics",
        });

        await page.getByLabel("Supplier").selectOption({
            label: "Rajesh Sharma",
        });

        await page
            .getByLabel("Description")
            .fill("Product created for Playwright bulk delete testing.");

        await page.getByLabel("Price").fill("99.99");

        await page.getByLabel("Quantity").fill("10");

        await page
            .getByLabel("Click to upload an image JPG")
            .setInputFiles("e2e/fixtures/mahadevv.png");

        const createProductButton = page.getByRole("button", {
            name: "Create Product",
        });

        await expect(createProductButton).toBeEnabled();

        await createProductButton.click();

        // Verify product was created
        await expect(page).toHaveURL(`${BASE_URL}/products`, {
            timeout: 30000,
        });

        await expect(page.getByText(product.sku)).toBeVisible({
            timeout: 15000,
        });
    }

    // Make sure both products are visible on the Products page
    await page.goto(`${BASE_URL}/products`);

    await expect(page.getByRole("heading", { name: "Products" })).toBeVisible({
        timeout: 15000,
    });

    // Locate both product rows
    const productRow1 = page.getByRole("row", {
        name: new RegExp(products[0].name),
    });

    const productRow2 = page.getByRole("row", {
        name: new RegExp(products[1].name),
    });

    await expect(productRow1).toBeVisible({
        timeout: 15000,
    });

    await expect(productRow2).toBeVisible({
        timeout: 15000,
    });

    // Select both products
    await productRow1.getByRole("checkbox").check();

    await productRow2.getByRole("checkbox").check();

    // Verify both checkboxes are selected
    await expect(productRow1.getByRole("checkbox")).toBeChecked();

    await expect(productRow2.getByRole("checkbox")).toBeChecked();

    // Click Trash / bulk delete button
    await page.getByRole("button", { name: "Trash" }).click();

    // Debug bulk delete modal
    await page.waitForTimeout(1000);

    console.log("Current URL:", page.url());
    console.log("Page text after clicking Trash:");
    console.log(await page.locator("body").innerText());

    await page.screenshot({
        path: "test-results/bulk-delete-modal.png",
        fullPage: true,
    });

    // Confirm bulk deletion
    await page
        .getByRole("button", {
            name: "Delete Products",
            exact: true,
        })
        .click();

    // Verify returned to Products page
    await expect(page).toHaveURL(`${BASE_URL}/products`, {
        timeout: 30000,
    });

    // Verify delete modal is closed
    await expect(
        page.getByRole("heading", { name: /Delete.*Products?/i }),
    ).not.toBeVisible({
        timeout: 15000,
    });

    // Verify both products were deleted
    await expect(
        page.getByRole("row", {
            name: new RegExp(products[0].name),
        }),
    ).not.toBeVisible({
        timeout: 15000,
    });

    await expect(
        page.getByRole("row", {
            name: new RegExp(products[1].name),
        }),
    ).not.toBeVisible({
        timeout: 15000,
    });
});
