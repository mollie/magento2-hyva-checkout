/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

export default class MollieHostedPaymentPage {
  incrementId: string;

  constructor(public expect) {
  }

  async selectStatus(page, status) {
    const element = await page.locator('.copyable').first();
    const text = await element.getAttribute('data-clipboard-text');
    this.incrementId = text.replace('Order ', '');

    await this.expect(page).toHaveURL(/https:\/\/www\.mollie\.com\/checkout\//);

    await page.click(`input[value="${status}"]`);
    await page.click('.button');
  }

  async assertIsVisible(page) {
    await this.expect(page).toHaveURL(/https:\/\/www\.mollie\.com\/checkout\//);
  }

  async selectPaymentMethod(page, method) {
    await page.locator('.payment-method-list').getByText(method).click();
  }

  async selectFirstIssuer(page) {
    await page.locator('.payment-method-list [name="issuer"]').first().click();
  }
}
