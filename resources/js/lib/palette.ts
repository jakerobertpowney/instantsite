/**
 * Site colour palette system.
 *
 * Each published site gets a primary + secondary palette either:
 *   1. Chosen by the user in the dashboard (stored in site.data.overrides.palette)
 *   2. Automatically derived from the Google Places `primaryType` field
 *
 * All colours are injected as CSS custom properties on the site root element
 * so child components can reference `var(--site-primary)` etc. without prop-drilling.
 */

// ─── Types ────────────────────────────────────────────────────────────────────

export interface SitePalette {
    /** Main brand colour — used for hero overlay, buttons, accents */
    primary: string;
    /** Text colour on a primary-coloured background (always white or near-black) */
    primaryFg: string;
    /** Very light tint of primary — used for tag backgrounds, section tints */
    primaryMuted: string;
    /** Slightly darker variant of primary — used for hover states */
    primaryHover: string;
    /** Deep dark variant of primary — used for dark section backgrounds (Contact strip, footer) */
    primaryDark: string;
    /** Secondary / complementary accent colour */
    secondary: string;
}

export interface ColourTheme {
    id: string;
    name: string;
    palette: SitePalette;
}

// ─── Colour math utilities ────────────────────────────────────────────────────

function hexToRgb(hex: string): [number, number, number] {
    const clean = hex.replace('#', '').slice(0, 6);
    return [
        parseInt(clean.slice(0, 2), 16),
        parseInt(clean.slice(2, 4), 16),
        parseInt(clean.slice(4, 6), 16),
    ];
}

function rgbToHex(r: number, g: number, b: number): string {
    return '#' + [r, g, b]
        .map(c => Math.max(0, Math.min(255, Math.round(c))).toString(16).padStart(2, '0'))
        .join('');
}

function relativeLuminance([r, g, b]: [number, number, number]): number {
    const toLinear = (c: number) => {
        const n = c / 255;
        return n <= 0.03928 ? n / 12.92 : ((n + 0.055) / 1.055) ** 2.4;
    };
    return 0.2126 * toLinear(r) + 0.7152 * toLinear(g) + 0.0722 * toLinear(b);
}

/** Returns '#ffffff' or '#000000' — whichever has better contrast against `hex` */
export function contrastingFg(hex: string): string {
    const lum = relativeLuminance(hexToRgb(hex));
    return lum > 0.179 ? '#000000' : '#ffffff';
}

/** Mix `hex` toward white by `ratio` (0 = no change, 1 = full white) */
function mixWithWhite(hex: string, ratio: number): string {
    const [r, g, b] = hexToRgb(hex);
    return rgbToHex(r + (255 - r) * ratio, g + (255 - g) * ratio, b + (255 - b) * ratio);
}

/** Darken `hex` by `ratio` (0 = no change, 1 = black) */
function darken(hex: string, ratio: number): string {
    const [r, g, b] = hexToRgb(hex);
    return rgbToHex(r * (1 - ratio), g * (1 - ratio), b * (1 - ratio));
}

function hexToHsl(hex: string): [number, number, number] {
    const [rr, gg, bb] = hexToRgb(hex).map(c => c / 255) as [number, number, number];
    const max = Math.max(rr, gg, bb);
    const min = Math.min(rr, gg, bb);
    let h = 0;
    let s = 0;
    const l = (max + min) / 2;
    if (max !== min) {
        const d = max - min;
        s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
        switch (max) {
            case rr: h = ((gg - bb) / d + (gg < bb ? 6 : 0)) / 6; break;
            case gg: h = ((bb - rr) / d + 2) / 6; break;
            case bb: h = ((rr - gg) / d + 4) / 6; break;
        }
    }
    return [h * 360, s * 100, l * 100];
}

function hslToHex(h: number, s: number, l: number): string {
    const hh = h / 360;
    const ss = s / 100;
    const ll = l / 100;
    const hue2rgb = (p: number, q: number, t: number) => {
        let tt = t;
        if (tt < 0) tt += 1;
        if (tt > 1) tt -= 1;
        if (tt < 1 / 6) return p + (q - p) * 6 * tt;
        if (tt < 1 / 2) return q;
        if (tt < 2 / 3) return p + (q - p) * (2 / 3 - tt) * 6;
        return p;
    };
    let r: number, g: number, b: number;
    if (ss === 0) {
        r = g = b = ll;
    } else {
        const q = ll < 0.5 ? ll * (1 + ss) : ll + ss - ll * ss;
        const p = 2 * ll - q;
        r = hue2rgb(p, q, hh + 1 / 3);
        g = hue2rgb(p, q, hh);
        b = hue2rgb(p, q, hh - 1 / 3);
    }
    return rgbToHex(r * 255, g * 255, b * 255);
}

/**
 * Derive a deep, dark variant of a hex colour for use as a section background.
 * Keeps the hue so it reads as the brand colour, clamps saturation to avoid
 * oversaturation, and drops lightness to ~13% so white text remains readable.
 */
function toDark(hex: string): string {
    const [h, s] = hexToHsl(hex);
    return hslToHex(h, Math.min(s, 55), 13);
}

/**
 * Build a full SitePalette from a primary hex and optional secondary hex.
 * Automatically derives `primaryFg`, `primaryMuted`, `primaryHover`, and `primaryDark`.
 * If `secondary` is omitted, derives a lighter / more vibrant variant of primary.
 */
export function buildPaletteFromPrimary(primary: string, secondary?: string): SitePalette {
    const [h, s, l] = hexToHsl(primary);
    const sec = secondary ?? hslToHex(h, Math.min(s + 10, 100), Math.min(l + 28, 78));

    return {
        primary,
        primaryFg:    contrastingFg(primary),
        primaryMuted: mixWithWhite(primary, 0.85),
        primaryHover: darken(primary, 0.12),
        primaryDark:  toDark(primary),
        secondary:    sec,
    };
}

// ─── Curated colour themes ────────────────────────────────────────────────────

export const COLOUR_THEMES: ColourTheme[] = [
    {
        id: 'cobalt',
        name: 'Cobalt',
        palette: buildPaletteFromPrimary('#1e40af', '#3b82f6'),
    },
    {
        id: 'navy',
        name: 'Navy',
        palette: buildPaletteFromPrimary('#1e3a5f', '#3b82f6'),
    },
    {
        id: 'ocean',
        name: 'Ocean',
        palette: buildPaletteFromPrimary('#0369a1', '#38bdf8'),
    },
    {
        id: 'teal',
        name: 'Teal',
        palette: buildPaletteFromPrimary('#0f766e', '#14b8a6'),
    },
    {
        id: 'forest',
        name: 'Forest',
        palette: buildPaletteFromPrimary('#14532d', '#22c55e'),
    },
    {
        id: 'emerald',
        name: 'Emerald',
        palette: buildPaletteFromPrimary('#065f46', '#10b981'),
    },
    {
        id: 'indigo',
        name: 'Indigo',
        palette: buildPaletteFromPrimary('#312e81', '#6366f1'),
    },
    {
        id: 'plum',
        name: 'Plum',
        palette: buildPaletteFromPrimary('#581c87', '#a855f7'),
    },
    {
        id: 'rose',
        name: 'Rose',
        palette: buildPaletteFromPrimary('#9d174d', '#ec4899'),
    },
    {
        id: 'crimson',
        name: 'Crimson',
        palette: buildPaletteFromPrimary('#7f1d1d', '#ef4444'),
    },
    {
        id: 'amber',
        name: 'Amber',
        palette: buildPaletteFromPrimary('#78350f', '#f59e0b'),
    },
    {
        id: 'copper',
        name: 'Copper',
        palette: buildPaletteFromPrimary('#7c2d12', '#ea580c'),
    },
    {
        id: 'charcoal',
        name: 'Charcoal',
        palette: buildPaletteFromPrimary('#1c1917', '#78716c'),
    },
    {
        id: 'slate',
        name: 'Slate',
        palette: buildPaletteFromPrimary('#1e293b', '#64748b'),
    },
];

// ─── Industry-type based auto-palette ────────────────────────────────────────

const AUTO_PALETTES: Record<string, SitePalette> = {
    trades:       COLOUR_THEMES.find(t => t.id === 'cobalt')!.palette,
    beauty:       COLOUR_THEMES.find(t => t.id === 'rose')!.palette,
    food:         COLOUR_THEMES.find(t => t.id === 'amber')!.palette,
    fitness:      COLOUR_THEMES.find(t => t.id === 'emerald')!.palette,
    medical:      COLOUR_THEMES.find(t => t.id === 'teal')!.palette,
    professional: COLOUR_THEMES.find(t => t.id === 'indigo')!.palette,
    retail:       COLOUR_THEMES.find(t => t.id === 'plum')!.palette,
    automotive:   COLOUR_THEMES.find(t => t.id === 'charcoal')!.palette,
    default:      COLOUR_THEMES.find(t => t.id === 'slate')!.palette,
};

/** Maps Google Places `primaryType` slugs to palette categories */
const TYPE_TO_CATEGORY: Record<string, keyof typeof AUTO_PALETTES> = {
    // Trades
    painter:                     'trades',
    plumber:                     'trades',
    electrician:                 'trades',
    roofing_contractor:          'trades',
    general_contractor:          'trades',
    hvac_contractor:             'trades',
    locksmith:                   'trades',
    moving_company:              'trades',
    flooring_store:              'trades',
    window_installation_service: 'trades',
    handyman:                    'trades',
    landscaper:                  'trades',
    // Beauty & wellness
    hair_care:                   'beauty',
    beauty_salon:                'beauty',
    nail_salon:                  'beauty',
    spa:                         'beauty',
    barber_shop:                 'beauty',
    massage_therapist:           'beauty',
    tanning_studio:              'beauty',
    // Food & hospitality
    restaurant:                  'food',
    cafe:                        'food',
    bakery:                      'food',
    bar:                         'food',
    night_club:                  'food',
    meal_takeaway:               'food',
    meal_delivery:               'food',
    catering_service:            'food',
    // Fitness
    gym:                         'fitness',
    fitness_center:              'fitness',
    sports_club:                 'fitness',
    yoga_studio:                 'fitness',
    martial_arts_school:         'fitness',
    swimming_pool:               'fitness',
    // Medical
    doctor:                      'medical',
    dentist:                     'medical',
    hospital:                    'medical',
    pharmacy:                    'medical',
    physiotherapist:             'medical',
    chiropractor:                'medical',
    veterinary_care:             'medical',
    optometrist:                 'medical',
    // Professional services
    lawyer:                      'professional',
    accountant:                  'professional',
    real_estate_agency:          'professional',
    insurance_agency:            'professional',
    financial_planner:           'professional',
    tax_preparation_service:     'professional',
    // Retail
    clothing_store:              'retail',
    shoe_store:                  'retail',
    jewelry_store:               'retail',
    electronics_store:           'retail',
    book_store:                  'retail',
    furniture_store:             'retail',
    gift_shop:                   'retail',
    // Automotive
    car_repair:                  'automotive',
    car_wash:                    'automotive',
    auto_parts_store:            'automotive',
    used_car_dealer:             'automotive',
    car_dealer:                  'automotive',
    tire_shop:                   'automotive',
};

/**
 * Returns the auto palette for a given Google Places primaryType string.
 * Falls back to the default palette when the type is unknown or absent.
 */
export function getPalette(primaryType?: string): SitePalette {
    if (!primaryType) return AUTO_PALETTES.default;
    const normalised = primaryType.toLowerCase().replace(/\s+/g, '_');
    const category = TYPE_TO_CATEGORY[normalised] ?? 'default';
    return AUTO_PALETTES[category];
}

/**
 * Returns the recommended theme ID for a given business type.
 * Used in the setup wizard to show a colour suggestion.
 */
export function getRecommendedThemeId(primaryType?: string): string {
    if (!primaryType) return 'slate';
    const normalised = primaryType.toLowerCase().replace(/\s+/g, '_');
    const category = TYPE_TO_CATEGORY[normalised] ?? 'default';
    const palette = AUTO_PALETTES[category];
    // Find matching theme
    const match = COLOUR_THEMES.find(t => t.palette.primary === palette.primary);
    return match?.id ?? 'slate';
}

/**
 * Returns the effective palette for a site, respecting user-set custom colours.
 * Priority: settings.palette (custom) → business-type auto palette
 */
export function getEffectivePalette(data: Record<string, any> | null | undefined): SitePalette {
    // Support both new shape (settings.palette) and legacy shape (overrides.palette)
    const custom = data?.settings?.palette ?? data?.overrides?.palette;
    if (custom?.primary) {
        return buildPaletteFromPrimary(custom.primary, custom.secondary || undefined);
    }
    // Support both new shape (business_type) and old shape (primaryType / primaryTypeDisplayName.text)
    const type = data?.business_type ?? data?.primaryType ?? data?.primaryTypeDisplayName?.text;
    return getPalette(type);
}

/**
 * Converts a SitePalette into a CSS custom-properties object suitable for
 * Vue's `:style` binding on a root element.
 */
export function paletteToCssVars(p: SitePalette): Record<string, string> {
    return {
        '--site-primary':       p.primary,
        '--site-primary-fg':    p.primaryFg,
        '--site-primary-muted': p.primaryMuted,
        '--site-primary-hover': p.primaryHover,
        '--site-primary-dark':  p.primaryDark,
        '--site-secondary':     p.secondary,
    };
}
