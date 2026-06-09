/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

import { test, expect } from '@playwright/test';
import ProductPage from "Pages/frontend/ProductPage";

const productPage = new ProductPage();

test('[C4251741] Can add multiple products to the cart', async ({ page }) => {
    await productPage.openProduct(page, 4);
    const product1Name = await productPage.addSimpleProductToCart(page, 1);

    await productPage.openProduct(page, 5);
    const product2Name = await productPage.addSimpleProductToCart(page, 1);

    await page.goto('checkout/cart');

    await expect(page.locator('#shopping-cart-table .cart.item')).toHaveCount(2);

    await page.locator('#shopping-cart-table').getByText(product1Name).waitFor({ state: 'visible' });
    await page.locator('#shopping-cart-table').getByText(product2Name).waitFor({ state: 'visible' });
});
