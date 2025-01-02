/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

import {expect} from "@playwright/test";
import BackendLogin from "Pages/backend/BackendLogin";

const backendLogin = new BackendLogin();

export default class OrdersPage {
    async openLatestOrder(page) {
      let url = `/admin/sales/order/index/`;
      await page.goto(url);

      await this.checkIfLoggedIn(page, url);

      await page.locator('.data-grid-actions-cell').first().click();
    }

    async openOrderById(page, id: int) {
      let url = `/admin/sales/order/view/order_id/${id}`;
      await page.goto(url);

      await this.checkIfLoggedIn(page, url);
    }

    async openByIncrementId(page, incrementId: string) {
      let url = `/admin/sales/order/`;
      await page.goto(url);

      await this.checkIfLoggedIn(page, url);

      // Find a th.data-grid-th with the text "Purchase Date" and check if it has the class "_descend". If not, click it.
      const purchaseDate = await page.locator('th.data-grid-th', { hasText: 'Purchase Date' });
      if (!purchaseDate.locator('._descend')) {
        await purchaseDate.click();
      }

      const row = await page.locator('.data-row', { hasText: incrementId })
        .locator('a.action-menu-item', { hasText: 'View' });

      const href = await row.getAttribute('href');
      await page.goto(href);

      await page.getByText('Submit Comment').isVisible();
    }

    async callFetchStatus(page ) {
      // Sometimes Playwright is too fast.
      await page.waitForTimeout(2000);
      await expect(await page.getByRole('button', { name: 'Fetch Status' })).toBeVisible();
      await page.locator('.fetch-mollie-payment-status').click();

      await expect(await page.getByText('The latest status from Mollie')).toBeVisible({ timeout: 30000 });
    }

    async assertOrderStatusIs(page, status: string) {
      await expect(page.locator('#order_status')).toContainText(status);
    }

    async checkIfLoggedIn(page, urlToNavigateAfterLogin) {
      const issueElement = Array.from(await page.getByText('Report an issue').all()).length;
      const passwordElement = Array.from(await page.getByText('Forgot your password?').all()).length;

      if (issueElement === 0 && passwordElement === 1) {
        await backendLogin.login(page);

        await page.goto(urlToNavigateAfterLogin);
      }
    }
}
