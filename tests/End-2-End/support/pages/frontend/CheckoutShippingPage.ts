/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

import { Page, expect } from '@playwright/test';
import * as fs from 'fs/promises';
import * as path from 'path';
import HyvaCheckout from './HyvaCheckout';

const hyvaCheckout = new HyvaCheckout(expect);

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
    const selectedCountry = await page.getByLabel('Country').inputValue();

    for (const [field, value] of Object.entries(address.type)) {
      if (['Email address', 'Password'].includes(field) && this.shouldSkipUsername) {
        continue;
      }

      await page.locator('#hyva-checkout-container').getByText(field, { exact: true }).fill(value as string);
    }

    for (const [field, value] of Object.entries(address.select)) {
      if (field !== 'country_id') {
        await page.getByText(field).selectOption(value as string);
      }

      if (field === 'country_id' && value !== selectedCountry) {
        await page.getByText(field).selectOption(value as string);
        await hyvaCheckout.waitForLoaderWithText(page, 'Switching country');
      }
    }
  }

  async selectFirstAvailableShippingMethod(page: Page) {
    await page.locator('#shipping-method-list input').first().click();

    await hyvaCheckout.waitForLoaderWithText(page, 'Saving shipping method');
  }
}
