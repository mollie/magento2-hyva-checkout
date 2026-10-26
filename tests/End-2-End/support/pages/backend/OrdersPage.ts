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

      // The grid rows are loaded by a separate AJAX request that can be slow under load.
      const href = await row.getAttribute('href', { timeout: 30000 });
      await page.goto(href);

      await page.getByText('Submit Comment').isVisible();
    }

    async callFetchStatus(page ) {
      const fetchStatusButton = page.locator('.fetch-mollie-payment-status');
      await expect(fetchStatusButton).toBeVisible();

      // The click handler is bound when the fetch-order-status RequireJS module loads, so an
      // early click does nothing. Retry until the fetchOrderStatus request actually fires.
      await expect(async () => {
        const responsePromise = page.waitForResponse(
            response => response.url().includes('/mollie/action/fetchOrderStatus'),
            { timeout: 5000 }
        );

        if (await fetchStatusButton.isEnabled()) {
          await fetchStatusButton.click();
        }

        await responsePromise;
      }).toPass({ timeout: 60000 });

      // A successful fetch triggers location.reload(), which can abort our own navigation
      // with ERR_ABORTED. Retry until a navigation completes without being interrupted.
      const url = page.url();
      await expect(async () => {
        await page.goto(url, {waitUntil: 'domcontentloaded'});
        await page.waitForLoadState('load');
      }).toPass({ timeout: 30000 });
    }

    async assertOrderStatusIs(page, status: string, maxWaitSeconds = 120) {
      const orderStatusLocator = page.locator('#order_status');
      const deadline = Date.now() + (maxWaitSeconds * 1000);

      while (Date.now() < deadline) {
        const text = await orderStatusLocator.textContent();
        if (text && text.includes(status)) {
          await expect(orderStatusLocator).toContainText(status, { timeout: 100 });
          return;
        }

        await page.waitForTimeout(1000);
        await page.reload({waitUntil: 'load'});
      }

      throw new Error(`Order status "${status}" was not reached within ${maxWaitSeconds} seconds.`);
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
