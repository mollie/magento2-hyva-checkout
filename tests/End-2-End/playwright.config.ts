/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

import {defineConfig, devices, type Project} from '@playwright/test';
import type {CheckoutType} from './support/fixtures';

/**
 * Read environment variables from file.
 * https://github.com/motdotla/dotenv
 */
// import dotenv from 'dotenv';
// import path from 'path';
// dotenv.config({ path: path.resolve(__dirname, '.env') });

const isNoApiKeyTest = process.env.NO_API_KEY_TEST === 'true';

// Each checkout type runs against its own store view (locally) or its own CI job. Only the
// checkout types with a base URL get projects, so CI can run one type per matrix entry.
const checkoutBaseUrls: Record<CheckoutType, string | undefined> = {
  default: process.env.BASE_URL ?? (process.env.ONEPAGE_BASE_URL ? undefined : 'https://mollie.test/'),
  onepage: process.env.ONEPAGE_BASE_URL,
};

const checkoutProjects: Project[] = Object.entries(checkoutBaseUrls)
  .filter((entry): entry is [CheckoutType, string] => entry[1] !== undefined)
  .map(([checkoutType, baseURL]) => ({
    name: checkoutType,
    use: {
      ...devices['Desktop Chrome'],
      baseURL,
      checkoutType,
      storageState: '.auth/backend.json',

      // These settings are required to be able to work with Mollie Components
      bypassCSP: true,
      launchOptions: {
        args: ['--disable-web-security'],
      },
    },

    dependencies: ['setup'],
  }));

const defaultBaseUrl = Object.values(checkoutBaseUrls).find(url => url !== undefined);

const testRailOptions = {
  // Whether to add <properties> with all annotations; default is false
  embedAnnotationsAsProperties: true,
  // Where to put the report.
  outputFile: './test-results/junit-report.xml'
};

/**
 * See https://playwright.dev/docs/test-configuration.
 */
export default defineConfig({
  globalSetup: require.resolve('./global-setup.ts'),

  testDir: './tests/',
  /* Run tests in files in parallel */
  fullyParallel: true,
  /* Stop execution after 2 failing tests */
  maxFailures: 2,
  /* Fail the build on CI if you accidentally left test.only in the source code. */
  forbidOnly: !!process.env.CI,
  /* Retry on CI only */
  retries: process.env.CI ? 2 : 0,
  /* Opt out of parallel tests on CI. */
  workers: process.env.CI ? 1 : undefined,
  /* Don't run the whole test suite but fail fast */
  maxFailures: process.env.CI ? 3 : undefined,
  /* Reporter to use. See https://playwright.dev/docs/test-reporters */
  reporter: process.env.CI ?
    [['html', { open: 'never' }], ['list'], ['github'], ['junit', testRailOptions]] :
    [['html', { open: 'never' }]],

  /* Shared settings for all the projects below. See https://playwright.dev/docs/api/class-testoptions. */
  use: {
    /* Base URL to use in actions like `await page.goto('/')`. */
    baseURL: defaultBaseUrl,

    /* Collect trace when retrying the failed test. See https://playwright.dev/docs/trace-viewer */
    trace: 'retain-on-failure',

    ignoreHTTPSErrors: true,

    /* Fail locator actions quickly instead of waiting out the full test timeout. */
    actionTimeout: 10_000,
  },

  timeout: 150000,

  testIgnore: /.*no-api-key\.spec\.ts/,

  grep: isNoApiKeyTest ? /@no-api-key/ : undefined,
  grepInvert: isNoApiKeyTest ? undefined : /@no-api-key/,

  /* Configure projects for major browsers */
  projects: [
    { name: 'setup', testMatch: /.*\.setup\.ts/ },

    ...checkoutProjects,

    {
      name: 'no-api-key',
      testMatch: /.*no-api-key\.spec\.ts/,
      testIgnore: 'disable this option',
      use: {
        ...devices['Desktop Chrome'],
        viewport: { width: 1280, height: 1440 },
      }
    }

    // {
    //   name: 'firefox',
    //   use: { ...devices['Desktop Firefox'] },
    // },
    //
    // {
    //   name: 'webkit',
    //   use: { ...devices['Desktop Safari'] },
    // },

    /* Test against mobile viewports. */
    // {
    //   name: 'Mobile Chrome',
    //   use: { ...devices['Pixel 5'] },
    // },
    // {
    //   name: 'Mobile Safari',
    //   use: { ...devices['iPhone 12'] },
    // },

    /* Test against branded browsers. */
    // {
    //   name: 'Microsoft Edge',
    //   use: { ...devices['Desktop Edge'], channel: 'msedge' },
    // },
    // {
    //   name: 'Google Chrome',
    //   use: { ...devices['Desktop Chrome'], channel: 'chrome' },
    // },
  ],

  /* Run your local dev server before starting the tests */
  // webServer: {
  //   command: 'npm run start',
  //   url: 'http://127.0.0.1:3000',
  //   reuseExistingServer: !process.env.CI,
  // },
});
