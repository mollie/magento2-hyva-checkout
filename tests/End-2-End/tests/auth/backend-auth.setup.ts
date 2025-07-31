/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

import { test as setup, expect } from '@playwright/test';
import BackendLogin from 'Pages/backend/BackendLogin';
import path from 'path';
import fs from 'fs';

const backendLogin = new BackendLogin();

const authFile = path.join(__dirname, '../../.auth/backend.json');

setup('[C4212591] authenticate', async ({ page }) => {
  if (fs.existsSync(authFile)) {
    return;
  }

  await backendLogin.login(page);

  await page.context().storageState({ path: authFile });
});
