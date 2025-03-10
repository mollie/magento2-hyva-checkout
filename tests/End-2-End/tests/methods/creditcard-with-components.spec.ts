/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

import { test, expect, chromium } from '@playwright/test';
import CheckoutPaymentPage from "Pages/frontend/CheckoutPaymentPage";
import VisitCheckoutPaymentCompositeAction from "CompositeActions/VisitCheckoutPaymentCompositeAction";
import MollieHostedPaymentPage from "Pages/mollie/MollieHostedPaymentPage";
import ComponentsAction from "Actions/checkout/ComponentsAction";
import CheckoutSuccessPage from "Pages/frontend/CheckoutSuccessPage";
import OrdersPage from "Pages/backend/OrdersPage";
import BackendLogin from "Pages/backend/BackendLogin";

const checkoutPaymentPage = new CheckoutPaymentPage();
const visitCheckoutPayment = new VisitCheckoutPaymentCompositeAction();
const components = new ComponentsAction();
const mollieHostedPaymentPage = new MollieHostedPaymentPage(expect);
const checkoutSuccessPage = new CheckoutSuccessPage(expect);
const ordersPage = new OrdersPage();

test('[C4228286] Validate the submission of an order with Credit Card as payment method using Mollie Components and payment mark as "Paid"', async ({ page }) => {
  test.skip(!process.env.mollie_available_methods.includes('creditcard'), 'Skipping test as Credit Card is not available');

  await visitCheckoutPayment.visit(page);

  await checkoutPaymentPage.selectPaymentMethod(page, 'Credit Card');

  await components.fillComponentsForm(
    page,
    'Mollie Tester',
    '3782 822463 10005',
    '1230',
    '1234'
  );

  await checkoutPaymentPage.placeOrder(page);

  await mollieHostedPaymentPage.selectStatus(page, 'paid');

  await checkoutSuccessPage.assertThatOrderSuccessPageIsShown(page);

  if (checkoutPaymentPage.orderId) {
    await ordersPage.openOrderById(page, checkoutPaymentPage.orderId);
  } else if (mollieHostedPaymentPage.incrementId) {
    await ordersPage.openByIncrementId(page, mollieHostedPaymentPage.incrementId);
  } else {
    await ordersPage.openLatestOrder(page);
  }

  await ordersPage.assertOrderStatusIs(page, 'Processing');
});
