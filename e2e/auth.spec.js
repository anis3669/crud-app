// @ts-check
import { test, expect } from "@playwright/test";

/**
 * Full end-to-end suite: Auth + Product CRUD + Stock + Invoice.
 *
 * Self-contained: it does NOT depend on seeded/random data (no "Electric Coffee Maker",
 * no products left over from other specs). Every product used here is created by the
 * test itself with a unique SKU, and cleaned up at the end.
 *
 * Requirements:  php artisan migrate:fresh --seed  (admin@gmail.com / password)
 *                php artisan serve  (http://127.0.0.1:8000)  +  npm run build (or npm run dev)
 *
 * Run:  npx playwright test e2e/auth.spec.js --project=chromium --workers=1
 */

const BASE_URL = "http://127.0.0.1:8000";
const ADMIN = { email: "admin@gmail.com", password: "password" };
const IMAGE = "e2e/fixtures/mahadevv.png";
const T = 20000; // default wait for UI/API-driven changes

test.use({ baseURL: BASE_URL });

// ---------- helpers ----------

/** First *visible* match (the app renders some lists twice: table + mobile cards). */
const visible = (locator) => locator.locator("visible=true").first();

async function login(page, email = ADMIN.email, password = ADMIN.password) {
    await page.goto("/login");
    await expect(
        page.getByRole("heading", { name: "Welcome Back" }),
    ).toBeVisible({ timeout: T });
    await page.locator("#email").fill(email);
    await page.locator("#password").fill(password);
    await page.getByRole("button", { name: "Sign In" }).click();
}

async function loginAsAdmin(page) {
    await login(page);
    await expect(page).toHaveURL(/\/products$/, { timeout: T });
    await expect(
        page.getByRole("heading", { name: "Products", exact: true }),
    ).toBeVisible({ timeout: T });
}

async function createProduct(
    page,
    {
        name,
        sku,
        price = "100",
        quantity = "10",
        category = "Electronics",
        supplier = "Rajesh Sharma",
    },
) {
    await page.goto("/products/create");
    await expect(
        page.getByRole("heading", { name: "Add Product" }),
    ).toBeVisible({ timeout: T });

    const nameInput = page.getByLabel("Product Name");
    await expect(nameInput).toBeEnabled({ timeout: T });
    await nameInput.fill(name);
    await page.getByLabel("SKU").fill(sku);
    await page.getByLabel("Category").selectOption({ label: category });
    await page.getByLabel("Supplier").selectOption({ label: supplier });
    await page.getByLabel("Description").fill(`Playwright product ${sku}`);
    await page.getByLabel("Price").fill(price);
    await page.getByLabel("Quantity").fill(quantity);
    await page.getByLabel("Click to upload an image JPG").setInputFiles(IMAGE);

    const submit = page.getByRole("button", { name: "Create Product" });
    await expect(submit).toBeEnabled({ timeout: T });
    await submit.click();

    await expect(page).toHaveURL(/\/products$/, { timeout: 30000 });
    await expect(visible(page.getByText(sku))).toBeVisible({ timeout: T });
}

/** Find a product row on /products by SKU (uses the search box so pagination never hides it). */
async function productRow(page, sku) {
    await page.goto("/products");
    await expect(
        page.getByRole("heading", { name: "Products", exact: true }),
    ).toBeVisible({ timeout: T });
    await page.getByPlaceholder("Search products...").fill(sku);
    const row = page.getByRole("row", { name: new RegExp(sku) });
    await expect(row).toBeVisible({ timeout: T });
    return row;
}

/** Open /inventory, search by SKU, return that product's row. */
async function stockRow(page, sku) {
    await page.goto("/inventory");
    await expect(
        page.getByRole("heading", { name: "Stock", exact: true }),
    ).toBeVisible({ timeout: T });
    await page
        .getByPlaceholder("Search by product, SKU, category, or supplier...")
        .fill(sku);
    const row = page.getByRole("row", { name: new RegExp(sku) });
    await expect(row).toBeVisible({ timeout: T });
    return row;
}

const stockCell = (row) =>
    row
        .getByRole("cell")
        .filter({ hasText: /In stock|Low stock|Out of stock/ });

async function readStock(row) {
    const text = await stockCell(row).innerText();
    return Number(text.match(/\d+/)?.[0]);
}

async function adjustStock(page, row, type, quantity, reason) {
    await row.getByRole("button", { name: "Adjust" }).click();
    await expect(
        page.getByRole("heading", { name: "Adjust stock" }),
    ).toBeVisible({ timeout: T });

    const radio = page.getByRole("radio", {
        name: type === "in" ? /Stock in/ : /Stock out/,
    });
    // radio inputs are visually hidden – click the wrapping label
    await radio.locator("..").click();
    await expect(radio).toBeChecked();

    await page.getByLabel("Quantity").fill(String(quantity));
    await page.getByLabel("Reason").fill(reason);
    await page.getByRole("button", { name: "Save adjustment" }).click();

    await expect(
        page.getByRole("heading", { name: "Adjust stock" }),
    ).not.toBeVisible({ timeout: T });
}

// =====================================================================
// 1. AUTH  (independent tests – each gets a fresh browser context)
// =====================================================================
test.describe("Auth", () => {
    test("admin can login and logout", async ({ page }) => {
        await loginAsAdmin(page);

        await page.getByText("Admin User").first().click();
        await page
            .locator("button:visible", { hasText: "Logout" })
            .first()
            .click();

        await expect(page).toHaveURL(/\/login$/, { timeout: T });
        await expect(
            page.getByRole("heading", { name: "Welcome Back" }),
        ).toBeVisible();
    });

    test("cannot login with wrong email and wrong password", async ({
        page,
    }) => {
        await login(page, "wrong@gmail.com", "wrongpassword");
        await expect(page).toHaveURL(/\/login$/);
        await expect(
            page.getByRole("heading", { name: "Welcome Back" }),
        ).toBeVisible();
    });

    test("cannot login with correct email and wrong password", async ({
        page,
    }) => {
        await login(page, ADMIN.email, "wrongpassword");
        await expect(page).toHaveURL(/\/login$/);
        await expect(
            page.getByRole("heading", { name: "Welcome Back" }),
        ).toBeVisible();
    });

    test("cannot login with wrong email and correct password", async ({
        page,
    }) => {
        await login(page, "wrong@gmail.com", ADMIN.password);
        await expect(page).toHaveURL(/\/login$/);
        await expect(
            page.getByRole("heading", { name: "Welcome Back" }),
        ).toBeVisible();
    });

    test("shows validation errors for empty credentials", async ({ page }) => {
        await page.goto("/login");
        await page.getByRole("button", { name: "Sign In" }).click();
        await expect(
            page.getByText("Please enter your email address."),
        ).toBeVisible();
        await expect(page).toHaveURL(/\/login$/);

        await page.locator("#email").fill(ADMIN.email);
        await page.getByRole("button", { name: "Sign In" }).click();
        await expect(
            page.getByText("Please enter your password."),
        ).toBeVisible();
        await expect(page).toHaveURL(/\/login$/);
    });

    test("protected pages redirect guests to login", async ({ page }) => {
        test.setTimeout(90000);
        for (const path of ["/products", "/inventory", "/invoices"]) {
            // don't wait for the full "load" event – the SPA redirects client-side
            await page.goto(path, { waitUntil: "domcontentloaded" });
            await expect(page).toHaveURL(/\/login$/, { timeout: 30000 });
        }
    });
});

// =====================================================================
// 2. PRODUCT CRUD  →  STOCK  →  INVOICE  (one shared, logged-in session)
//    Serial on purpose: each step builds on the product created in step 1.
// =====================================================================
test.describe.serial("Product, stock and invoice flow", () => {
    test.setTimeout(90000);

    const stamp = Date.now();
    const sku = `PW-${stamp}`;
    const originalName = `Playwright Item ${stamp}`;
    const updatedName = `Playwright Item Updated ${stamp}`;
    const customerName = `PW Customer ${stamp}`;

    /** @type {import('@playwright/test').Page} */
    let page;

    test.beforeAll(async ({ browser }) => {
        page = await browser.newPage({ baseURL: BASE_URL });
        await loginAsAdmin(page);
    });

    test.afterAll(async () => {
        await page?.close();
    });

    // ---------- Product CRUD ----------

    test("products page loads with search and add button", async () => {
        await page.goto("/products");
        await expect(
            page.getByRole("heading", { name: "Products", exact: true }),
        ).toBeVisible({ timeout: T });
        await expect(page.getByPlaceholder("Search products...")).toBeVisible();
        await expect(
            page.getByRole("button", { name: "Add Product" }).first(),
        ).toBeVisible();
    });

    test("create product", async () => {
        await createProduct(page, {
            name: originalName,
            sku,
            price: "100",
            quantity: "10",
        });

        const row = await productRow(page, sku);
        await expect(row).toContainText(originalName);
    });

    test("search finds the product by SKU", async () => {
        const row = await productRow(page, sku);
        await expect(row).toBeVisible();
        await expect(row).toContainText(originalName);
    });

    test("edit product", async () => {
        const row = await productRow(page, sku);
        await row.getByLabel("Edit product").click();

        await expect(
            page.getByRole("heading", { name: "Edit Product" }),
        ).toBeVisible({ timeout: T });

        await page.getByLabel("Product Name").fill(updatedName);
        await page.getByLabel("Price").fill("100");
        await page.getByLabel("Category").selectOption({ label: "Clothing" });
        await page
            .getByLabel("Supplier")
            .selectOption({ label: "Suman Thapa" });

        const update = page.getByRole("button", { name: "Update Product" });
        await expect(update).toBeEnabled();
        await update.click();

        await expect(page).toHaveURL(/\/products$/, { timeout: 30000 });

        const updatedRow = await productRow(page, sku);
        await expect(updatedRow).toContainText(updatedName);
        await expect(updatedRow).not.toContainText(originalName);
    });

    // ---------- Stock ----------

    test("stock page shows the new product with initial stock 10", async () => {
        const row = await stockRow(page, sku);
        expect(await readStock(row)).toBe(10);
    });

    test("stock out decreases stock", async () => {
        const row = await stockRow(page, sku);
        const before = await readStock(row);

        await adjustStock(page, row, "out", 4, "Playwright stock out");

        await expect
            .poll(async () => readStock(await stockRow(page, sku)), {
                timeout: T,
            })
            .toBe(before - 4);
    });

    test("stock in increases stock", async () => {
        const row = await stockRow(page, sku);
        const before = await readStock(row);

        await adjustStock(page, row, "in", 10, "Playwright stock in");

        await expect
            .poll(async () => readStock(await stockRow(page, sku)), {
                timeout: T,
            })
            .toBe(before + 10);
    });

    test("save adjustment is disabled without quantity and reason", async () => {
        const row = await stockRow(page, sku);
        await row.getByRole("button", { name: "Adjust" }).click();
        await expect(
            page.getByRole("heading", { name: "Adjust stock" }),
        ).toBeVisible({ timeout: T });

        await expect(
            page.getByRole("button", { name: "Save adjustment" }),
        ).toBeDisabled();

        await page.getByRole("button", { name: "Cancel" }).click();
        await expect(
            page.getByRole("heading", { name: "Adjust stock" }),
        ).not.toBeVisible({ timeout: T });
    });

    // ---------- Invoice ----------

    test("invoice form blocks creation without customer and items", async () => {
        await page.goto("/invoices/create");
        await expect(
            page.getByRole("heading", { name: "Create Invoice" }),
        ).toBeVisible({ timeout: T });

        await expect(
            page.getByRole("button", { name: "Create Invoice", exact: true }),
        ).toBeDisabled();
        await expect(
            page.getByRole("button", { name: "Add Product" }),
        ).toBeDisabled();
    });

    test("create invoice, totals are correct and stock is deducted", async () => {
        // stock right before invoice: 10 - 4 + 10 = 16
        const stockBefore = await readStock(await stockRow(page, sku));
        expect(stockBefore).toBe(16);

        await page.goto("/invoices/create");
        await expect(
            page.getByRole("heading", { name: "Create Invoice" }),
        ).toBeVisible({ timeout: T });

        // customer
        await page.locator("#customer-name").fill(customerName);
        await page.locator("#customer-email").fill("playwright@test.com");
        await page.locator("#customer-phone").fill("9800000000");

        // product (wait for the product list to load, filter to our product)
        await page.locator("#product-search").fill(updatedName);
        const option = page.locator("select option", { hasText: updatedName });
        await expect(option).toHaveCount(1, { timeout: T });
        const productId = await option.getAttribute("value");
        expect(productId).toBeTruthy();
        await page.locator("select").first().selectOption(String(productId));

        await page.locator("#product-quantity").fill("2");
        const add = page.getByRole("button", { name: "Add Product" });
        await expect(add).toBeEnabled();
        await add.click();

        // item is in the invoice
        await expect(visible(page.getByText(updatedName))).toBeVisible();

        // 2 x 100 = 200, +10% tax = 220
        await page.locator("#tax").fill("10");
        await page.locator("#discount").fill("0");
        await expect(page.getByText(/220\.00/).first()).toBeVisible();

        await page
            .getByRole("button", { name: "Create Invoice", exact: true })
            .click();

        await expect(page).toHaveURL(/\/invoices$/, { timeout: 30000 });
        await expect(
            page.getByRole("heading", { name: "Invoices", exact: true }),
        ).toBeVisible({ timeout: T });
        await expect(visible(page.getByText(customerName))).toBeVisible({
            timeout: T,
        });

        // stock reduced by the invoiced quantity
        await expect
            .poll(async () => readStock(await stockRow(page, sku)), {
                timeout: T,
            })
            .toBe(stockBefore - 2);
    });

    test("invoice detail page opens and lists the product", async () => {
        await page.goto("/invoices");
        const row = page.getByRole("row", { name: new RegExp(customerName) });
        await expect(row).toBeVisible({ timeout: T });

        await row.getByText("View").first().click();
        await expect(page).toHaveURL(/\/invoices\/\d+$/, { timeout: T });
        await expect(visible(page.getByText(updatedName))).toBeVisible({
            timeout: T,
        });
    });

    // ---------- Delete ----------

    test("delete product", async () => {
        const row = await productRow(page, sku);
        await row.getByLabel("Delete product").click();

        await expect(
            page.getByRole("heading", { name: "Delete Product?" }),
        ).toBeVisible({ timeout: T });
        await page
            .getByRole("button", { name: "Delete Product", exact: true })
            .click();

        await expect(
            page.getByRole("heading", { name: "Delete Product?" }),
        ).not.toBeVisible({ timeout: T });

        await page.getByPlaceholder("Search products...").fill(sku);
        await expect(
            page.getByRole("row", { name: new RegExp(sku) }),
        ).toHaveCount(0, { timeout: T });
    });

    test("bulk delete products", async () => {
        const items = [1, 2].map((n) => ({
            name: `Playwright Bulk ${n} ${stamp}`,
            sku: `PW-BULK-${n}-${stamp}`,
        }));

        for (const item of items) {
            await createProduct(page, { ...item, price: "50", quantity: "5" });
        }

        await page.goto("/products");
        await page.getByPlaceholder("Search products...").fill(`PW-BULK-`);
        for (const item of items) {
            const row = page.getByRole("row", { name: new RegExp(item.sku) });
            await expect(row).toBeVisible({ timeout: T });
            await row.getByRole("checkbox").check();
            await expect(row.getByRole("checkbox")).toBeChecked();
        }

        // "Trash" in the header only opens the trash page; the bulk action bar has its own "Delete"
        await page.getByRole("button", { name: "Delete", exact: true }).click();
        await expect(
            page.getByRole("button", { name: "Delete Products", exact: true }),
        ).toBeVisible({ timeout: T });
        await page
            .getByRole("button", { name: "Delete Products", exact: true })
            .click();

        for (const item of items) {
            await expect(
                page.getByRole("row", { name: new RegExp(item.sku) }),
            ).toHaveCount(0, { timeout: T });
        }
    });
});
