// @ts-check
import { test, expect } from "@playwright/test";

test("admin can login successfully", async ({ page }) => {
    await page.goto("http://127.0.0.1:8000/login");

    await page.getByLabel("Email").fill("admin@gmail.com");
    await page.getByLabel("Password").fill("password");

    await page.getByRole("button", { name: "Sign In" }).click();

    await expect(page).toHaveURL("http://127.0.0.1:8000/products");
});
