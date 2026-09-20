import { defineConfig } from '@playwright/test';
import { resolve } from 'node:path';

export default defineConfig({
  testDir: './tests/Browser',
  workers: 1,
  timeout: 45000,
  use: {
    baseURL: 'http://127.0.0.1:8011',
    channel: 'chrome',
    screenshot: 'only-on-failure',
    trace: 'retain-on-failure',
  },
  webServer: {
    command: 'php tests/Browser/prepare.php && php artisan serve --host=127.0.0.1 --port=8011',
    url: 'http://127.0.0.1:8011/login',
    reuseExistingServer: false,
    env: {
      APP_ENV: 'testing', DB_CONNECTION: 'sqlite',
      DB_DATABASE: resolve('storage/framework/testing/browser-audit.sqlite'),
      SESSION_DRIVER: 'file', CACHE_DRIVER: 'array', SESSION_LIFETIME: '30',
    },
  },
});
