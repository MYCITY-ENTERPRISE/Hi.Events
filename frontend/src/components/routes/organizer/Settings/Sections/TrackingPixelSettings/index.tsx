import {useParams} from "react-router";
import {useForm} from "@mantine/form";
import {useFormErrorResponseHandler} from "../../../../../../hooks/useFormErrorResponseHandler.tsx";
import {useEffect} from "react";
import {showSuccess} from "../../../../../../utilites/notifications.tsx";
import {t} from "@lingui/macro";
import {Card} from "../../../../../common/Card";
import {HeadingWithDescription} from "../../../../../common/Card/CardHeading";
import {Button, Checkbox, PasswordInput, Switch, Text, TextInput} from "@mantine/core";
import {Callout} from "../../../../../common/Callout";
import {
    IconBrandFacebook,
    IconBrandGoogle,
    IconBrandTiktok,
    IconTag,
} from "@tabler/icons-react";
import {useGetOrganizerSettings} from "../../../../../../queries/useGetOrganizerSettings.ts";
import {useUpdateOrganizerSettings} from "../../../../../../mutations/useUpdateOrganizerSettings.ts";
import {useGetAccount} from "../../../../../../queries/useGetAccount.ts";
import {TrackingPixelConfig} from "../../../../../../types.ts";

interface ProviderDef {
    key: string;
    label: string;
    icon: any;
    placeholder: string;
    description: string;
    pattern: RegExp;
    formatHint: string;
}

const PROVIDERS: ProviderDef[] = [
    {
        key: 'facebook_pixel',
        label: 'Facebook Pixel',
        icon: IconBrandFacebook,
        placeholder: '1234567890',
        description: 'Pixel ID (numeric)',
        pattern: /^\d{9,20}$/,
        formatHint: 'Must be 9-20 digits',
    },
    {
        key: 'google_analytics_4',
        label: 'Google Analytics 4',
        icon: IconBrandGoogle,
        placeholder: 'G-XXXXXXXXXX',
        description: 'Measurement ID',
        pattern: /^G-[a-zA-Z0-9]{6,20}$/,
        formatHint: 'Must start with G- followed by 6-20 characters',
    },
    {
        key: 'google_tag_manager',
        label: 'Google Tag Manager',
        icon: IconTag,
        placeholder: 'GTM-XXXXXXX',
        description: 'Container ID',
        pattern: /^GTM-[a-zA-Z0-9]{4,20}$/,
        formatHint: 'Must start with GTM- followed by 4-20 characters',
    },
    {
        key: 'tiktok_pixel',
        label: 'TikTok Pixel',
        icon: IconBrandTiktok,
        placeholder: 'CXXXXXXXXXX',
        description: 'Pixel ID',
        pattern: /^[a-zA-Z0-9]{6,30}$/,
        formatHint: 'Must be 6-30 alphanumeric characters',
    },
];

function pixelsToFormState(pixels: TrackingPixelConfig[] | undefined): Record<string, { enabled: boolean; pixel_id: string }> {
    const state: Record<string, { enabled: boolean; pixel_id: string }> = {};
    for (const p of PROVIDERS) {
        state[p.key] = {enabled: false, pixel_id: ''};
    }
    if (pixels) {
        for (const pixel of pixels) {
            if (state[pixel.provider]) {
                state[pixel.provider] = {enabled: pixel.enabled, pixel_id: pixel.pixel_id};
            }
        }
    }
    return state;
}

function formStateToPixels(state: Record<string, { enabled: boolean; pixel_id: string }>): TrackingPixelConfig[] {
    return Object.entries(state)
        .filter(([, v]) => v.pixel_id.trim() !== '')
        .map(([provider, v]) => ({
            provider,
            pixel_id: v.pixel_id.trim(),
            enabled: v.enabled,
        }));
}

const GTM_PROVIDER_KEY = 'google_tag_manager';
const FACEBOOK_PROVIDER_KEY = 'facebook_pixel';

export const TrackingPixelSettings = () => {
    const {organizerId} = useParams();
    const {data: account} = useGetAccount();
    const isSaasMode = account?.is_saas_mode_enabled;
    const availableProviders = isSaasMode
        ? PROVIDERS.filter(p => p.key !== GTM_PROVIDER_KEY)
        : PROVIDERS;
    const organizerSettingsQuery = useGetOrganizerSettings(organizerId);
    const updateMutation = useUpdateOrganizerSettings();
    const formErrorHandle = useFormErrorResponseHandler();

    const form = useForm<{
        pixels: Record<string, { enabled: boolean; pixel_id: string }>;
        tracking_consent_acknowledged: boolean;
        meta_conversions_api_access_token: string;
        remove_meta_conversions_api_access_token: boolean;
    }>({
        initialValues: {
            pixels: pixelsToFormState(undefined),
            tracking_consent_acknowledged: false,
            meta_conversions_api_access_token: '',
            remove_meta_conversions_api_access_token: false,
        },
    });

    const isMetaConversionsApiConfigured = !!organizerSettingsQuery.data?.meta_conversions_api_configured;

    useEffect(() => {
        if (organizerSettingsQuery?.isFetched && organizerSettingsQuery?.data) {
            form.setValues({
                pixels: pixelsToFormState(organizerSettingsQuery.data.tracking_pixels),
                tracking_consent_acknowledged: organizerSettingsQuery.data.tracking_consent_acknowledged || false,
                meta_conversions_api_access_token: '',
                remove_meta_conversions_api_access_token: false,
            });
        }
    }, [organizerSettingsQuery.isFetched]);

    const handleSubmit = (values: typeof form.values) => {
        let hasErrors = false;

        for (const provider of availableProviders) {
            const pixelId = values.pixels[provider.key]?.pixel_id.trim();
            if (pixelId && !provider.pattern.test(pixelId)) {
                form.setFieldError(`pixels.${provider.key}.pixel_id`, provider.formatHint);
                hasErrors = true;
            }
        }

        let trackingPixels = formStateToPixels(values.pixels);
        if (isSaasMode) {
            trackingPixels = trackingPixels.filter(p => p.provider !== GTM_PROVIDER_KEY);
        }

        if (trackingPixels.length > 0 && !values.tracking_consent_acknowledged) {
            form.setFieldError('tracking_consent_acknowledged', t`You must acknowledge your responsibilities before saving`);
            hasErrors = true;
        }

        if (hasErrors) return;

        const metaAccessToken = values.meta_conversions_api_access_token.trim();
        const metaAccessTokenUpdate = metaAccessToken !== ''
            ? {meta_conversions_api_access_token: metaAccessToken}
            : values.remove_meta_conversions_api_access_token
                ? {meta_conversions_api_access_token: null}
                : {};

        updateMutation.mutate({
            organizerSettings: {
                tracking_pixels: trackingPixels,
                tracking_consent_acknowledged: values.tracking_consent_acknowledged,
                ...metaAccessTokenUpdate,
            },
            organizerId: organizerId,
        }, {
            onSuccess: () => {
                form.setFieldValue('meta_conversions_api_access_token', '');
                form.setFieldValue('remove_meta_conversions_api_access_token', false);
                showSuccess(t`Successfully Updated Tracking Settings`);
            },
            onError: (error) => {
                formErrorHandle(form, error);
            }
        });
    };

    const hasAnyPixels = availableProviders.some(p => form.values.pixels[p.key]?.pixel_id.trim() !== '');

    return (
        <Card>
            <HeadingWithDescription
                heading={t`Tracking & Analytics`}
                description={t`Add tracking pixels to your public event pages and organizer homepage. A cookie consent banner will be shown to visitors when tracking is active.`}
            />
            <form onSubmit={form.onSubmit(handleSubmit)}>
                <fieldset disabled={organizerSettingsQuery.isLoading || updateMutation.isPending}>
                    <div style={{display: 'flex', flexDirection: 'column', gap: '12px'}}>
                        {availableProviders.map((provider) => {
                            const Icon = provider.icon;
                            const pixelState = form.values.pixels[provider.key];
                            const hasValue = pixelState?.pixel_id.trim() !== '';

                            return (
                                <div
                                    key={provider.key}
                                    style={{
                                        padding: '14px 16px',
                                        borderRadius: '8px',
                                        border: `1px solid var(--mantine-color-${hasValue && pixelState?.enabled ? 'primary-3' : 'gray-3'})`,
                                        backgroundColor: hasValue && pixelState?.enabled ? 'var(--mantine-color-primary-0)' : 'transparent',
                                        transition: 'all 0.15s ease',
                                    }}
                                >
                                    <div style={{
                                        display: 'flex',
                                        alignItems: 'center',
                                        justifyContent: 'space-between',
                                        marginBottom: hasValue ? '10px' : 0,
                                    }}>
                                        <div style={{display: 'flex', alignItems: 'center', gap: '10px'}}>
                                            <Icon size={20} style={{opacity: 0.7}}/>
                                            <div>
                                                <Text size="sm" fw={500}>{provider.label}</Text>
                                                {!hasValue && (
                                                    <Text size="xs" c="dimmed">{provider.description}</Text>
                                                )}
                                            </div>
                                        </div>
                                        {hasValue && (
                                            <Switch
                                                size="sm"
                                                checked={pixelState?.enabled ?? false}
                                                onChange={(e) => form.setFieldValue(
                                                    `pixels.${provider.key}.enabled`,
                                                    e.currentTarget.checked
                                                )}
                                            />
                                        )}
                                    </div>
                                    <TextInput
                                        {...form.getInputProps(`pixels.${provider.key}.pixel_id`)}
                                        placeholder={provider.placeholder}
                                        size="sm"
                                        description={hasValue ? provider.description : undefined}
                                        onChange={(e) => {
                                            form.setFieldValue(`pixels.${provider.key}.pixel_id`, e.currentTarget.value);
                                            if (e.currentTarget.value.trim() !== '' && !pixelState?.enabled) {
                                                form.setFieldValue(`pixels.${provider.key}.enabled`, true);
                                            }
                                        }}
                                        styles={{
                                            input: {
                                                fontFamily: 'monospace',
                                                fontSize: '13px',
                                            },
                                        }}
                                    />
                                    {provider.key === FACEBOOK_PROVIDER_KEY && hasValue && (
                                        <div style={{marginTop: '12px'}}>
                                            <PasswordInput
                                                {...form.getInputProps('meta_conversions_api_access_token')}
                                                label={t`Conversions API access token (optional)`}
                                                description={isMetaConversionsApiConfigured
                                                    ? t`A token is saved. Enter a new token to replace it.`
                                                    : t`Also sends completed purchases to Meta from our server, so ad blockers and browser privacy settings don't hide them. Generate the token in Meta Events Manager under your pixel's settings.`}
                                                placeholder={isMetaConversionsApiConfigured ? '••••••••••••' : undefined}
                                                size="sm"
                                                autoComplete="off"
                                            />
                                            {isMetaConversionsApiConfigured && (
                                                <Checkbox
                                                    {...form.getInputProps('remove_meta_conversions_api_access_token', {type: 'checkbox'})}
                                                    label={t`Remove the saved access token`}
                                                    size="xs"
                                                    mt="xs"
                                                />
                                            )}
                                        </div>
                                    )}
                                </div>
                            );
                        })}
                    </div>

                    {hasAnyPixels && (
                        <>
                            <Callout variant="tip" style={{marginTop: 24}}>
                                {t`By adding tracking pixels, you acknowledge that you and this platform are joint controllers of the data collected. You are responsible for ensuring you have a lawful basis for this processing under applicable privacy laws (GDPR, CCPA, etc.).`}
                            </Callout>
                            <Checkbox
                                {...form.getInputProps('tracking_consent_acknowledged', {type: 'checkbox'})}
                                label={t`I acknowledge my responsibilities as a data controller`}
                                mb="md"
                                error={form.errors.tracking_consent_acknowledged}
                            />
                        </>
                    )}

                    <Button
                        loading={updateMutation.isPending}
                        type="submit"
                        mt="md"
                    >
                        {t`Save`}
                    </Button>
                </fieldset>
            </form>
        </Card>
    );
};
