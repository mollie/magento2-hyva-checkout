/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

import { expect } from '@playwright/test';

// The checkout page itself: /checkout/index/index/step/<step>/ for the default checkout, /checkout for onepage.
// The success page (/checkout/onepage/success) and Mollie's hosted page don't match.
const CHECKOUT_PAGE = /\/checkout\/?(index\/index\/.*)?(\?.*)?$/;

export default class CheckoutPaymentPage {
  orderId = null;

  async selectPaymentMethod(page, name) {
    const option = page.locator('#payment-method-list > div').filter({ hasText: name });
    const input = option.locator('input[name="payment-method-option"]');

    // The radio uses wire:model, so a click only sticks once Magewire has booted and is not
    // mid-morph. Retry the click until the server re-render marks the method as active.
    await expect(async () => {
      await input.click({ force: true });
      await expect(option).toHaveClass(/\bactive\b/, { timeout: 3000 });
    }).toPass({ timeout: 30000 });

    await expect(input).toBeChecked();
  }

  async selectIssuer(page, issuer) {
    await page.locator(`text=${issuer}`).first().check();
  }

  async selectFirstAvailableIssuer(page) {
    await page.locator('.payment-method._active [name="issuer"]').first().waitFor({ state: 'visible' });
    await page.locator('.payment-method._active [name="issuer"]').first().check();
  }

  async pressPlaceOrderButton(page) {
    await page.locator('[x-bind="buttonPlaceOrder"]:not([disabled])').waitFor({ state: 'visible', timeout: 15000 });
    await this.clickPlaceOrderButton(page.locator('[x-bind="buttonPlaceOrder"]'));
  }

  // A forced click goes to the button's coordinates. Near the top of the viewport, the sticky header covers the
  // button and swallows the click, so center the button first.
  private async clickPlaceOrderButton(button) {
    await button.evaluate(element => element.scrollIntoView({ block: 'center' }));
    await button.click({ force: true });
  }

  async enterCouponCode(page, code = 'H20') {
    await page.click('text=Apply Discount Code');
    await page.locator('[name=discount_code]').waitFor({ state: 'visible' });
    await page.fill('[name=discount_code]', code);
    await page.click('.action.action-apply');

    await page.locator('.totals.discount').waitFor({ state: 'visible' });
  }

  async placeOrder(page) {
    const button = page.locator('[x-bind="buttonPlaceOrder"]');

    // The click handler is bound by Alpine after the button renders, so an early click can
    // be swallowed. Retry while the checkout is still shown and the button is clickable.
    try {
      await expect(async () => {
        if (CHECKOUT_PAGE.test(page.url()) && await button.isEnabled().catch(() => false)) {
          await this.clickPlaceOrderButton(button);
        }

        await expect(page).not.toHaveURL(CHECKOUT_PAGE, { timeout: 10000 });
      }).toPass({ timeout: 90000 });
    } catch (error) {
      const message = await page.locator('[role="alert"], .message.error').first().textContent().catch(() => null);

      if (message !== null && message.trim() !== '') {
        throw new Error(`Placing the order did not leave the checkout: ${message.trim()}`);
      }

      throw error;
    }
  }
}
