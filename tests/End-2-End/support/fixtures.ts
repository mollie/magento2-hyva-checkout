/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

import { test as base } from '@playwright/test';

export type CheckoutType = 'default' | 'onepage';

type CheckoutOptions = {
  checkoutType: CheckoutType;
};

export const test = base.extend<CheckoutOptions>({
  checkoutType: ['default', { option: true }],
});

export function isOnepageCheckout(): boolean {
  return (test.info().project.use as Partial<CheckoutOptions>).checkoutType === 'onepage';
}

export { expect } from '@playwright/test';
