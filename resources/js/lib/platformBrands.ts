export interface PlatformBrand {
    bgColor: string;
    textColor: string;
    domain: string;
    suggestedLabel: string;
}

interface PlatformEntry extends PlatformBrand {
    pattern: RegExp;
}

export const PLATFORM_ENTRIES: PlatformEntry[] = [
    { pattern: /calendly\.com/i,         bgColor: '#006BFF', textColor: '#fff', domain: 'calendly.com',          suggestedLabel: 'Book an appointment' },
    { pattern: /acuityscheduling\.com/i, bgColor: '#6C4AB6', textColor: '#fff', domain: 'acuityscheduling.com',  suggestedLabel: 'Book an appointment' },
    { pattern: /checkatrade\.com/i,      bgColor: '#2E7D32', textColor: '#fff', domain: 'checkatrade.com',       suggestedLabel: 'Find us on Checkatrade' },
    { pattern: /mybuilder\.com/i,        bgColor: '#E65100', textColor: '#fff', domain: 'mybuilder.com',         suggestedLabel: 'Find us on MyBuilder' },
    { pattern: /ratedpeople\.com/i,      bgColor: '#C62828', textColor: '#fff', domain: 'ratedpeople.com',       suggestedLabel: 'Find us on Rated People' },
    { pattern: /treatwell\./i,           bgColor: '#4A148C', textColor: '#fff', domain: 'treatwell.co.uk',       suggestedLabel: 'Book on Treatwell' },
    { pattern: /opentable\.com/i,        bgColor: '#DA3743', textColor: '#fff', domain: 'opentable.com',         suggestedLabel: 'Reserve a table' },
    { pattern: /booksy\.com/i,           bgColor: '#7B2D8B', textColor: '#fff', domain: 'booksy.com',            suggestedLabel: 'Book on Booksy' },
    { pattern: /fresha\.com/i,           bgColor: '#111827', textColor: '#fff', domain: 'fresha.com',            suggestedLabel: 'Book on Fresha' },
    { pattern: /wa\.me|whatsapp\.com/i,  bgColor: '#25D366', textColor: '#fff', domain: 'whatsapp.com',          suggestedLabel: 'WhatsApp us' },
    { pattern: /trustpilot\.com/i,       bgColor: '#00B67A', textColor: '#fff', domain: 'trustpilot.com',        suggestedLabel: 'Read our reviews' },
    { pattern: /google\.com\/maps/i,     bgColor: '#4285F4', textColor: '#fff', domain: 'google.com',            suggestedLabel: 'Find us on Google Maps' },
];

/**
 * Returns brand styling for a known platform URL, or null for unknown URLs.
 */
export function detectPlatformBrand(url: string): PlatformBrand | null {
    if (!url) return null;
    const entry = PLATFORM_ENTRIES.find((p) => p.pattern.test(url));
    if (!entry) return null;
    return {
        bgColor: entry.bgColor,
        textColor: entry.textColor,
        domain: entry.domain,
        suggestedLabel: entry.suggestedLabel,
    };
}

/**
 * Returns the suggested label for a known platform URL, or null.
 */
export function suggestLabelForUrl(url: string): string | null {
    return detectPlatformBrand(url)?.suggestedLabel ?? null;
}

/**
 * Returns a Google favicon URL for any given link URL.
 * Falls back to null if the URL can't be parsed.
 */
export function getFaviconUrl(url: string): string | null {
    try {
        const { hostname } = new URL(url);
        return `https://www.google.com/s2/favicons?sz=32&domain=${hostname}`;
    } catch {
        return null;
    }
}
