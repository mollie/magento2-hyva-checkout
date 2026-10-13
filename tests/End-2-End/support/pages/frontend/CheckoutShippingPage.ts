/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

import { Page, expect } from '@playwright/test';
import * as fs from 'fs/promises';
import * as path from 'path';

export default class CheckoutShippingPage {
  private shouldSkipUsername = false;

  async skipUsername(page: Page) {
    this.shouldSkipUsername = true;
  }

  async fillDutchShippingAddress(page: Page) {
    const address = JSON.parse(await fs.readFile(path.join(__dirname, '../../../fixtures/dutch-shipping-address.json'), 'utf-8'));
    await this.fillShippingAddress(page, address);
  }

  async fillBelgianShippingAddress(page: Page) {
    const address = JSON.parse(await fs.readFile(path.join(__dirname, '../../../fixtures/belgian-shipping-address.json'), 'utf-8'));
    await this.fillShippingAddress(page, address);
  }

  async fillGermanShippingAddress(page: Page) {
    const address = JSON.parse(await fs.readFile(path.join(__dirname, '../../../fixtures/german-shipping-address.json'), 'utf-8'));
    await this.fillShippingAddress(page, address);
  }

  async fillFrenchShippingAddress(page: Page) {
    const address = JSON.parse(await fs.readFile(path.join(__dirname, '../../../fixtures/french-shipping-address.json'), 'utf-8'));
    await this.fillShippingAddress(page, address);
  }

  async fillShippingAddressUsingFixture(page: Page, fixture: string) {
    const address = JSON.parse(await fs.readFile(path.join(__dirname, `../../../fixtures/${fixture}`), 'utf-8'));
    await this.fillShippingAddress(page, address);
  }

  async fillShippingAddress(page: Page, address: any) {
    // The email must be committed before anything else touches the quote: the country switch
    // and the address form save each load and re-save the whole quote in parallel requests,
    // and one that loaded the quote before the email save commits writes customer_email back
    // as a stale null. Placing the order then fails with "Email has a wrong format".
    for (const [field, value] of this.typeableFields(address)) {
      if (field === 'Email address') {
        const input = page.locator('#hyva-checkout-container').getByText(field, { exact: true });
        await this.fillEmailAndWaitUntilSaved(page, input, value);
      }
    }

    for (const [field, value] of Object.entries(address.select)) {
      const select = page.getByText(field);

      await expect(async () => {
        await select.selectOption(value as string);
        await expect(select).toHaveValue(value as string, { timeout: 2000 });
      }).toPass({ timeout: 30000 });
    }

    for (const [field, value] of this.typeableFields(address)) {
      if (field === 'Email address') {
        continue;
      }

      const input = page.locator('#hyva-checkout-container').getByText(field, { exact: true });
      await input.fill(value);
      await input.blur();
    }

    // Magewire syncs every field to the server and a re-render can wipe values that were
    // typed while a previous sync was still processing. Verify and refill until all stick.
    await expect(async () => {
      for (const [field, value] of this.typeableFields(address)) {
        const input = page.locator('#hyva-checkout-container').getByText(field, { exact: true });

        if (await input.inputValue() !== value) {
          if (field === 'Email address') {
            await this.fillEmailAndWaitUntilSaved(page, input, value);
          } else {
            await input.fill(value);
            await input.blur();
          }
        }

        await expect(input).toHaveValue(value, { timeout: 2000 });
      }
    }).toPass({ timeout: 30000 });

    await page.locator('#shipping-region').waitFor({ state: 'hidden' });
  }

  // The wire:auto-save directive only schedules the save when its listener is already
  // attached and the value changed since focus, so clear and refill on every attempt and
  // retry until the guest-details request confirms the email reached the server.
  private async fillEmailAndWaitUntilSaved(page: Page, input: any, value: string) {
    await expect(async () => {
      const guestDetailsSaved = page.waitForResponse(
        (response) => this.isGuestDetailsUpdate(response) && response.status() === 200,
        { timeout: 5000 }
      );

      await input.fill('');
      await input.fill(value);
      await input.blur();

      await guestDetailsSaved;
    }).toPass({ timeout: 45000 });
  }

  // Hyvä Checkout 1.x (Magewire 1) posts each component to a URL ending in its name. Hyvä Checkout 3 (Magewire 3)
  // posts to one URL and names the components in the request body.
  private isGuestDetailsUpdate(response: any): boolean {
    if (response.url().includes('guest-details')) {
      return true;
    }

    try {
      const components = JSON.parse(response.request().postData() || '{}').components || [];

      return components.some((component: any) => JSON.parse(component.snapshot).memo?.name === 'checkout.guest-details');
    } catch (e) {
      return false;
    }
  }

  private typeableFields(address: any): [string, string][] {
    return Object.entries(address.type).filter(
      ([field]) => !(['Email address', 'Password'].includes(field) && this.shouldSkipUsername)
    ) as [string, string][];
  }

  async selectFirstAvailableShippingMethod(page: Page) {
    const option = page.locator('#shipping-method-list > div').first();
    const input = option.locator('input[name="shipping-method-option"]');

    // The radio uses wire:model, so a click only sticks once Magewire has booted and is not
    // mid-morph. Retry the click until the server re-render marks the method as active.
    await expect(async () => {
      await input.click({ force: true });
      await expect(option).toHaveClass(/\bactive\b/, { timeout: 3000 });
    }).toPass({ timeout: 30000 });
  }
}
