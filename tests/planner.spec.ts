import { test, expect, Page } from '@playwright/test';

// App credentials (HTTP Basic Auth is handled via TEST_URL env var for production)
const APP_USERNAME = 'sruli';
const APP_PASSWORD = 'ContentlySleeveVoyage#@!';

// Helper to login to the app
async function login(page: Page) {
  await page.goto('/');
  await page.fill('input[name="username"]', APP_USERNAME);
  await page.fill('input[name="password"]', APP_PASSWORD);
  await page.click('button[type="submit"]');
  await page.waitForSelector('#pretty-date');
  // Wait for JS to initialize
  await page.waitForTimeout(1000);
}

test.describe('Authentication', () => {
  test('should show login form', async ({ page }) => {
    await page.goto('/');
    await expect(page.locator('input[name="username"]')).toBeVisible();
    await expect(page.locator('input[name="password"]')).toBeVisible();
  });

  test('should reject invalid credentials', async ({ page }) => {
    await page.goto('/');
    await page.fill('input[name="username"]', 'wronguser');
    await page.fill('input[name="password"]', 'wrongpass');
    await page.click('button[type="submit"]');
    await expect(page.locator('text=Invalid')).toBeVisible();
  });

  test('should login with valid credentials', async ({ page }) => {
    await login(page);
    await expect(page.locator('#pretty-date')).toBeVisible();
    await expect(page.locator('text=Gapaika Planner')).toBeVisible();
  });

  test('should have CSRF token after login', async ({ page }) => {
    await login(page);
    const csrfToken = await page.locator('meta[name="csrf-token"]').getAttribute('content');
    expect(csrfToken).toBeTruthy();
    expect(csrfToken!.length).toBeGreaterThan(20);
  });

  test('should logout successfully', async ({ page }) => {
    await login(page);
    await page.click('#user-menu-btn');
    await page.waitForTimeout(300);
    await page.click('text=Logout');
    await expect(page.locator('input[name="username"]')).toBeVisible();
  });
});

test.describe('Date Navigation', () => {
  test.beforeEach(async ({ page }) => {
    await login(page);
  });

  test('should navigate to previous day', async ({ page }) => {
    const initialDate = await page.locator('#pretty-date').textContent();
    await page.click('#prev-day', { force: true });
    await page.waitForTimeout(500);
    const newDate = await page.locator('#pretty-date').textContent();
    expect(newDate).not.toBe(initialDate);
  });

  test('should navigate to next day', async ({ page }) => {
    const initialDate = await page.locator('#pretty-date').textContent();
    await page.click('#next-day', { force: true });
    await page.waitForTimeout(500);
    const newDate = await page.locator('#pretty-date').textContent();
    expect(newDate).not.toBe(initialDate);
  });

  test('should return to today when clicking Today button', async ({ page }) => {
    // Go to a different day first
    await page.click('#prev-day', { force: true });
    await page.click('#prev-day', { force: true });
    await page.waitForTimeout(500);

    // Click Today
    await page.click('#today-btn');
    await page.waitForTimeout(500);

    // Verify we're on today
    const today = new Date();
    const expectedDay = today.getDate().toString();

    const dateText = await page.locator('#pretty-date').textContent();
    expect(dateText).toContain(expectedDay);
  });

  test('should open calendar dropdown when clicking date', async ({ page }) => {
    await page.click('#pretty-date', { force: true });
    await page.waitForTimeout(300);
    await expect(page.locator('#calendar-dropdown')).toBeVisible();
  });

  test('should show Hebrew date', async ({ page }) => {
    // Wait for Hebrew date to load (async)
    await page.waitForTimeout(2000);
    const hebrewDate = await page.locator('#hebrew-date').textContent();
    expect(hebrewDate).toBeTruthy();
    expect(hebrewDate!.length).toBeGreaterThan(3);
  });
});

test.describe('Task Management', () => {
  test.beforeEach(async ({ page }) => {
    await login(page);
  });

  test('should add a new task with priority A', async ({ page }) => {
    const taskText = `Test task A ${Date.now()}`;

    await page.fill('#new-task-text', taskText);
    await page.selectOption('#new-task-priority', 'A');
    await page.click('#add-task-form button[type="submit"]');

    await page.waitForTimeout(500);
    await expect(page.locator(`.task-item:has-text("${taskText}")`)).toBeVisible();
  });

  test('should add tasks with different priorities', async ({ page }) => {
    const timestamp = Date.now();

    for (const priority of ['B', 'C', 'D']) {
      const taskText = `Test ${priority} ${timestamp}`;
      await page.fill('#new-task-text', taskText);
      await page.selectOption('#new-task-priority', priority);
      await page.click('#add-task-form button[type="submit"]');
      await page.waitForTimeout(500);
    }

    // Verify tasks were added
    for (const priority of ['B', 'C', 'D']) {
      await expect(page.locator(`.task-item:has-text("Test ${priority} ${timestamp}")`)).toBeVisible();
    }
  });

  test('should open task details modal when clicking Details', async ({ page }) => {
    // Click Details on an existing task
    const detailsBtn = page.locator('.task-item button:has-text("Details")').first();
    if (await detailsBtn.isVisible()) {
      await detailsBtn.click();
      await page.waitForTimeout(300);
      await expect(page.locator('#task-details-modal')).toBeVisible();
    }
  });

  test('should change task status to done', async ({ page }) => {
    // Add a task first
    const taskText = `Complete me ${Date.now()}`;
    await page.fill('#new-task-text', taskText);
    await page.click('#add-task-form button[type="submit"]');
    await page.waitForTimeout(500);

    // Find the task and click Done
    const taskItem = page.locator(`.task-item:has-text("${taskText}")`);
    await taskItem.locator('button:has-text("✓")').click();
    await page.waitForTimeout(500);

    // Verify status changed to done (dropdown value)
    await expect(taskItem.locator('select')).toHaveValue('done');
  });

  test('should delete a task', async ({ page }) => {
    // Add a task first
    const taskText = `Delete me ${Date.now()}`;
    await page.fill('#new-task-text', taskText);
    await page.click('#add-task-form button[type="submit"]');
    await page.waitForTimeout(500);

    // Verify it exists
    await expect(page.locator(`.task-item:has-text("${taskText}")`)).toBeVisible();

    // Delete it (X button) - handle custom confirm modal
    const taskItem = page.locator(`.task-item:has-text("${taskText}")`);
    await taskItem.locator('button:has-text("X")').click();
    await page.waitForTimeout(300);

    // Click the Delete button in the confirm modal
    await page.click('#confirm-ok');
    await page.waitForTimeout(500);

    // Verify it's gone
    await expect(page.locator(`.task-item:has-text("${taskText}")`)).not.toBeVisible();
  });
});

test.describe('Journal', () => {
  test.beforeEach(async ({ page }) => {
    await login(page);
  });

  test('should save journal entry with Ctrl+Enter', async ({ page }) => {
    const journalText = `Test journal entry ${Date.now()}`;

    await page.fill('#journal-content', journalText);
    await page.press('#journal-content', 'Control+Enter');

    // Wait for save
    await page.waitForTimeout(1000);

    // Reload and verify persistence
    await page.reload();
    await page.waitForSelector('#journal-content');
    await page.waitForTimeout(1000);

    const savedContent = await page.locator('#journal-content').inputValue();
    expect(savedContent).toContain(journalText);
  });

  test('should save journal with Save button', async ({ page }) => {
    const journalText = `Save button test ${Date.now()}`;

    await page.fill('#journal-content', journalText);
    await page.click('#save-journal');

    await page.waitForTimeout(1000);

    // Navigate away and back
    await page.click('#prev-day', { force: true });
    await page.waitForTimeout(500);
    await page.click('#next-day', { force: true });
    await page.waitForTimeout(500);

    const savedContent = await page.locator('#journal-content').inputValue();
    expect(savedContent).toContain(journalText);
  });
});

test.describe('Search', () => {
  test.beforeEach(async ({ page }) => {
    await login(page);
  });

  test('should open search modal', async ({ page }) => {
    await page.click('#search-btn');
    await page.waitForTimeout(300);
    await expect(page.locator('#search-modal')).toBeVisible();
  });
});

test.describe('Month Index', () => {
  test.beforeEach(async ({ page }) => {
    await login(page);
  });

  test('should open month index modal', async ({ page }) => {
    await page.click('#month-index-btn');
    await page.waitForTimeout(300);
    await expect(page.locator('#month-index-modal')).toBeVisible();
  });

  test('should show current month name in button', async ({ page }) => {
    const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                        'July', 'August', 'September', 'October', 'November', 'December'];
    const currentMonth = monthNames[new Date().getMonth()];

    const buttonText = await page.locator('#month-index-btn').textContent();
    expect(buttonText).toContain(currentMonth);
  });
});

test.describe('Zmanim', () => {
  test.beforeEach(async ({ page }) => {
    await login(page);
  });

  test('should open zmanim modal', async ({ page }) => {
    await page.click('#zmanim-btn');
    await page.waitForTimeout(300);
    await expect(page.locator('#zmanim-modal')).toBeVisible();
  });
});

test.describe('Recurring Tasks', () => {
  test.beforeEach(async ({ page }) => {
    await login(page);
  });

  test('should open recurring tasks modal', async ({ page }) => {
    await page.click('#recurring-btn');
    await page.waitForTimeout(300);
    await expect(page.locator('#recurring-modal')).toBeVisible();
  });
});

test.describe('User Menu', () => {
  test.beforeEach(async ({ page }) => {
    await login(page);
  });

  test('should open user dropdown', async ({ page }) => {
    await page.click('#user-menu-btn');
    await page.waitForTimeout(300);
    await expect(page.locator('#user-dropdown')).toBeVisible();
  });

  test('should show username in dropdown', async ({ page }) => {
    await page.click('#user-menu-btn');
    await page.waitForTimeout(300);
    await expect(page.locator('#user-dropdown')).toContainText(APP_USERNAME);
  });

  test('should have Settings option', async ({ page }) => {
    await page.click('#user-menu-btn');
    await page.waitForTimeout(300);
    await expect(page.locator('#user-dropdown >> text=Settings')).toBeVisible();
  });

  test('should have Export option', async ({ page }) => {
    await page.click('#user-menu-btn');
    await page.waitForTimeout(300);
    await expect(page.locator('#user-dropdown >> text=Export')).toBeVisible();
  });

  test('should open settings modal', async ({ page }) => {
    await page.click('#user-menu-btn');
    await page.waitForTimeout(300);
    await page.click('#user-dropdown >> text=Settings');
    await page.waitForTimeout(300);
    await expect(page.locator('#settings-modal')).toBeVisible();
  });
});

test.describe('Yartzheits', () => {
  test.beforeEach(async ({ page }) => {
    await login(page);
  });

  test('should open yartzheits modal', async ({ page }) => {
    await page.click('#yartzheits-btn');
    await page.waitForTimeout(300);
    await expect(page.locator('#yartzheits-modal')).toBeVisible();
  });
});

test.describe('UI Layout', () => {
  test.beforeEach(async ({ page }) => {
    await login(page);
  });

  test('should have centered date block', async ({ page }) => {
    const header = page.locator('header');
    const dateWrapper = page.locator('.date-controls-wrapper');

    const headerBox = await header.boundingBox();
    const dateBox = await dateWrapper.boundingBox();

    if (headerBox && dateBox) {
      const headerCenter = headerBox.x + headerBox.width / 2;
      const dateCenter = dateBox.x + dateBox.width / 2;

      // Date should be within 300px of center (grid layout centers between left/right sections)
      expect(Math.abs(headerCenter - dateCenter)).toBeLessThan(300);
    }
  });

  test('should have two-column layout', async ({ page }) => {
    const columns = page.locator('.column');
    const count = await columns.count();
    expect(count).toBe(2);
  });

  test('should display Gapaika Planner title', async ({ page }) => {
    await expect(page.locator('.header-left')).toContainText('Gapaika Planner');
  });

  test('should display priority sections', async ({ page }) => {
    await expect(page.locator('.priority-title:has-text("Priority A")')).toBeVisible();
    await expect(page.locator('.priority-title:has-text("Priority B")')).toBeVisible();
    await expect(page.locator('.priority-title:has-text("Priority C")')).toBeVisible();
    await expect(page.locator('.priority-title:has-text("Priority D")')).toBeVisible();
  });
});

test.describe('Data Persistence', () => {
  test('should persist task after reload', async ({ page }) => {
    await login(page);

    const taskText = `Persist task ${Date.now()}`;
    await page.fill('#new-task-text', taskText);
    await page.click('#add-task-form button[type="submit"]');
    await page.waitForTimeout(500);

    // Reload
    await page.reload();
    await page.waitForSelector('#pretty-date');
    await page.waitForTimeout(1000);

    // Task should still be there
    await expect(page.locator(`.task-item:has-text("${taskText}")`)).toBeVisible();
  });
});
