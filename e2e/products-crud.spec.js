// @ts-check
import { test, expect } from "@playwright/test";

const BASE_URL = "http://127.0.0.1:8000";

test.use({ baseURL: BASE_URL });

const ADMIN = {
    email: "admin@gmail.com",
    password: "password",
};

const IMAGE = "e2e/fixtures/mahadevv.png";

const T = 20000;

// LOGIN HELPER

async function login(page) {
    await page.goto("/login");

    await page.getByLabel("Email").fill(ADMIN.email);
    await page.getByLabel("Password").fill(ADMIN.password);

    await page.getByRole("button", { name: "Sign In" }).click();

    await expect(page).toHaveURL(/\/products$/, {
        timeout: 15000,
    });

    await expect(
        page.getByRole("heading", {
            name: "Products",
            exact: true,
        }),
    ).toBeVisible({
        timeout: T,
    });
}

// CREATE PRODUCT HELPER

async function createProduct(
    page,
    {
        name,
        sku,
        price = "99.99",
        quantity = "10",
        category = "Electronics",
        supplier = "Rajesh Sharma",
    },
) {
    await page.goto("/products/create");

    await expect(
        page.getByRole("heading", {
            name: "Add Product",
        }),
    ).toBeVisible({
        timeout: T,
    });

    await page.getByLabel("Product Name").fill(name);

    await page.getByLabel("SKU").fill(sku);

    await page.getByLabel("Category").selectOption({
        label: category,
    });

    await page.getByLabel("Supplier").selectOption({
        label: supplier,
    });

    await page
        .getByLabel("Description")
        .fill(`Product created for Playwright testing - ${sku}`);

    await page.getByLabel("Price").fill(price);

    await page.getByLabel("Quantity").fill(quantity);

    await page.getByLabel("Click to upload an image JPG").setInputFiles(IMAGE);

    const createButton = page.getByRole("button", {
        name: "Create Product",
    });

    await expect(createButton).toBeEnabled();

    await createButton.click();

    await expect(page).toHaveURL(/\/products$/, {
        timeout: 30000,
    });

    await expect(page.getByText(sku)).toBeVisible({
        timeout: T,
    });
}

// 1. VIEW PRODUCTS

test("admin can view products", async ({ page }) => {
    await login(page);

    // Verify Products heading
    await expect(
        page.getByRole("heading", {
            name: "Products",
            exact: true,
        }),
    ).toBeVisible();

    // Verify search
    await expect(page.getByPlaceholder("Search products...")).toBeVisible();

    // Verify Add Product button
    await expect(
        page.getByRole("button", {
            name: "Add Product",
        }),
    ).toBeVisible();
});

// 2. CREATE PRODUCT

test("admin can create a product", async ({ page }) => {
    test.setTimeout(60000);

    await login(page);

    const timestamp = Date.now();

    const productName = `Playwright Create Test ${timestamp}`;
    const sku = `PW-CREATE-${timestamp}`;

    await createProduct(page, {
        name: productName,
        sku,
        price: "99.99",
        quantity: "10",
        category: "Electronics",
        supplier: "Rajesh Sharma",
    });

    // Verify product is visible
    const productRow = page.getByRole("row", {
        name: new RegExp(productName),
    });

    await expect(productRow).toBeVisible({
        timeout: T,
    });

    await expect(productRow).toContainText(productName);
    await expect(productRow).toContainText(sku);
});

// 3. SEARCH PRODUCT

test("admin can search for a product", async ({ page }) => {
    test.setTimeout(60000);

    await login(page);

    const timestamp = Date.now();

    const productName = `Playwright Search Test ${timestamp}`;
    const sku = `PW-SEARCH-${timestamp}`;

    await createProduct(page, {
        name: productName,
        sku,
    });

    // Search by SKU
    const search = page.getByPlaceholder("Search products...");

    await search.fill(sku);

    // Locate product row
    const productRow = page.getByRole("row", {
        name: new RegExp(sku),
    });

    await expect(productRow).toBeVisible({
        timeout: T,
    });

    await expect(productRow).toContainText(productName);
    await expect(productRow).toContainText(sku);
});

// 4. EDIT PRODUCT

test("admin can edit a product", async ({ page }) => {
    test.setTimeout(60000);

    await login(page);

    const timestamp = Date.now();

    const sku = `PW-EDIT-${timestamp}`;

    const originalName = `Playwright Edit Test ${timestamp}`;

    const updatedName = `Playwright Edit Test Updated ${timestamp}`;

    // Create product
    await createProduct(page, {
        name: originalName,
        sku,
        price: "99.99",
        quantity: "10",
        category: "Electronics",
        supplier: "Rajesh Sharma",
    });

    // Search product
    await page.getByPlaceholder("Search products...").fill(sku);

    const productRow = page.getByRole("row", {
        name: new RegExp(sku),
    });

    await expect(productRow).toBeVisible({
        timeout: T,
    });

    // Open Edit Product
    await productRow.getByLabel("Edit product").click();

    await expect(
        page.getByRole("heading", {
            name: "Edit Product",
        }),
    ).toBeVisible({
        timeout: T,
    });

    // Update name
    await page.getByLabel("Product Name").fill(updatedName);

    // Update price
    await page.getByLabel("Price").fill("100.07");

    // Update category
    await page.getByLabel("Category").selectOption({
        label: "Clothing",
    });

    // Update supplier
    await page.getByLabel("Supplier").selectOption({
        label: "Suman Thapa",
    });

    // Submit update
    const updateButton = page.getByRole("button", {
        name: "Update Product",
    });

    await expect(updateButton).toBeEnabled();

    await updateButton.click();

    // Verify returned to Products
    await expect(page).toHaveURL(/\/products$/, {
        timeout: 30000,
    });

    // Search updated product
    await page.getByPlaceholder("Search products...").fill(sku);

    const updatedRow = page.getByRole("row", {
        name: new RegExp(sku),
    });

    await expect(updatedRow).toBeVisible({
        timeout: T,
    });

    // Verify updated name
    await expect(updatedRow).toContainText(updatedName);

    // Verify old name is gone
    await expect(updatedRow).not.toContainText(originalName);
});

// 5. SINGLE DELETE PRODUCT

test("admin can delete a product", async ({ page }) => {
    test.setTimeout(60000);

    await login(page);

    const timestamp = Date.now();

    const sku = `PW-DELETE-${timestamp}`;

    const productName = `Playwright Delete Test ${timestamp}`;

    // Create product
    await createProduct(page, {
        name: productName,
        sku,
        price: "99.99",
        quantity: "10",
        category: "Electronics",
        supplier: "Rajesh Sharma",
    });

    // Search product
    await page.getByPlaceholder("Search products...").fill(sku);

    const productRow = page.getByRole("row", {
        name: new RegExp(sku),
    });

    await expect(productRow).toBeVisible({
        timeout: T,
    });

    // Click Delete
    await productRow.getByLabel("Delete product").click();

    // Verify confirmation modal
    await expect(
        page.getByRole("heading", {
            name: "Delete Product?",
        }),
    ).toBeVisible({
        timeout: 10000,
    });

    // Verify confirmation button
    await expect(
        page.getByRole("button", {
            name: "Delete Product",
            exact: true,
        }),
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

    // Verify modal closed
    await expect(
        page.getByRole("heading", {
            name: "Delete Product?",
        }),
    ).not.toBeVisible({
        timeout: T,
    });

    // Search again
    await page.getByPlaceholder("Search products...").fill(sku);

    // Verify product is gone from Products
    await expect(
        page.getByRole("row", {
            name: new RegExp(sku),
        }),
    ).toHaveCount(0, {
        timeout: T,
    });
});

// 6. BULK DELETE PRODUCTS

test("admin can bulk delete products", async ({ page }) => {
    test.setTimeout(90000);

    await login(page);

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

    // Create both produ

    for (const product of products) {
        await createProduct(page, {
            name: product.name,
            sku: product.sku,
            price: "99.99",
            quantity: "10",
            category: "Electronics",
            supplier: "Rajesh Sharma",
        });

    // Return to Products p

    await page.goto("/products");

    await expect(
        page.getByRole("heading", {
            name: "Products",
            exact: true,
        }),
    ).toBeVisible({
        timeout: T,
    }
    // Locate both produ

    const productRow1 = page.getByRole("row", {
        name: new RegExp(products[0].name),
    });

    const productRow2 = page.getByRole("row", {
        name: new RegExp(products[1].name),
    });

    await expect(productRow1).toBeVisible({
        timeout: T,
    });

    await expect(productRow2).toBeVisible({
        timeout: T,
    }
    // Select first prod

    const checkbox1 = productRow1.getByRole("checkbox", {
        name: `Select ${products[0].name}`,
    });

    await checkbox1.check();

    await expect(checkbox1).toBeChecked(
    // Select second prod

    const checkbox2 = productRow2.getByRole("checkbox", {
        name: `Select ${products[1].name}`,
    });

    await checkbox2.check();

    await expect(checkbox2).toBeChecked(
    // Verify bulk actions

    await expect(page.getByText("Products selected")).toBeVisible({
        timeout: T,
    });

    // Verify selected count
    await expect(
        page
            .locator("div")
            .filter({
                hasText: "Products selected",
            })
            .first(),
    ).toContainText("2"
    // Click bulk Del

    await page
        .getByRole("button", {
            name: "Delete",
            exact: true,
        })
        .click(
    // Verify bulk delete confirmat

    await expect(
        page.getByRole("button", {
            name: "Delete Products",
            exact: true,
        }),
    ).toBeVisible({
        timeout: 10000,
    }
    // Confirm bulk delet

    await page
        .getByRole("button", {
            name: "Delete Products",
            exact: true,
        })
        .click(
    // Verify products are removed from Products p

    await expect(page).toHaveURL(/\/products$/, {
        timeout: 30000,
    });

    await expect(
        page.getByRole("row", {
            name: new RegExp(products[0].sku),
        }),
    ).toHaveCount(0, {
        timeout: T,
    });

    await expect(
        page.getByRole("row", {
            name: new RegExp(products[1].sku),
        }),
    ).toHaveCount(0, {
        timeout: T,
    });
});
