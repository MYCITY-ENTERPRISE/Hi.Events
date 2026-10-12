import { test, expect } from '../../fixtures';
import { OrganizerPage } from '../../pages/organizer.page';
import { createFreshOrganizer } from '../../api/factory';

test.describe('organizer Meta Conversions API settings', () => {
  test('an organizer saves a Conversions API token next to their pixel and removes it again', async ({ authedPage, api }) => {
    const seeded = await createFreshOrganizer(api);
    const accessToken = 'EAAe2eConversionsToken123';

    const organizer = new OrganizerPage(authedPage);
    await organizer.gotoSettings(seeded.id);

    await expect(organizer.metaConversionsApiTokenInput).toBeHidden();
    await organizer.facebookPixelIdInput.fill('1588480782483786');
    await organizer.metaConversionsApiTokenInput.fill(accessToken);
    await organizer.trackingConsentCheckbox.check();
    await organizer.saveTrackingSettings();
    await expect(authedPage.getByText('Successfully Updated Tracking Settings')).toBeVisible();

    const savedSettings = await api.getOrganizerSettings(seeded.id);
    expect(savedSettings.meta_conversions_api_configured).toBe(true);
    expect(JSON.stringify(savedSettings)).not.toContain(accessToken);

    await authedPage.reload();
    await authedPage.waitForLoadState('networkidle');

    await expect(organizer.facebookPixelIdInput).toHaveValue('1588480782483786');
    await expect(organizer.metaConversionsApiTokenInput).toHaveValue('');
    await expect(organizer.trackingSection.getByText('A token is saved. Enter a new token to replace it.')).toBeVisible();

    await organizer.removeMetaConversionsApiTokenCheckbox.check();
    await organizer.saveTrackingSettings();
    await expect(authedPage.getByText('Successfully Updated Tracking Settings')).toBeVisible();

    await authedPage.reload();
    await authedPage.waitForLoadState('networkidle');

    await expect(organizer.removeMetaConversionsApiTokenCheckbox).toBeHidden();
    expect((await api.getOrganizerSettings(seeded.id)).meta_conversions_api_configured).toBe(false);
  });
});
