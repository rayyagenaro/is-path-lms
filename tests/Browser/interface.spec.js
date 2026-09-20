import { test, expect } from '@playwright/test';

async function login(page) {
  await page.goto('/login');
  await page.getByLabel('Email', { exact: true }).fill('rani@ispath.id');
  await page.locator('input[name="password"]').fill('password');
  await page.locator('button[type="submit"]').click();
  await expect(page).toHaveURL(/dashboard/);
}

for (const width of [375, 768, 1024, 1440]) {
  test(`workspace, evidence and progress at ${width}px`, async ({ page }) => {
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await page.setViewportSize({ width, height: 1000 });
    await login(page);
    for (const path of ['/dashboard', '/competencies', '/assessments', '/courses', '/careers', '/career-profile']) {
      await page.goto(path);
      await expect(page.locator('main')).toBeVisible();
      await expect.poll(() => page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1)).toBe(true);
      const mismatch = await page.locator('[role="progressbar"]:has(> i)').evaluateAll(bars => bars.filter(bar => {
        const fill = bar.querySelector('i');
        const expected = Math.min(100, Math.max(0, Number(bar.getAttribute('aria-valuenow')))) / 100;
        return Math.abs(Number(fill.style.getPropertyValue('--progress-scale')) - expected) > 0.001;
      }).length);
      expect(mismatch, `progress on ${path}`).toBe(0);
      await page.screenshot({ path: `test-results/audit-${width}-${path.slice(1)}.png`, fullPage: true });
    }
    await page.goto('/competencies');
    await page.getByRole('link', { name: /Lihat sumber bukti/ }).first().click();
    await expect(page.getByRole('heading', { name: 'Riwayat bukti' })).toBeVisible();
    await expect.poll(() => page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1)).toBe(true);
    await page.locator('summary').click();
    await expect(page.getByText('Penguasaan adalah rata-rata', { exact: false })).toBeVisible();
    expect(errors).toEqual([]);
  });
}

test('progress fills remain visible when IntersectionObserver never fires', async ({ page }) => {
  await page.addInitScript(() => {
    window.IntersectionObserver = class { observe() {} unobserve() {} disconnect() {} };
  });
  await login(page);
  await page.goto('/competencies');
  const bar = page.locator('[role="progressbar"]').filter({ has: page.locator('i') }).first();
  await expect.poll(() => bar.locator('i').evaluate(el => Number(getComputedStyle(el).transform.match(/matrix\(([^,]+)/)?.[1]))).toBeGreaterThan(0);
});

test('new account is prompted to complete pre-assessment before learning', async ({ page }) => {
  const runId = Date.now();
  await page.setViewportSize({ width: 375, height: 812 });
  await page.goto('/register');
  await page.getByLabel('Nama lengkap').fill('Nadia Prameswari');
  await page.getByLabel('Email', { exact: true }).fill(`nadia.browser.${runId}@kampus.ac.id`);
  await page.getByLabel('NIM').fill(String(runId));
  await page.getByLabel('Tahun Angkatan').fill('2026');
  await page.getByLabel('Kata Sandi', { exact: true }).fill('belajar123');
  await page.getByLabel('Ulangi Kata Sandi').fill('belajar123');
  await page.getByRole('button', { name: 'Buat akun mahasiswa' }).click();
  await expect(page).toHaveURL(/onboarding\/career-role/);
  const dialog = page.getByRole('dialog');
  await expect(dialog).toBeVisible();
  await expect(dialog.getByRole('heading', { name: /Selesaikan pre-assessment/ })).toBeVisible();
  await dialog.getByRole('button', { name: 'Kerjakan sekarang' }).click();
  await expect(page).toHaveURL(/assessments\/attempts\/\d+/);
  await expect(page.getByText('Nomor soal', { exact: true })).toBeVisible();
  await expect.poll(() => page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1)).toBe(true);
});

test('keyboard focus and reduced motion remain usable', async ({ page }) => {
  await page.emulateMedia({ reducedMotion: 'reduce' });
  await page.setViewportSize({ width: 812, height: 375 });
  await page.goto('/login');
  await page.keyboard.press('Tab');
  const focused = page.locator(':focus');
  await expect(focused).toBeVisible();
  await expect.poll(() => focused.evaluate(el => Number.parseFloat(getComputedStyle(el).outlineWidth))).toBeGreaterThanOrEqual(2);
  await login(page);
  await expect.poll(() => page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1)).toBe(true);
  await expect(page.locator('main')).toBeVisible();
});
