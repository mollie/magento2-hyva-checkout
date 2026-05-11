/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

export default class CheckoutPaymentPage {
  orderId = null;

  async selectPaymentMethod(page, name) {
    const methodRow = page.locator('#payment-method-list > div').filter({ hasText: name });
    const input = methodRow.locator('input');

    if (await input.isChecked()) {
        // If the input is already checked, we can skip clicking it again
        return;
    }

    const magewireSave = page.waitForResponse(
      (response) => response.url().includes('/magewire/post') && response.status() === 200
    );
    await input.click();
    await magewireSave;
  }

  async selectIssuer(page, issuer) {
    await page.locator(`text=${issuer}`).first().check();
  }

  async selectFirstAvailableIssuer(page) {
    await page.locator('.payment-method._active [name="issuer"]').first().waitFor({ state: 'visible' });
    await page.locator('.payment-method._active [name="issuer"]').first().check();
  }

  async pressPlaceOrderButton(page) {
    await page.getByText('Place Order').click();
  }

  async enterCouponCode(page, code = 'H20') {
    await page.click('text=Apply Discount Code');
    await page.locator('[name=discount_code]').waitFor({ state: 'visible' });
    await page.fill('[name=discount_code]', code);
    await page.click('.action.action-apply');

    await page.locator('.totals.discount').waitFor({ state: 'visible' });
  }

  async placeOrder(page) {
    await this.pressPlaceOrderButton(page);
  }
}
