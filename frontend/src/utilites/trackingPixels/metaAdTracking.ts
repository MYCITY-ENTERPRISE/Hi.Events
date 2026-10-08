import {TrackingPixelConfig} from '../../types';
import {isConsentBannerEnabled, readConsent} from '../cookieConsent';

export interface MetaAdTrackingContext {
    fbp: string | null;
    fbc: string | null;
    event_source_url: string;
}

function readCookie(name: string, maxLength: number): string | null {
    const match = document.cookie.match(new RegExp(`(?:^|;\\s*)${name}=([^;]*)`));
    const value = match ? decodeURIComponent(match[1]) : null;
    return value && value.length <= maxLength ? value : null;
}

export function getMetaAdTrackingContext(trackingPixels: TrackingPixelConfig[] | undefined): MetaAdTrackingContext | undefined {
    if (typeof window === 'undefined') return undefined;

    const hasFacebookPixel = (trackingPixels ?? []).some((pixel) => pixel.provider === 'facebook_pixel' && pixel.enabled);
    const advertisingAllowed = !isConsentBannerEnabled() || !!readConsent()?.advertising;

    if (!hasFacebookPixel || !advertisingAllowed) return undefined;

    return {
        fbp: readCookie('_fbp', 255),
        fbc: readCookie('_fbc', 500),
        event_source_url: `${window.location.origin}${window.location.pathname}`.slice(0, 2048),
    };
}
