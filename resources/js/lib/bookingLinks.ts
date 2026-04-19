import { detectPlatformBrand } from '@/lib/platformBrands';

export interface QuickLink {
    label?: string | null;
    link?: string | null;
}

const BOOKING_LABEL_PATTERN = /\b(book|booking|appointment|reserve|reservation|schedule|consultation)\b/i;
const BOOKING_URL_PATTERN = /calendly|acuityscheduling|treatwell|booksy|fresha|opentable|simplybook|vagaro/i;

export function isBookingQuickLink(link: QuickLink | null | undefined): boolean {
    if (!link?.link) {
        return false;
    }

    const label = link.label ?? '';
    const brand = detectPlatformBrand(link.link);

    return BOOKING_LABEL_PATTERN.test(label)
        || BOOKING_URL_PATTERN.test(link.link)
        || BOOKING_LABEL_PATTERN.test(brand?.suggestedLabel ?? '');
}

export function findPrimaryBookingLink(quickLinks: QuickLink[] | null | undefined): QuickLink | null {
    if (!quickLinks?.length) {
        return null;
    }

    return quickLinks.find((link) => isBookingQuickLink(link)) ?? null;
}

export function bookingCtaLabel(link: QuickLink | null | undefined): string {
    if (!link) {
        return 'Book now';
    }

    const label = link.label?.trim();
    if (label && BOOKING_LABEL_PATTERN.test(label)) {
        return label;
    }

    const brandLabel = detectPlatformBrand(link.link ?? '')?.suggestedLabel;
    if (brandLabel && BOOKING_LABEL_PATTERN.test(brandLabel)) {
        return brandLabel;
    }

    return 'Book now';
}
