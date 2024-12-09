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

    await this.expect(page.locator("#magewire-loader")).toBeHidden();
  }

  async waitForLoaderWithText(page: any, text: string) {
    await page.getByText(text).waitFor({ state: 'visible' });

    await this.waitForLoadersToBeHidden(page);
  }
}