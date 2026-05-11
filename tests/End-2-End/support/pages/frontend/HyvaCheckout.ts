/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

export default class HyvaCheckout {
  private expect: any;

  constructor(expect: any) {
    this.expect = expect;
  }

  async waitForLoadersToBeHidden(page: any) {
    await page.waitForFunction(() =>
      Array.from(
        document.querySelectorAll(".magewire\\.notification\\.message"),
      ).every((element) => element.offsetParent === null),
    );

    await this.expect(page.locator("#magewire-loader-overlay")).toBeHidden();
  }

  async waitForLoaderWithText(page: any, text: string) {
    // In Hyvä Checkout V3, actions may complete before the loader becomes visible
    await page.getByText(text).waitFor({ state: 'visible', timeout: 3000 }).catch(() => {});

    await this.waitForLoadersToBeHidden(page);
  }
}
