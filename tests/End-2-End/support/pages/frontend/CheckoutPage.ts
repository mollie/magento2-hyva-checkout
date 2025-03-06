import { Page } from '@playwright/test';

export default class CheckoutPage {
  async visit(page: Page) {
    await page.goto('/checkout');
  }
}
