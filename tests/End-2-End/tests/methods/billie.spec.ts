/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

import { test, expect } from '@playwright/test';
import CheckoutPaymentPage from "Pages/frontend/CheckoutPaymentPage";
import VisitCheckoutPaymentCompositeAction from "CompositeActions/VisitCheckoutPaymentCompositeAction";
import MollieHostedPaymentPage from "Pages/mollie/MollieHostedPaymentPage";
import CheckoutSuccessPage from "Pages/frontend/CheckoutSuccessPage";
import OrdersPage from "Pages/backend/OrdersPage";
import CartPage from "Pages/frontend/CartPage";

const checkoutPaymentPage = new CheckoutPaymentPage();
const visitCheckoutPayment = new VisitCheckoutPaymentCompositeAction();
const mollieHostedPaymentPage = new MollieHostedPaymentPage(expect);
const checkoutSuccessPage = new CheckoutSuccessPage(expect);
const ordersPage = new OrdersPage();
const cartPage = new CartPage();

test.describe('Check that extra validations for Billie are working as expected', () => {
    test('[C4228955] Validate that a company is required to place an order with Billie', async ({ page }) => {
        test.skip(!process.env.mollie_available_methods.includes('billie'), 'Skipping test as Billie is not available');

        await visitCheckoutPayment.visit(page, 'german-shipping-address-without-company.json');

        await checkoutPaymentPage.selectPaymentMethod(page, 'Billie');
        await checkoutPaymentPage.pressPlaceOrderButton(page);

        await expect(page.locator('.messages.container .message.error')).toHaveText('A billing organization name is required for this payment method.');
        await expect(page.locator('.messages.container .message.error')).toBeVisible();

        await page.reload();

        // We should stay on the checkout page
        await expect(page).toHaveURL(/checkout\/index\/index\/step\/payment\//);
    });
})

test.describe('Check that Billie behaves as expected', () => {
    const testCases = [
        {status: 'authorized', orderStatus: 'Processing', title: '[C4228956] Validate the submission of an order with Billie as payment method and payment mark as "Authorized"'},
        {status: 'failed', orderStatus: 'Canceled', title: '[C4228957] Validate the submission of an order with Billie as payment method and payment mark as "Failed"'},
        {status: 'expired', orderStatus: 'Canceled', title: '[C4228958] Validate the submission of an order with Billie as payment method and payment mark as "Expired"'},
        {status: 'canceled', orderStatus: 'Canceled', title: '[C4228959] Validate the submission of an order with Billie as payment method and payment mark as "Cancelled"'},
    ];

    for (const testCase of testCases) {
        test(testCase.title, async ({ page }) => {
            test.skip(!process.env.mollie_available_methods.includes('billie'), 'Skipping test as Billie is not available');

            await visitCheckoutPayment.visit(page, 'DE');

            await checkoutPaymentPage.selectPaymentMethod(page, 'Billie');
            await checkoutPaymentPage.placeOrder(page);

            await mollieHostedPaymentPage.selectStatus(page, testCase.status);

            if (testCase.status === 'paid') {
                await checkoutSuccessPage.assertThatOrderSuccessPageIsShown(page);
            }

            if (testCase.status === 'canceled') {
                await cartPage.assertCartPageIsShown(page);
            }

            if (checkoutPaymentPage.orderId) {
                await ordersPage.openOrderById(page, checkoutPaymentPage.orderId);
            } else if (mollieHostedPaymentPage.incrementId) {
                await ordersPage.openByIncrementId(page, mollieHostedPaymentPage.incrementId);
            } else {
                await ordersPage.openLatestOrder(page);
            }

            if (testCase.status === 'expired') {
                await ordersPage.callFetchStatus(page);
            }

            await ordersPage.assertOrderStatusIs(page, testCase.orderStatus);
        });
    }
});
