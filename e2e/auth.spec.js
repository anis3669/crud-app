// @ts-check
import { test, expect } from "@playwright/test";

test("admin can login and logout successfully", async ({ page }) => {
    // Open login page
    await page.goto("http://127.0.0.1:8000/login");

    // Login
    await page.getByLabel("Email").fill("admin@gmail.com");
    await page.getByLabel("Password").fill("password");

    await page.getByRole("button", { name: "Sign In" }).click();

    // Verify successful login
    await expect(page).toHaveURL("http://127.0.0.1:8000/products", {
        timeout: 15000,
    });

    // Verify products page loaded
    await expect(page.getByRole("heading", { name: "Products" })).toBeVisible();

    // Logout
    await page.getByText("Admin User").click();
    await page.getByText("Logout").click();

    // Verify successful logout
    await expect(page).toHaveURL("http://127.0.0.1:8000/login", {
        timeout: 15000,
    });

    // Verify login page is visible again
    await expect(
        page.getByRole("heading", { name: "Welcome Back" }),
    ).toBeVisible();
});

test("admin cannot login with wrong email and password", async ({ page }) => {
    // Open login page
    await page.goto("http://127.0.0.1:8000/login");

    // Enter wrong credentials
    await page.getByLabel("Email").fill("wrong@gmail.com");
    await page.getByLabel("Password").fill("wrongpassword");

    // Attempt login
    await page.getByRole("button", { name: "Sign In" }).click();

    // Verify user remains on login page
    await expect(page).toHaveURL("http://127.0.0.1:8000/login", {
        timeout: 15000,
    });

    // Verify login form is still visible
    await expect(
        page.getByRole("heading", { name: "Welcome Back" }),
    ).toBeVisible();
});

test("admin cannot login with correct email and wrong password", async ({
    page,
}) => {
    // Open login page
    await page.goto("http://127.0.0.1:8000/login");

    // Enter correct email and wrong password
    await page.getByLabel("Email").fill("admin@gmail.com");
    await page.getByLabel("Password").fill("wrongpassword");

    // Attempt login
    await page.getByRole("button", { name: "Sign In" }).click();

    // Verify user remains on login page
    await expect(page).toHaveURL("http://127.0.0.1:8000/login", {
        timeout: 15000,
    });

    // Verify login form is still visible
    await expect(
        page.getByRole("heading", { name: "Welcome Back" }),
    ).toBeVisible();
});

test("admin cannot login with wrong email and correct password", async ({
    page,
}) => {
    // Open login page
    await page.goto("http://127.0.0.1:8000/login");

    // Enter wrong email and correct password
    await page.getByLabel("Email").fill("wrong@gmail.com");
    await page.getByLabel("Password").fill("password");

    // Attempt login
    await page.getByRole("button", { name: "Sign In" }).click();

    // Verify user remains on login page
    await expect(page).toHaveURL("http://127.0.0.1:8000/login", {
        timeout: 15000,
    });

    // Verify login form is still visible
    await expect(
        page.getByRole("heading", { name: "Welcome Back" }),
    ).toBeVisible();
});

test("admin cannot login with empty credentials", async ({ page }) => {
    // Open login page
    await page.goto("http://127.0.0.1:8000/login");

    // Attempt login without entering credentials
    await page.getByRole("button", { name: "Sign In" }).click();

    // Verify user remains on login page
    await expect(page).toHaveURL("http://127.0.0.1:8000/login", {
        timeout: 15000,
    });

    // Verify login form is still visible
    await expect(
        page.getByRole("heading", { name: "Welcome Back" }),
    ).toBeVisible();
});
