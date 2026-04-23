<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, inject, ref, watch } from 'vue';
import { components as dashboardComponents } from '@/routes/dashboard';
import { suggestLabelForUrl, getFaviconUrl, detectPlatformBrand } from '@/lib/platformBrands';
import { toast } from 'vue-sonner';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { Input } from '@/components/ui/input';
import { CheckCircle2, Eye, EyeOff, ExternalLink, Image, Loader2, Lock, MapPin, MessageCircle, Palette, Pencil, Phone, Plus, Sparkles, Star, Trash2, Upload, Wand2, X } from 'lucide-vue-next';
import axios from 'axios';
import HeaderBackgroundPicker from '@/components/dashboard/HeaderBackgroundPicker.vue';
import ServiceInlineEditor from '@/components/dashboard/ServiceInlineEditor.vue';
import { COLOUR_THEMES, buildPaletteFromPrimary, getRecommendedThemeId } from '@/lib/palette';
import { formatPrice } from '@/lib/currencies';

type ComponentKey = 'header' | 'description' | 'gallery' | 'quick_actions' | 'reviews' | 'contact' | 'contact_form' | 'services';
type ComponentState = Record<ComponentKey, { enabled: boolean }>;
type Socials = { instagram: string; facebook: string; x: string; linkedin: string };
type QuickLink = { label: string; link: string };
type Service = { id: string; name: string; description: string | null; price: string | null; currency: string; show_price: boolean; featured: boolean };
type SiteData = Record<string, any>;

const componentKeys: ComponentKey[] = ['header', 'description', 'gallery', 'quick_actions', 'reviews', 'contact', 'contact_form', 'services'];
const page = usePage();
const isPremium = inject('isPremium') as boolean;
const site = computed(() => (page.props.site as { data?: SiteData } | undefined) ?? {});

// Derive component flags from site.data, defaulting missing keys to enabled
const siteData = computed(() => site.value.data ?? {});
const siteComponents = computed(() => siteData.value.components ?? {});
const siteOverrides = computed(() => siteData.value.overrides ?? {});

function normalizeEnabled(value: unknown): boolean {
    if (value === undefined || value === null) return true;
    if (typeof value === 'boolean') return value;
    if (typeof value === 'number') return value !== 0;

    if (typeof value === 'string') {
        const normalized = value.trim().toLowerCase();

        if (['0', 'false', 'off', 'no'].includes(normalized)) return false;
        if (['1', 'true', 'on', 'yes'].includes(normalized)) return true;
    }

    return Boolean(value);
}

function getComponentEnabled(key: ComponentKey): boolean {
    const component = siteComponents.value[key];

    if (component === undefined) return true;

    return normalizeEnabled(component.enabled);
}

function buildInitialComponents(): ComponentState {
    return componentKeys.reduce((components, key) => {
        components[key] = { enabled: getComponentEnabled(key) };
        return components;
    }, {} as ComponentState);
}

// Logo upload
const logoInput = ref<HTMLInputElement | null>(null);
const logoPreview = ref<string | null>(siteOverrides.value.logo_path ?? null);

// ─── Photo reorder ────────────────────────────────────────────────────────────
const dragSourceIndex = ref<number | null>(null);
const dragOverIndex   = ref<number | null>(null);

function imageDisplayUrl(rawPath: string): string {
    if (/^https?:\/\//i.test(rawPath)) return rawPath;
    return '/' + rawPath;
}

function onPhotoDragStart(index: number) {
    dragSourceIndex.value = index;
}

function onPhotoDragOver(event: DragEvent, index: number) {
    event.preventDefault();
    dragOverIndex.value = index;
    if (dragSourceIndex.value === null || dragSourceIndex.value === index) return;
    // Live swap so the grid animates as user drags
    const arr = [...form.images_order];
    const [removed] = arr.splice(dragSourceIndex.value, 1);
    arr.splice(index, 0, removed);
    form.images_order = arr;
    dragSourceIndex.value = index;
}

function onPhotoDragEnd() {
    dragSourceIndex.value = null;
    dragOverIndex.value   = null;
}

function triggerLogoUpload() {
    logoInput.value?.click();
}

function onLogoChange(event: Event) {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    if (file) {
        form.logo = file;
        logoPreview.value = URL.createObjectURL(file);
    }
}

const siteSocials = computed(() => siteData.value.socials ?? {});

function buildInitialSocials(): Socials {
    return {
        instagram: siteSocials.value.instagram ?? '',
        facebook: siteSocials.value.facebook ?? '',
        x: siteSocials.value.x ?? '',
        linkedin: siteSocials.value.linkedin ?? '',
    };
}

const siteQuickLinks = computed<QuickLink[]>(() => siteData.value.quickLinks ?? []);

function buildInitialQuickLinks(): QuickLink[] {
    return siteQuickLinks.value.map((l) => ({ label: l.label ?? '', link: l.link ?? '' }));
}

function addQuickLink() {
    form.quickLinks.push({ label: '', link: '' });
}

function removeQuickLink(index: number) {
    form.quickLinks.splice(index, 1);
}

// Auto-suggest a button label when a known URL is pasted
function onLinkBlur(index: number) {
    const link = form.quickLinks[index];
    if (!link || link.label.trim()) return; // don't overwrite an existing label
    const suggested = suggestLabelForUrl(link.link);
    if (suggested) link.label = suggested;
}

// Brand preview for a quick link row in the editor
function linkBrandStyle(url: string): Record<string, string> {
    const brand = detectPlatformBrand(url);
    return brand ? { backgroundColor: brand.bgColor, color: brand.textColor } : {};
}

function linkFavicon(url: string): string | null {
    return url ? getFaviconUrl(url) : null;
}

// ─── Colour palette ───────────────────────────────────────────────────────────
const existingPalette = computed(() => siteOverrides.value.palette ?? {});
const recommendedThemeId = computed(() => getRecommendedThemeId(siteData.value.primaryType));
const customPrimaryInput = ref('');
const customSecondaryInput = ref('');
const selectedThemeId = ref<string>(
    existingPalette.value.primary
        ? COLOUR_THEMES.find(t => t.palette.primary === existingPalette.value.primary)?.id ?? 'custom'
        : recommendedThemeId.value
);

// Live palette preview for the colour picker
const previewPalette = computed(() => {
    if (selectedThemeId.value === 'custom') {
        const p = customPrimaryInput.value || '#1e293b';
        const s = customSecondaryInput.value || undefined;
        return buildPaletteFromPrimary(p, s);
    }
    return COLOUR_THEMES.find(t => t.id === selectedThemeId.value)?.palette
        ?? COLOUR_THEMES[0].palette;
});

function selectTheme(id: string) {
    selectedThemeId.value = id;
    if (id !== 'custom') {
        const theme = COLOUR_THEMES.find(t => t.id === id);
        if (theme) {
            customPrimaryInput.value   = theme.palette.primary;
            customSecondaryInput.value = theme.palette.secondary;
            form.palette_primary       = theme.palette.primary;
            form.palette_secondary     = theme.palette.secondary;
        }
    }
}

// Keep form fields in sync with the live hex inputs (for custom mode)
watch(customPrimaryInput, v => { form.palette_primary = v; });
watch(customSecondaryInput, v => { form.palette_secondary = v; });

const existingBg = computed(() => siteOverrides.value.header_bg ?? { type: 'auto', value: '' });

// Derive initial palette form values from saved custom or auto theme
const initPaletteValues = () => {
    if (existingPalette.value.primary) {
        return { primary: existingPalette.value.primary, secondary: existingPalette.value.secondary ?? '' };
    }
    const theme = COLOUR_THEMES.find(t => t.id === recommendedThemeId.value) ?? COLOUR_THEMES[0];
    return { primary: theme.palette.primary, secondary: theme.palette.secondary };
};
const initPalette = initPaletteValues();
customPrimaryInput.value   = initPalette.primary;
customSecondaryInput.value = initPalette.secondary;

// Services
const siteServices = computed<Service[]>(() => siteData.value.services ?? []);

function buildInitialServices(): Service[] {
    return siteServices.value.map((s) => ({
        id:          s.id          ?? crypto.randomUUID(),
        name:        s.name        ?? '',
        description: s.description ?? null,
        price:       s.price       ?? null,
        currency:    s.currency    ?? 'GBP',
        show_price:  s.show_price  !== false,
        featured:    s.featured    === true,
    }));
}

// Track which service row is being inline-edited
const editingServiceIndex = ref<number | null>(null);

// Ref to the ServiceInlineEditor child component instance.
// Typing in the editor only re-renders that tiny child — Components.vue
// is completely still. We read the final values via getDraft() on close/save.
// When ref is placed inside v-for, Vue sets it to an array — we unwrap it.
type EditorInstance = InstanceType<typeof ServiceInlineEditor>;
const editorRef = ref<EditorInstance | EditorInstance[] | null>(null);

function flushDraft() {
    if (editingServiceIndex.value === null) return;
    const editor = Array.isArray(editorRef.value) ? editorRef.value[0] : editorRef.value;
    if (editor) {
        form.services[editingServiceIndex.value] = editor.getDraft();
    }
}

function addService() {
    flushDraft();
    const newService: Service = {
        id:          crypto.randomUUID(),
        name:        '',
        description: null,
        price:       null,
        currency:    'GBP',
        show_price:  true,
        featured:    false,
    };
    form.services.push(newService);
    editingServiceIndex.value = form.services.length - 1;
}

function removeService(index: number) {
    if (editingServiceIndex.value === index) {
        editingServiceIndex.value = null;
    } else if (editingServiceIndex.value !== null && editingServiceIndex.value > index) {
        editingServiceIndex.value--;
    }
    form.services.splice(index, 1);
}

function toggleServiceEdit(index: number) {
    if (editingServiceIndex.value === index) {
        flushDraft();
        editingServiceIndex.value = null;
    } else {
        flushDraft();
        editingServiceIndex.value = index;
    }
}

function toggleServiceFeatured(index: number) {
    // If this row's editor is open, flush first so we don't lose edits, then toggle
    if (editingServiceIndex.value === index) {
        flushDraft();
    }
    form.services[index].featured = !form.services[index].featured;
    // If the editor was open, re-open it with the updated featured value
    if (editingServiceIndex.value === index) {
        // editorRef will pick up the new initial.featured on next render
    }
}

const form = useForm<{
    components: ComponentState;
    overrides: { description: string; hidden_reviews: number[]; contact_email: string };
    socials: Socials;
    quickLinks: QuickLink[];
    whatsapp_number: string;
    logo: File | null;
    palette_primary: string;
    palette_secondary: string;
    header_bg_type: string;
    header_bg_value: string;
    header_bg_thumb: string;
    header_bg_credit: string;
    header_bg_credit_url: string;
    header_bg_image: File | null;
    services: Service[];
    services_heading: string;
    services_cta_label: string;
    services_cta_link: string;
    images_order: string[];
}>({
    components: buildInitialComponents(),
    overrides: {
        description:   siteOverrides.value.description ?? '',
        hidden_reviews: (siteOverrides.value.hidden_reviews as number[] | undefined) ?? [],
        contact_email: (siteOverrides.value.contact_email as string | undefined) ?? '',
    },
    socials: buildInitialSocials(),
    quickLinks: buildInitialQuickLinks(),
    whatsapp_number: siteData.value.whatsapp_number ?? '',
    logo: null,
    palette_primary:   initPalette.primary,
    palette_secondary: initPalette.secondary,
    header_bg_type:      existingBg.value.type       ?? 'auto',
    header_bg_value:     existingBg.value.value      ?? '',
    header_bg_thumb:     existingBg.value.thumb      ?? '',
    header_bg_credit:    existingBg.value.credit     ?? '',
    header_bg_credit_url: existingBg.value.credit_url ?? '',
    header_bg_image: null,
    services:          buildInitialServices(),
    services_heading:  siteData.value.services_heading  ?? '',
    services_cta_label: siteData.value.services_cta_label ?? '',
    services_cta_link:  siteData.value.services_cta_link  ?? '',
    images_order: (siteData.value.images as string[] | undefined) ?? [],
});

watch(
    () => page.props.site,
    () => {
        form.components = buildInitialComponents();
        form.overrides.description    = siteOverrides.value.description ?? '';
        form.overrides.hidden_reviews = (siteOverrides.value.hidden_reviews as number[] | undefined) ?? [];
        form.overrides.contact_email  = (siteOverrides.value.contact_email as string | undefined) ?? '';
        form.socials = buildInitialSocials();
        form.quickLinks = buildInitialQuickLinks();
        form.whatsapp_number = siteData.value.whatsapp_number ?? '';
        form.services          = buildInitialServices();
        form.services_heading  = siteData.value.services_heading  ?? '';
        form.services_cta_label = siteData.value.services_cta_label ?? '';
        form.services_cta_link  = siteData.value.services_cta_link  ?? '';
        editingServiceIndex.value = null;
        form.logo = null;
        logoPreview.value = siteOverrides.value.logo_path ?? null;
        form.images_order = (siteData.value.images as string[] | undefined) ?? [];
        const reinitPalette = initPaletteValues();
        form.palette_primary   = reinitPalette.primary;
        form.palette_secondary = reinitPalette.secondary;
        customPrimaryInput.value   = reinitPalette.primary;
        customSecondaryInput.value = reinitPalette.secondary;
        selectedThemeId.value = existingPalette.value.primary
            ? COLOUR_THEMES.find(t => t.palette.primary === existingPalette.value.primary)?.id ?? 'custom'
            : recommendedThemeId.value;
        const bg = siteOverrides.value.header_bg ?? { type: 'auto', value: '' };
        form.header_bg_type      = bg.type       ?? 'auto';
        form.header_bg_value     = bg.value      ?? '';
        form.header_bg_thumb     = bg.thumb      ?? '';
        form.header_bg_credit    = bg.credit     ?? '';
        form.header_bg_credit_url = bg.credit_url ?? '';
        form.header_bg_image = null;
    },
    { deep: true },
);

const saving = ref(false);

function saveForm() {
    // Commit any open inline service editor before submitting
    flushDraft();
    saving.value = true;

    form.post(dashboardComponents.url(), {
        forceFormData: true,
        onSuccess: () => {
            toast('Saved!');
            saving.value = false;
        },
        onError: () => {
            saving.value = false;
        },
    });
}

// ─── Computed helpers for read-only preview data ──────────────────────────────

// Header
const businessName = computed(() => siteData.value.displayName?.text ?? '');
const businessType = computed(() => siteData.value.primaryTypeDisplayName?.text ?? '');
const businessLocation = computed(() => {
    const components: Array<{ types: string[]; longText: string }> = siteData.value.addressComponents ?? [];
    const locality = components.find((c) => c.types?.includes('locality'))?.longText ?? '';
    const region = components.find((c) => c.types?.includes('administrative_area_level_1'))?.longText ?? '';
    if (locality && region) return `${locality}, ${region}`;
    return locality || region || '';
});

// About / Description
const googleDescription = computed(
    () => siteData.value.editorialSummary?.text ?? siteData.value.description ?? '',
);

const isGeneratingDescription = ref(false);
const generateDescriptionError = ref<string | null>(null);

const generateDescription = async () => {
    isGeneratingDescription.value = true;
    generateDescriptionError.value = null;
    try {
        const response = await axios.post('/dashboard/generate-description');
        form.overrides.description = response.data.description ?? '';
    } catch (err: any) {
        generateDescriptionError.value =
            err?.response?.data?.error ?? 'Something went wrong. Please try again.';
    } finally {
        isGeneratingDescription.value = false;
    }
};

// Normalise photo paths for the header background picker.
// New downloads store "storage/images/uuid.jpg" (correct — prepend '/' → '/storage/…').
// Legacy entries (pre-fix) were stored as "images/uuid.jpg" — normalise those too.
const googleImages = computed<string[]>(() =>
    ((siteData.value.images ?? []) as string[]).map((img) => {
        if (img.startsWith('storage/') || img.startsWith('/') || /^https?:\/\//.test(img)) {
            return img;
        }
        // Old format: "images/uuid.jpg" → "storage/images/uuid.jpg"
        return 'storage/' + img;
    }),
);

// Quick Actions
const phoneNumber = computed(
    () => siteData.value.nationalPhoneNumber ?? siteData.value.internationalPhoneNumber ?? '',
);

// Reviews
const rating = computed(() => siteData.value.rating ?? null);
const reviewCount = computed(() =>
    siteData.value.userRatingCount ?? siteData.value.reviews?.length ?? null,
);
const siteReviews = computed<Array<{ author: string; rating: number; text: string; time: string }>>(() =>
    ((siteData.value.reviews ?? []) as Array<Record<string, any>>).map((r) => ({
        author: r.authorAttribution?.displayName ?? 'Anonymous',
        rating: r.rating ?? 0,
        text:   r.text?.text ?? '',
        time:   r.publishTime ?? '',
    })),
);

function toggleReviewHidden(index: number) {
    const hidden = form.overrides.hidden_reviews;
    const pos = hidden.indexOf(index);
    if (pos === -1) {
        hidden.push(index);
    } else {
        hidden.splice(pos, 1);
    }
}

// Contact Info
const formattedAddress = computed(() => siteData.value.formattedAddress ?? '');
const googleEmail      = computed(() => (siteData.value.contact as string | undefined) ?? '');
const openingHoursPeriods = computed(
    () => siteData.value.regularOpeningHours?.periods?.length ?? null,
);
// ─── Section navigation ────────────────────────────────────────────────────────
type SectionId = 'design' | 'header' | 'about' | 'gallery' | 'quick_actions' | 'reviews' | 'contact' | 'services';
const activeSec = ref<SectionId>('design');
const sections: { id: SectionId; label: string; hint: string; icon: any }[] = [
    { id: 'design',        label: 'Colours & look',     hint: 'Pick colours for buttons',   icon: Palette  },
    { id: 'header',        label: 'Top of page',         hint: 'Logo + header background',   icon: Image    },
    { id: 'about',         label: 'About',               hint: 'A sentence about you',       icon: Pencil   },
    { id: 'gallery',       label: 'Photos',              hint: 'Photos from Google',         icon: Image    },
    { id: 'quick_actions', label: 'Buttons',             hint: 'Call, WhatsApp, links',      icon: Phone    },
    { id: 'reviews',       label: 'Reviews',             hint: 'Your star rating',           icon: Star     },
    { id: 'contact',       label: 'Contact details',     hint: 'Address, phone, hours',      icon: MapPin   },
    { id: 'services',      label: 'Services & Pricing',  hint: 'List what you offer',        icon: Sparkles },
];
</script>


<template>
    <div class="ec-wrap">

        <!-- ── Left nav ──────────────────────────────────────────────────────── -->
        <nav class="ec-nav">
            <button
                v-for="sec in sections"
                :key="sec.id"
                type="button"
                class="ec-nav-item"
                :class="{ 'ec-nav-item--active': activeSec === sec.id }"
                @click="activeSec = sec.id"
            >
                <component :is="sec.icon" class="ec-nav-item__icon" />
                <div class="ec-nav-item__text">
                    <div class="ec-nav-item__label">{{ sec.label }}</div>
                    <div class="ec-nav-item__hint">{{ sec.hint }}</div>
                </div>
            </button>
        </nav>

        <!-- ── Right panel ───────────────────────────────────────────────────── -->
        <div class="ec-panel">

            <!-- ── 0. Design / Colours ────────────────────────────────────────── -->
            <div v-if="activeSec === 'design'" class="ec-section">
                <div class="ec-sec-head-card">
                    <div class="ec-sec-head__title">Colours &amp; look</div>
                    <div class="ec-sec-head__sub">Pick a colour theme that matches your business. It'll be used for buttons and highlights across your site.</div>
                </div>

                <div class="ec-card ec-card--pad">
                    <div class="ec-rec-row">
                        <span class="ec-recommended-badge">✦ Recommended for your business type</span>
                        <span class="ec-rec-name">{{ COLOUR_THEMES.find(t => t.id === recommendedThemeId)?.name }}</span>
                    </div>

                    <div class="ec-theme-grid">
                        <button
                            v-for="theme in COLOUR_THEMES"
                            :key="theme.id"
                            type="button"
                            class="ec-theme-btn"
                            :class="{ 'ec-theme-btn--active': selectedThemeId === theme.id }"
                            @click="selectTheme(theme.id)"
                        >
                            <div class="ec-theme-swatches">
                                <span class="ec-swatch" :style="{ background: theme.palette.primary }" />
                                <span class="ec-swatch" :style="{ background: theme.palette.secondary }" />
                                <span class="ec-swatch" :style="{ background: theme.palette.primaryMuted }" />
                            </div>
                            <p class="ec-theme-name">{{ theme.name }}</p>
                            <span v-if="theme.id === recommendedThemeId" class="ec-theme-star" title="Recommended">✦</span>
                            <CheckCircle2 v-if="selectedThemeId === theme.id" class="ec-theme-check" />
                        </button>
                        <button
                            type="button"
                            class="ec-theme-btn"
                            :class="{ 'ec-theme-btn--active': selectedThemeId === 'custom' }"
                            @click="selectTheme('custom')"
                        >
                            <div class="ec-theme-swatches">
                                <span class="ec-swatch ec-swatch--dashed" />
                                <span class="ec-swatch ec-swatch--dashed" />
                            </div>
                            <p class="ec-theme-name">Custom</p>
                            <CheckCircle2 v-if="selectedThemeId === 'custom'" class="ec-theme-check" />
                        </button>
                    </div>

                    <div v-if="selectedThemeId === 'custom'" class="ec-custom-hex">
                        <div class="ec-field">
                            <label for="palette-primary" class="ec-field__label">Primary colour</label>
                            <div class="ec-color-row">
                                <span class="ec-color-swatch" :style="{ background: customPrimaryInput || '#1e293b' }" />
                                <Input id="palette-primary" v-model="customPrimaryInput" placeholder="#1e40af" class="ec-hex-input" maxlength="7" />
                            </div>
                            <span class="ec-field__hint">Buttons, accents, and highlights</span>
                        </div>
                        <div class="ec-field">
                            <label for="palette-secondary" class="ec-field__label">Secondary colour <span class="ec-label-opt">(optional)</span></label>
                            <div class="ec-color-row">
                                <span class="ec-color-swatch" :style="{ background: customSecondaryInput || previewPalette.secondary }" />
                                <Input id="palette-secondary" v-model="customSecondaryInput" :placeholder="previewPalette.secondary" class="ec-hex-input" maxlength="7" />
                            </div>
                            <span class="ec-field__hint">Leave blank to auto-generate from primary</span>
                        </div>
                    </div>

                    <div class="ec-palette-preview">
                        <p class="ec-palette-preview__label">Preview</p>
                        <div class="ec-palette-preview__strip">
                            <div class="ec-palette-circles">
                                <span class="ec-palette-dot" :style="{ background: previewPalette.primary }" />
                                <span class="ec-palette-dot" :style="{ background: previewPalette.secondary }" />
                                <span class="ec-palette-dot ec-palette-dot--sq" :style="{ background: previewPalette.primaryMuted }" />
                            </div>
                            <span class="ec-btn-preview ec-btn-preview--fill" :style="{ background: previewPalette.primary, color: previewPalette.primaryFg }">Call us</span>
                            <span class="ec-btn-preview ec-btn-preview--outline" :style="{ borderColor: previewPalette.primary, color: previewPalette.primary }">Message</span>
                        </div>
                    </div>
                    <p v-if="form.errors.palette_primary" class="ec-error">{{ form.errors.palette_primary }}</p>
                </div>
            </div>

            <!-- ── 1. Header / Top of page ───────────────────────────────────── -->
            <div v-if="activeSec === 'header'" class="ec-section">
                <div class="ec-sec-head-card">
                    <div class="ec-sec-head__left">
                        <div class="ec-sec-head__title">Top of page</div>
                        <div class="ec-sec-head__sub">Your business name, logo, and the background image shown at the top of your site.</div>
                    </div>
                    <div class="ec-sec-head__right">
                        <span class="ec-toggle-label">{{ form.components['header'].enabled ? 'Shows on your site' : 'Hidden from your site' }}</span>
                        <Switch id="toggle-header" v-model="form.components['header'].enabled" class="ec-switch" />
                    </div>
                </div>

                <div class="ec-card ec-card--pad">
                    <p class="ec-google-note"><ExternalLink class="ec-google-note__icon" /> This information is pulled from your Google Business Profile</p>
                    <div class="ec-info-grid">
                        <div class="ec-info-cell">
                            <p class="ec-info-cell__label">Business name</p>
                            <p class="ec-info-cell__value">{{ businessName || '—' }}</p>
                        </div>
                        <div class="ec-info-cell">
                            <p class="ec-info-cell__label">Type of business</p>
                            <p class="ec-info-cell__value">{{ businessType || '—' }}</p>
                        </div>
                        <div class="ec-info-cell">
                            <p class="ec-info-cell__label">Location</p>
                            <p class="ec-info-cell__value">{{ businessLocation || '—' }}</p>
                        </div>
                    </div>
                </div>

                <div class="ec-card ec-card--pad">
                    <div class="ec-field">
                        <label class="ec-field__label">Your logo</label>
                        <div class="ec-logo-row">
                            <img v-if="logoPreview" :src="logoPreview" alt="Logo preview" class="ec-logo-thumb" />
                            <div v-else class="ec-logo-placeholder">No logo yet</div>
                            <div class="ec-logo-actions">
                                <button type="button" class="ec-btn ec-btn--outline" @click="triggerLogoUpload">
                                    <Upload class="ec-btn__icon" />
                                    {{ logoPreview ? 'Change logo' : 'Upload a logo' }}
                                </button>
                                <span class="ec-field__hint">JPG, PNG or SVG. Max 2MB.</span>
                            </div>
                            <input ref="logoInput" type="file" accept="image/*" class="ec-hidden" @change="onLogoChange" />
                        </div>
                        <p v-if="form.errors.logo" class="ec-error">{{ form.errors.logo }}</p>
                    </div>
                </div>

                <div class="ec-card ec-card--pad">
                    <HeaderBackgroundPicker
                        :bg-type="(form.header_bg_type as any)"
                        :bg-value="form.header_bg_value"
                        :bg-thumb="form.header_bg_thumb"
                        :bg-credit="form.header_bg_credit"
                        :bg-credit-url="form.header_bg_credit_url"
                        :google-images="googleImages"
                        :business-name="businessName"
                        :business-type="businessType"
                        @update:bg-type="(v) => form.header_bg_type = v"
                        @update:bg-value="(v) => form.header_bg_value = v"
                        @update:bg-thumb="(v) => form.header_bg_thumb = v"
                        @update:bg-credit="(v) => form.header_bg_credit = v"
                        @update:bg-credit-url="(v) => form.header_bg_credit_url = v"
                        @update:image-file="(v) => form.header_bg_image = v"
                    />
                </div>
            </div>

            <!-- ── 2. About ───────────────────────────────────────────────────── -->
            <div v-if="activeSec === 'about'" class="ec-section">
                <div class="ec-sec-head-card">
                    <div class="ec-sec-head__left">
                        <div class="ec-sec-head__title">About</div>
                        <div class="ec-sec-head__sub">A short description of your business shown near the top of your site.</div>
                    </div>
                    <div class="ec-sec-head__right">
                        <span class="ec-toggle-label">{{ form.components['description'].enabled ? 'Shows on your site' : 'Hidden from your site' }}</span>
                        <Switch id="toggle-description" v-model="form.components['description'].enabled" class="ec-switch" />
                    </div>
                </div>

                <div class="ec-card ec-card--pad">
                    <p class="ec-google-note"><ExternalLink class="ec-google-note__icon" /> From your Google Business Profile</p>
                    <div class="ec-info-cell-full">
                        <p class="ec-info-cell__label">Google description</p>
                        <p v-if="googleDescription" class="ec-info-cell__value">{{ googleDescription }}</p>
                        <p v-else class="ec-info-cell__value ec-info-cell__value--empty">No description found on your Google Business Profile.</p>
                    </div>
                </div>

                <div class="ec-card ec-card--pad">
                    <div class="ec-field">
                        <label for="override-description" class="ec-field__label">Write your own description</label>
                        <Textarea
                            id="override-description"
                            v-model="form.overrides.description"
                            :placeholder="googleDescription || 'Tell customers what you do, where you work, and what makes you stand out...'"
                            rows="5"
                            class="ec-textarea"
                        />
                        <span class="ec-field__hint">Leave this empty to use the description from your Google Business Profile.</span>
                        <p v-if="form.errors['overrides.description']" class="ec-error">{{ form.errors['overrides.description'] }}</p>
                        <button
                            type="button"
                            class="ec-ai-btn"
                            :disabled="isGeneratingDescription"
                            @click="generateDescription"
                        >
                            <Loader2 v-if="isGeneratingDescription" :size="14" class="ec-ai-btn__spin" />
                            <Wand2 v-else :size="14" />
                            {{ isGeneratingDescription ? 'Writing…' : 'Write it for me' }}
                        </button>
                        <p v-if="generateDescriptionError" class="ec-error">{{ generateDescriptionError }}</p>
                    </div>
                </div>
            </div>

            <!-- ── 3. Gallery / Photos ─────────────────────────────────────────── -->
            <div v-if="activeSec === 'gallery'" class="ec-section">
                <div class="ec-sec-head-card">
                    <div class="ec-sec-head__left">
                        <div class="ec-sec-head__title">Photos</div>
                        <div class="ec-sec-head__sub">A gallery of photos from your Google Business Profile.</div>
                    </div>
                    <div class="ec-sec-head__right">
                        <span class="ec-toggle-label">{{ form.components['gallery'].enabled ? 'Shows on your site' : 'Hidden from your site' }}</span>
                        <Switch id="toggle-gallery" v-model="form.components['gallery'].enabled" class="ec-switch" />
                    </div>
                </div>

                <div class="ec-card ec-card--pad">
                    <p class="ec-google-note"><ExternalLink class="ec-google-note__icon" /> Photos come from your Google Business Profile</p>

                    <template v-if="form.images_order.length > 0">
                        <p class="ec-field__label" style="margin-bottom:10px;">
                            {{ form.images_order.length }} photo{{ form.images_order.length === 1 ? '' : 's' }}
                            <span class="ec-field__hint" style="font-size:12px;font-weight:400;margin-left:6px;">Drag to reorder — the first photo is shown as the featured image.</span>
                        </p>

                        <div class="ec-photo-grid">
                            <div
                                v-for="(rawPath, index) in form.images_order"
                                :key="rawPath"
                                class="ec-photo-item"
                                :class="{
                                    'ec-photo-item--dragging': dragSourceIndex === index,
                                    'ec-photo-item--over': dragOverIndex === index && dragSourceIndex !== index,
                                }"
                                draggable="true"
                                @dragstart="onPhotoDragStart(index)"
                                @dragover="onPhotoDragOver($event, index)"
                                @dragend="onPhotoDragEnd"
                            >
                                <img
                                    :src="imageDisplayUrl(rawPath)"
                                    :alt="`Photo ${index + 1}`"
                                    class="ec-photo-img"
                                    draggable="false"
                                />
                                <span v-if="index === 0" class="ec-photo-badge">Featured</span>
                                <span class="ec-photo-drag-hint">⠿</span>
                            </div>
                        </div>
                    </template>
                    <p v-else class="ec-info-cell__value ec-info-cell__value--empty">No photos downloaded yet. Use "Refresh from Google" on the Home tab to import them.</p>
                    <p class="ec-field__hint ec-field__hint--mt">To add or remove photos, update them on your Google Business Profile and use "Refresh from Google" to sync.</p>
                </div>
            </div>

            <!-- ── 4. Buttons / Quick Actions ─────────────────────────────────── -->
            <div v-if="activeSec === 'quick_actions'" class="ec-section">
                <div class="ec-sec-head-card">
                    <div class="ec-sec-head__left">
                        <div class="ec-sec-head__title">Buttons</div>
                        <div class="ec-sec-head__sub">Buttons that let visitors call you, message you, or click a link.</div>
                    </div>
                    <div class="ec-sec-head__right">
                        <span class="ec-toggle-label">{{ form.components['quick_actions'].enabled ? 'Shows on your site' : 'Hidden from your site' }}</span>
                        <Switch id="toggle-quick_actions" v-model="form.components['quick_actions'].enabled" class="ec-switch" />
                    </div>
                </div>

                <div class="ec-card ec-card--pad">
                    <p class="ec-google-note"><ExternalLink class="ec-google-note__icon" /> Phone number is pulled from your Google Business Profile</p>
                    <div class="ec-info-cell-full">
                        <p class="ec-info-cell__label">Phone number</p>
                        <p class="ec-info-cell__value">{{ phoneNumber || 'No phone number found on Google' }}</p>
                    </div>
                </div>

                <div class="ec-card ec-card--pad">
                    <div class="ec-field">
                        <label for="whatsapp-number" class="ec-field__label">
                            <MessageCircle class="ec-field__icon ec-field__icon--wa" />
                            WhatsApp number
                        </label>
                        <Input
                            id="whatsapp-number"
                            v-model="form.whatsapp_number"
                            placeholder="447911123456"
                            class="ec-input"
                        />
                        <span class="ec-field__hint">Adds a WhatsApp button. Include your country code without the + sign — UK numbers start with 44, e.g. <span class="ec-mono">447911123456</span>.</span>
                        <p v-if="form.errors.whatsapp_number" class="ec-error">{{ form.errors.whatsapp_number }}</p>
                    </div>
                </div>

                <div class="ec-card ec-card--pad">
                    <div class="ec-field-header">
                        <div class="ec-field__label">Custom buttons</div>
                        <p class="ec-field__hint">Add buttons that link to booking pages, menus, price lists, or anywhere else.</p>
                    </div>

                    <div v-if="form.quickLinks.length > 0" class="ec-link-list">
                        <div
                            v-for="(link, index) in form.quickLinks"
                            :key="index"
                            class="ec-link-row"
                        >
                            <div class="ec-link-inputs">
                                <div class="ec-field ec-field--inline">
                                    <label :for="`ql-label-${index}`" class="ec-field__label">Button label</label>
                                    <Input :id="`ql-label-${index}`" v-model="link.label" placeholder="e.g. Book a quote" class="ec-input" />
                                </div>
                                <div class="ec-field ec-field--inline">
                                    <label :for="`ql-link-${index}`" class="ec-field__label">Link</label>
                                    <Input :id="`ql-link-${index}`" v-model="link.link" placeholder="https://..." type="url" class="ec-input" @blur="onLinkBlur(index)" />
                                </div>
                            </div>
                            <div class="ec-link-row__actions">
                                <div v-if="link.label || link.link" class="ec-link-preview">
                                    <span class="ec-link-preview__label">Preview:</span>
                                    <span
                                        :style="linkBrandStyle(link.link)"
                                        class="ec-link-pill"
                                        :class="!detectPlatformBrand(link.link) ? 'ec-link-pill--dark' : ''"
                                    >
                                        {{ link.label || 'Button label' }}
                                        <img
                                            v-if="linkFavicon(link.link)"
                                            :src="linkFavicon(link.link)!"
                                            class="ec-link-favicon"
                                            aria-hidden="true"
                                        />
                                    </span>
                                </div>
                                <button
                                    type="button"
                                    class="ec-icon-btn ec-icon-btn--danger"
                                    @click="removeQuickLink(index)"
                                    :aria-label="`Remove button ${index + 1}`"
                                >
                                    <Trash2 class="ec-icon-btn__icon" />
                                </button>
                            </div>
                        </div>
                    </div>
                    <p v-else class="ec-empty-hint">No custom buttons yet.</p>

                    <button type="button" class="ec-btn ec-btn--outline ec-btn--full ec-btn--mt" @click="addQuickLink">
                        <Plus class="ec-btn__icon" /> Add a button
                    </button>
                </div>
            </div>

            <!-- ── 5. Reviews ──────────────────────────────────────────────────── -->
            <div v-if="activeSec === 'reviews'" class="ec-section">
                <div class="ec-sec-head-card">
                    <div class="ec-sec-head__left">
                        <div class="ec-sec-head__title">Reviews</div>
                        <div class="ec-sec-head__sub">Your Google star rating and customer reviews.</div>
                    </div>
                    <div class="ec-sec-head__right">
                        <span class="ec-toggle-label">{{ form.components['reviews'].enabled ? 'Shows on your site' : 'Hidden from your site' }}</span>
                        <Switch id="toggle-reviews" v-model="form.components['reviews'].enabled" class="ec-switch" />
                    </div>
                </div>

                <div class="ec-card ec-card--pad">
                    <p class="ec-google-note"><ExternalLink class="ec-google-note__icon" /> Reviews are pulled from your Google Business Profile</p>
                    <div class="ec-info-grid">
                        <div class="ec-info-cell">
                            <p class="ec-info-cell__label">Star rating</p>
                            <p class="ec-info-cell__value">{{ rating !== null ? `${rating} ★` : 'No rating yet' }}</p>
                        </div>
                        <div class="ec-info-cell">
                            <p class="ec-info-cell__label">Total reviews</p>
                            <p class="ec-info-cell__value">{{ reviewCount !== null ? `${reviewCount} review${reviewCount === 1 ? '' : 's'}` : 'No reviews yet' }}</p>
                        </div>
                    </div>
                </div>

                <div v-if="siteReviews.length > 0" class="ec-card ec-card--pad">
                    <div class="ec-field-header">
                        <div class="ec-field__label">Individual reviews</div>
                        <p class="ec-field__hint">Hide any reviews you don't want to show on your site. Hidden reviews are not deleted from Google — only from your site.</p>
                    </div>
                    <div class="ec-review-list">
                        <div
                            v-for="(review, i) in siteReviews"
                            :key="i"
                            class="ec-review-row"
                            :class="{ 'ec-review-row--hidden': form.overrides.hidden_reviews.includes(i) }"
                        >
                            <div class="ec-review-body">
                                <div class="ec-review-meta">
                                    <span class="ec-review-author">{{ review.author }}</span>
                                    <span class="ec-review-stars">{{ '★'.repeat(review.rating) }}{{ '☆'.repeat(5 - review.rating) }}</span>
                                </div>
                                <p class="ec-review-text">{{ review.text || 'No text' }}</p>
                            </div>
                            <button
                                type="button"
                                class="ec-visibility-btn"
                                :class="{ 'ec-visibility-btn--hidden': form.overrides.hidden_reviews.includes(i) }"
                                @click="toggleReviewHidden(i)"
                                :aria-label="form.overrides.hidden_reviews.includes(i) ? `Show review from ${review.author}` : `Hide review from ${review.author}`"
                            >
                                <EyeOff v-if="form.overrides.hidden_reviews.includes(i)" class="ec-vis-icon" />
                                <Eye v-else class="ec-vis-icon" />
                                {{ form.overrides.hidden_reviews.includes(i) ? 'Hidden' : 'Visible' }}
                            </button>
                        </div>
                    </div>
                </div>
                <p v-else class="ec-empty-hint ec-empty-hint--top">No reviews imported yet.</p>
            </div>

            <!-- ── 6. Contact ─────────────────────────────────────────────────── -->
            <div v-if="activeSec === 'contact'" class="ec-section">
                <div class="ec-sec-head-card">
                    <div class="ec-sec-head__left">
                        <div class="ec-sec-head__title">Contact details</div>
                        <div class="ec-sec-head__sub">Your address, phone number, opening hours, and social media links.</div>
                    </div>
                    <div class="ec-sec-head__right">
                        <span class="ec-toggle-label">{{ form.components['contact'].enabled ? 'Shows on your site' : 'Hidden from your site' }}</span>
                        <Switch id="toggle-contact" v-model="form.components['contact'].enabled" class="ec-switch" />
                    </div>
                </div>

                <div class="ec-card ec-card--pad">
                    <p class="ec-google-note"><ExternalLink class="ec-google-note__icon" /> Contact details are pulled from your Google Business Profile</p>
                    <div class="ec-info-grid">
                        <div class="ec-info-cell">
                            <p class="ec-info-cell__label">Address</p>
                            <p class="ec-info-cell__value">{{ formattedAddress || 'Not available' }}</p>
                        </div>
                        <div class="ec-info-cell">
                            <p class="ec-info-cell__label">Phone</p>
                            <p class="ec-info-cell__value">{{ phoneNumber || 'Not available' }}</p>
                        </div>
                        <div class="ec-info-cell">
                            <p class="ec-info-cell__label">Opening hours</p>
                            <p class="ec-info-cell__value">{{ openingHoursPeriods !== null ? `${openingHoursPeriods} day${openingHoursPeriods === 1 ? '' : 's'} configured` : 'Not available' }}</p>
                        </div>
                    </div>
                </div>

                <div class="ec-card ec-card--pad">
                    <div class="ec-field">
                        <label for="contact-email" class="ec-field__label">Contact email</label>
                        <div class="ec-info-cell-inline">
                            <p class="ec-info-cell__label">Email from Google</p>
                            <p v-if="googleEmail" class="ec-info-cell__value">{{ googleEmail }}</p>
                            <p v-else class="ec-info-cell__value ec-info-cell__value--empty">No email found on your Google Business Profile.</p>
                        </div>
                        <Input
                            id="contact-email"
                            v-model="form.overrides.contact_email"
                            type="email"
                            placeholder="hello@yourbusiness.com"
                            class="ec-input"
                        />
                        <span class="ec-field__hint">Used for the Message button{{ isPremium ? ' and contact form' : '' }}. Leave blank to use the email from your Google Business Profile.</span>
                        <p v-if="form.errors['overrides.contact_email']" class="ec-error">{{ form.errors['overrides.contact_email'] }}</p>
                    </div>
                </div>

                <div class="ec-card ec-card--pad" :class="{ 'ec-card--dimmed': !isPremium }">
                    <div class="ec-toggle-row">
                        <div>
                            <div class="ec-toggle-row__label">
                                Contact form
                                <span v-if="!isPremium" class="ec-premium-badge">
                                    <Sparkles class="ec-premium-badge__icon" /> Premium
                                </span>
                            </div>
                            <div class="ec-toggle-row__hint">Show a "Send us a message" form so visitors can email you directly.</div>
                        </div>
                        <div class="ec-toggle-row__control">
                            <template v-if="isPremium">
                                <span class="ec-toggle-label">{{ form.components['contact_form'].enabled ? 'Shows on your site' : 'Hidden from your site' }}</span>
                                <Switch id="toggle-contact_form" v-model="form.components['contact_form'].enabled" class="ec-switch" />
                            </template>
                            <Lock v-else class="ec-lock-icon" />
                        </div>
                    </div>
                </div>

                <div class="ec-card ec-card--pad">
                    <div class="ec-field-header">
                        <div class="ec-field__label">Social media links</div>
                        <p class="ec-field__hint">Add links to your social media pages so customers can follow you.</p>
                    </div>
                    <div class="ec-socials-list">
                        <div class="ec-field">
                            <label for="social-instagram" class="ec-field__label">Instagram page link</label>
                            <Input id="social-instagram" v-model="form.socials.instagram" placeholder="https://www.instagram.com/yourbusiness" type="url" class="ec-input" />
                            <p v-if="form.errors['socials.instagram']" class="ec-error">{{ form.errors['socials.instagram'] }}</p>
                        </div>
                        <div class="ec-field">
                            <label for="social-facebook" class="ec-field__label">Facebook page link</label>
                            <Input id="social-facebook" v-model="form.socials.facebook" placeholder="https://www.facebook.com/yourbusiness" type="url" class="ec-input" />
                            <p v-if="form.errors['socials.facebook']" class="ec-error">{{ form.errors['socials.facebook'] }}</p>
                        </div>
                        <div class="ec-field">
                            <label for="social-x" class="ec-field__label">X (Twitter) page link</label>
                            <Input id="social-x" v-model="form.socials.x" placeholder="https://x.com/yourbusiness" type="url" class="ec-input" />
                            <p v-if="form.errors['socials.x']" class="ec-error">{{ form.errors['socials.x'] }}</p>
                        </div>
                        <div class="ec-field">
                            <label for="social-linkedin" class="ec-field__label">LinkedIn page link</label>
                            <Input id="social-linkedin" v-model="form.socials.linkedin" placeholder="https://www.linkedin.com/company/yourbusiness" type="url" class="ec-input" />
                            <p v-if="form.errors['socials.linkedin']" class="ec-error">{{ form.errors['socials.linkedin'] }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── 7. Services ────────────────────────────────────────────────── -->
            <div v-if="activeSec === 'services'" class="ec-section">
                <div class="ec-sec-head-card">
                    <div class="ec-sec-head__left">
                        <div class="ec-sec-head__title">Services &amp; Pricing</div>
                        <div class="ec-sec-head__sub">List what you offer so customers know what to expect.</div>
                    </div>
                    <div class="ec-sec-head__right">
                        <span class="ec-toggle-label">{{ form.components['services'].enabled ? 'Shows on your site' : 'Hidden from your site' }}</span>
                        <Switch id="toggle-services" v-model="form.components['services'].enabled" class="ec-switch" />
                    </div>
                </div>

                <div class="ec-card ec-card--pad">
                    <div class="ec-field">
                        <label for="services-heading" class="ec-field__label">Section heading</label>
                        <Input id="services-heading" v-model="form.services_heading" placeholder="Our Services" class="ec-input" />
                    </div>
                </div>

                <div class="ec-card ec-card--pad">
                    <div v-if="form.services.length > 0" class="ec-service-list">
                        <div
                            v-for="(service, index) in form.services"
                            :key="service.id"
                            class="ec-service-item"
                        >
                            <div class="ec-service-row">
                                <button
                                    type="button"
                                    class="ec-star-btn"
                                    :class="{ 'ec-star-btn--on': service.featured }"
                                    @click="toggleServiceFeatured(index)"
                                    :title="service.featured ? 'Remove from featured' : 'Mark as featured'"
                                >
                                    <Star class="ec-star-icon" :fill="service.featured ? 'currentColor' : 'none'" />
                                </button>
                                <div class="ec-service-info">
                                    <p class="ec-service-name">{{ service.name || '(no name)' }}</p>
                                    <p v-if="service.price && service.show_price" class="ec-service-price">{{ formatPrice(service.price, service.currency) }}</p>
                                    <p v-else class="ec-service-price ec-service-price--empty">No price</p>
                                </div>
                                <div class="ec-service-actions">
                                    <button type="button" class="ec-icon-btn" @click="toggleServiceEdit(index)">
                                        <X v-if="editingServiceIndex === index" class="ec-icon-btn__icon" />
                                        <Pencil v-else class="ec-icon-btn__icon" />
                                    </button>
                                    <button type="button" class="ec-icon-btn ec-icon-btn--danger" @click="removeService(index)">
                                        <Trash2 class="ec-icon-btn__icon" />
                                    </button>
                                </div>
                            </div>
                            <ServiceInlineEditor
                                v-if="editingServiceIndex === index"
                                ref="editorRef"
                                :initial="service"
                            />
                        </div>
                    </div>
                    <p v-else class="ec-empty-hint">No services added yet. Click "Add a service" below to get started.</p>

                    <button type="button" class="ec-btn ec-btn--outline ec-btn--full ec-btn--mt" @click="addService">
                        <Plus class="ec-btn__icon" /> Add a service
                    </button>
                </div>

                <div class="ec-card ec-card--pad">
                    <div class="ec-field-header">
                        <div class="ec-field__label">Call-to-action button <span class="ec-label-opt">(optional)</span></div>
                        <p class="ec-field__hint">A button shown below your services list. Leave the link blank to use your phone number instead.</p>
                    </div>
                    <div class="ec-cta-grid">
                        <div class="ec-field">
                            <label for="services-cta-label" class="ec-field__label">Button text</label>
                            <Input id="services-cta-label" v-model="form.services_cta_label" placeholder="e.g. Get a free quote" class="ec-input" />
                        </div>
                        <div class="ec-field">
                            <label for="services-cta-link" class="ec-field__label">Button link</label>
                            <Input id="services-cta-link" v-model="form.services_cta_link" type="url" placeholder="https://..." class="ec-input" />
                            <p v-if="form.errors['services_cta_link']" class="ec-error">{{ form.errors['services_cta_link'] }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Save ──────────────────────────────────────────────────────── -->
            <div class="ec-save-row">
                <button type="button" class="ec-save-btn" :disabled="saving" @click.prevent="saveForm">
                    <Loader2 v-show="saving" class="ec-spin" />
                    {{ saving ? 'Saving…' : 'Save changes' }}
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* ── Layout ──────────────────────────────────────────────────────────────── */
.ec-wrap {
    display: flex;
    gap: 0;
    align-items: flex-start;
    min-height: 0;
}

.ec-nav {
    width: 256px;
    flex-shrink: 0;
    display: flex;
    flex-direction: column;
    gap: 2px;
    padding: 4px 0;
    position: sticky;
    top: 24px;
}

.ec-nav-item {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 13px 16px;
    border-radius: 12px;
    border: none;
    border-left: 3px solid transparent;
    background: transparent;
    cursor: pointer;
    text-align: left;
    width: 100%;
    transition: background 0.12s;
}
.ec-nav-item:hover { background: var(--db-panel); }
.ec-nav-item--active {
    background: color-mix(in srgb, var(--db-accent) 10%, transparent);
    border-left-color: var(--db-accent);
}
.ec-nav-item__icon { width: 22px; height: 22px; color: var(--db-ink-soft); flex-shrink: 0; }
.ec-nav-item--active .ec-nav-item__icon { color: var(--db-accent); }
.ec-nav-item__label { font-size: 15px; font-weight: 600; color: var(--db-ink); line-height: 1.2; }
.ec-nav-item--active .ec-nav-item__label { color: var(--db-accent); }
.ec-nav-item__hint { font-size: 12px; color: var(--db-ink-soft); margin-top: 2px; }

.ec-panel {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 16px;
    padding-left: 28px;
}

/* ── Section ─────────────────────────────────────────────────────────────── */
.ec-section { display: flex; flex-direction: column; gap: 16px; }

/* ── Cards ───────────────────────────────────────────────────────────────── */
.ec-card {
    background: var(--db-surface);
    border: 1.5px solid var(--db-line);
    border-radius: 14px;
    overflow: hidden;
}
.ec-card--pad { padding: 24px; }
.ec-card--dimmed { opacity: 0.6; }

/* ── Section header card ─────────────────────────────────────────────────── */
.ec-sec-head-card {
    background: var(--db-surface);
    border: 1.5px solid var(--db-line);
    border-radius: 14px;
    padding: 20px 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
}
.ec-sec-head__left { flex: 1; }
.ec-sec-head__title { font-size: 22px; font-weight: 800; color: var(--db-ink); }
.ec-sec-head__sub { font-size: 14px; color: var(--db-ink-soft); margin-top: 5px; line-height: 1.5; max-width: 420px; }
.ec-sec-head__right { display: flex; align-items: center; gap: 10px; flex-shrink: 0; }
.ec-toggle-label { font-size: 13px; color: var(--db-ink-soft); white-space: nowrap; }
.ec-switch { flex-shrink: 0; }

/* ── Google note ─────────────────────────────────────────────────────────── */
.ec-google-note {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    color: var(--db-ink-soft);
    margin-bottom: 16px;
}
.ec-google-note__icon { width: 13px; height: 13px; flex-shrink: 0; }

/* ── Info cells ──────────────────────────────────────────────────────────── */
.ec-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 10px;
}
.ec-info-cell {
    background: var(--db-panel);
    border-radius: 10px;
    padding: 12px 14px;
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.ec-info-cell-full {
    background: var(--db-panel);
    border-radius: 10px;
    padding: 12px 14px;
}
.ec-info-cell-inline {
    background: var(--db-panel);
    border-radius: 10px;
    padding: 12px 14px;
    margin-bottom: 12px;
}
.ec-info-cell__label {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--db-ink-soft);
    margin-bottom: 2px;
}
.ec-info-cell__value { font-size: 14px; font-weight: 500; color: var(--db-ink); line-height: 1.4; }
.ec-info-cell__value--empty { font-style: italic; color: var(--db-ink-soft); font-weight: 400; }

/* ── Fields ──────────────────────────────────────────────────────────────── */
.ec-field { display: flex; flex-direction: column; gap: 8px; }
.ec-field--inline { flex: 1; }
.ec-field-header { margin-bottom: 16px; }
.ec-field__label {
    font-size: 15px;
    font-weight: 600;
    color: var(--db-ink);
    display: flex;
    align-items: center;
    gap: 6px;
}
.ec-field__hint { font-size: 13px; color: var(--db-ink-soft); line-height: 1.5; }
.ec-field__hint--mt { margin-top: 12px; }
.ec-field__icon { width: 16px; height: 16px; }
.ec-field__icon--wa { color: #25D366; }
.ec-label-opt { font-weight: 400; color: var(--db-ink-soft); }

.ec-input {
    height: 48px !important;
    font-size: 15px !important;
    border-radius: 10px !important;
    border: 1.5px solid var(--db-line) !important;
    background: #fff !important;
}
.ec-textarea {
    font-size: 15px !important;
    border-radius: 10px !important;
    border: 1.5px solid var(--db-line) !important;
    background: #fff !important;
    resize: vertical;
}
.ec-mono { font-family: monospace; font-size: 12px; background: var(--db-panel); padding: 1px 6px; border-radius: 4px; }

/* ── Buttons ─────────────────────────────────────────────────────────────── */
.ec-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    height: 48px;
    padding: 0 20px;
    border-radius: 10px;
    font-family: inherit;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    border: none;
    transition: opacity 0.1s, background 0.1s;
}
.ec-btn--outline {
    background: transparent;
    border: 1.5px solid var(--db-line);
    color: var(--db-ink);
}
.ec-btn--outline:hover { background: var(--db-panel); }
.ec-btn--full { width: 100%; }
.ec-btn--mt { margin-top: 12px; }
.ec-btn__icon { width: 18px; height: 18px; }

.ec-icon-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 38px;
    height: 38px;
    border: none;
    background: transparent;
    border-radius: 8px;
    cursor: pointer;
    color: var(--db-ink-soft);
    transition: all 0.1s;
    flex-shrink: 0;
}
.ec-icon-btn:hover { background: var(--db-panel); color: var(--db-ink); }
.ec-icon-btn--danger:hover { background: #fee2e2; color: #dc2626; }
.ec-icon-btn__icon { width: 16px; height: 16px; }

/* ── Save row ────────────────────────────────────────────────────────────── */
.ec-save-row { display: flex; justify-content: flex-end; padding-top: 8px; }
.ec-save-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    height: 52px;
    padding: 0 32px;
    border-radius: 10px;
    background: var(--db-accent);
    color: var(--db-accent-fg);
    border: none;
    font-family: inherit;
    font-size: 17px;
    font-weight: 700;
    cursor: pointer;
    transition: opacity 0.1s;
}
.ec-save-btn:disabled { opacity: 0.5; cursor: not-allowed; }
.ec-save-btn:hover:not(:disabled) { opacity: 0.9; }
.ec-spin { width: 18px; height: 18px; animation: ec-spin 1s linear infinite; }
@keyframes ec-spin { to { transform: rotate(360deg); } }

/* ── Logo ────────────────────────────────────────────────────────────────── */
.ec-logo-row { display: flex; align-items: center; gap: 16px; flex-wrap: wrap; }
.ec-logo-thumb { width: 80px; height: 80px; border-radius: 10px; border: 1.5px solid var(--db-line); object-fit: contain; background: #fff; flex-shrink: 0; }
.ec-logo-placeholder { width: 80px; height: 80px; border-radius: 10px; border: 1.5px dashed var(--db-line); background: var(--db-panel); display: flex; align-items: center; justify-content: center; font-size: 12px; color: var(--db-ink-soft); text-align: center; padding: 8px; flex-shrink: 0; }
.ec-logo-actions { display: flex; flex-direction: column; gap: 8px; }
.ec-hidden { display: none; }

/* ── Colour themes ───────────────────────────────────────────────────────── */
.ec-rec-row { display: flex; align-items: center; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
.ec-rec-name { font-size: 14px; font-weight: 600; color: var(--db-ink); }
.ec-recommended-badge {
    display: inline-flex;
    align-items: center;
    padding: 4px 10px;
    border-radius: 999px;
    background: #fef3c7;
    color: #92400e;
    font-size: 12px;
    font-weight: 700;
    border: 1px solid #fde68a;
}
.ec-theme-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
    gap: 10px;
    margin-bottom: 20px;
}
.ec-theme-btn {
    position: relative;
    border: 2px solid transparent;
    border-radius: 12px;
    padding: 12px;
    background: var(--db-panel);
    cursor: pointer;
    text-align: left;
    transition: all 0.12s;
}
.ec-theme-btn:hover { background: var(--db-line-soft, var(--db-line)); }
.ec-theme-btn--active { border-color: var(--db-ink); }
.ec-theme-swatches { display: flex; gap: 4px; margin-bottom: 8px; }
.ec-swatch { width: 20px; height: 20px; border-radius: 50%; flex-shrink: 0; }
.ec-swatch--dashed { border: 2px dashed var(--db-line); background: transparent; }
.ec-theme-name { font-size: 12px; font-weight: 600; color: var(--db-ink); }
.ec-theme-star { position: absolute; top: 8px; right: 8px; font-size: 11px; color: #f59e0b; }
.ec-theme-check { position: absolute; bottom: 8px; right: 8px; width: 14px; height: 14px; color: var(--db-ink); }

.ec-custom-hex {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    border: 1.5px solid var(--db-line);
    border-radius: 12px;
    padding: 16px;
    background: var(--db-panel);
    margin-bottom: 20px;
}
.ec-color-row { display: flex; align-items: center; gap: 8px; }
.ec-color-swatch { width: 36px; height: 36px; border-radius: 8px; border: 1.5px solid var(--db-line); flex-shrink: 0; }
.ec-hex-input { flex: 1 !important; height: 36px !important; font-family: monospace !important; font-size: 14px !important; }

.ec-palette-preview { }
.ec-palette-preview__label { font-size: 14px; font-weight: 600; color: var(--db-ink); margin-bottom: 8px; }
.ec-palette-preview__strip {
    display: flex;
    align-items: center;
    gap: 12px;
    border: 1.5px solid var(--db-line);
    border-radius: 12px;
    padding: 16px;
    flex-wrap: wrap;
}
.ec-palette-circles { display: flex; gap: 8px; }
.ec-palette-dot { width: 30px; height: 30px; border-radius: 50%; }
.ec-palette-dot--sq { border-radius: 8px; border: 1.5px solid var(--db-line); }
.ec-btn-preview { display: inline-flex; align-items: center; border-radius: 999px; padding: 6px 16px; font-size: 14px; font-weight: 600; }
.ec-btn-preview--outline { border: 2px solid; background: transparent; }

/* ── Quick links ─────────────────────────────────────────────────────────── */
.ec-link-list { display: flex; flex-direction: column; gap: 12px; }
.ec-link-row {
    display: flex;
    flex-direction: column;
    gap: 10px;
    border: 1.5px solid var(--db-line);
    border-radius: 10px;
    padding: 14px;
}
.ec-link-inputs { display: flex; gap: 12px; }
.ec-link-row__actions { display: flex; align-items: center; justify-content: space-between; }
.ec-link-preview { display: flex; align-items: center; gap: 8px; }
.ec-link-preview__label { font-size: 12px; color: var(--db-ink-soft); }
.ec-link-pill { display: inline-flex; align-items: center; gap: 6px; border-radius: 8px; padding: 6px 12px; font-size: 13px; font-weight: 500; }
.ec-link-pill--dark { background: #0f172a; color: #fff; }
.ec-link-favicon { width: 14px; height: 14px; border-radius: 3px; object-fit: contain; }

/* ── Reviews ─────────────────────────────────────────────────────────────── */
.ec-review-list { display: flex; flex-direction: column; gap: 8px; }
.ec-review-row {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    border: 1.5px solid var(--db-line);
    border-radius: 10px;
    padding: 12px 14px;
    background: var(--db-surface);
    transition: opacity 0.15s;
}
.ec-review-row--hidden { opacity: 0.5; background: var(--db-panel); }
.ec-review-body { flex: 1; min-width: 0; }
.ec-review-meta { display: flex; align-items: center; gap: 8px; margin-bottom: 4px; }
.ec-review-author { font-size: 14px; font-weight: 600; color: var(--db-ink); }
.ec-review-stars { font-size: 12px; color: #f59e0b; letter-spacing: 1px; }
.ec-review-text {
    font-size: 13px;
    color: var(--db-ink-soft);
    line-height: 1.4;
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}
.ec-visibility-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 8px;
    border: 1.5px solid var(--db-line);
    background: var(--db-panel);
    font-size: 13px;
    font-weight: 500;
    color: var(--db-ink);
    cursor: pointer;
    white-space: nowrap;
    flex-shrink: 0;
    font-family: inherit;
    transition: all 0.1s;
}
.ec-visibility-btn--hidden { color: var(--db-ink-soft); background: transparent; }
.ec-vis-icon { width: 14px; height: 14px; }

/* ── Services ────────────────────────────────────────────────────────────── */
.ec-service-list { display: flex; flex-direction: column; gap: 8px; margin-bottom: 4px; }
.ec-service-item { border: 1.5px solid var(--db-line); border-radius: 12px; overflow: hidden; }
.ec-service-row { display: flex; align-items: center; gap: 12px; padding: 12px 14px; background: var(--db-panel); }
.ec-star-btn { background: none; border: none; cursor: pointer; padding: 4px; color: var(--db-ink-soft); transition: color 0.1s; flex-shrink: 0; }
.ec-star-btn--on { color: #f59e0b; }
.ec-star-icon { width: 16px; height: 16px; }
.ec-service-info { flex: 1; min-width: 0; }
.ec-service-name { font-size: 15px; font-weight: 600; color: var(--db-ink); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.ec-service-price { font-size: 13px; color: var(--db-ink-soft); }
.ec-service-price--empty { font-style: italic; }
.ec-service-actions { display: flex; align-items: center; gap: 4px; flex-shrink: 0; }

/* ── Toggle rows ─────────────────────────────────────────────────────────── */
.ec-toggle-row { display: flex; align-items: center; justify-content: space-between; gap: 16px; }
.ec-toggle-row__label { font-size: 16px; font-weight: 700; color: var(--db-ink); display: flex; align-items: center; gap: 8px; }
.ec-toggle-row__hint { font-size: 14px; color: var(--db-ink-soft); line-height: 1.5; margin-top: 4px; max-width: 480px; }
.ec-toggle-row__control { display: flex; align-items: center; gap: 10px; flex-shrink: 0; }
.ec-lock-icon { width: 18px; height: 18px; color: var(--db-ink-soft); }

/* ── Premium badge ───────────────────────────────────────────────────────── */
.ec-premium-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 2px 8px;
    border-radius: 999px;
    background: #fef9c3;
    color: #854d0e;
    font-size: 12px;
    font-weight: 700;
}
.ec-premium-badge__icon { width: 11px; height: 11px; }

/* ── CTA / Socials ───────────────────────────────────────────────────────── */
.ec-cta-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.ec-socials-list { display: flex; flex-direction: column; gap: 16px; }

/* ── Misc ────────────────────────────────────────────────────────────────── */
.ec-empty-hint { font-size: 14px; color: var(--db-ink-soft); font-style: italic; }
.ec-empty-hint--top { margin-top: 8px; }
.ec-error { font-size: 13px; color: var(--db-danger); font-weight: 500; }

/* ── AI generate button ─────────────────────────────────────────────── */
.ec-ai-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 0 14px; height: 34px; border-radius: 8px;
    font-family: inherit; font-size: 13px; font-weight: 600;
    background: var(--db-panel); color: var(--db-ink);
    border: 1.5px solid var(--db-line);
    cursor: pointer; transition: border-color 0.15s, background 0.15s;
}
.ec-ai-btn:hover:not(:disabled) { border-color: var(--db-accent); background: var(--db-accent-soft); color: var(--db-accent); }
.ec-ai-btn:disabled { opacity: 0.5; cursor: not-allowed; }
.ec-ai-btn__spin { animation: ec-spin 1s linear infinite; }
@keyframes ec-spin { to { transform: rotate(360deg); } }

/* ── Photo reorder grid ──────────────────────────────────────────────────── */
.ec-photo-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(88px, 1fr));
    gap: 8px;
    margin-bottom: 4px;
}
.ec-photo-item {
    position: relative;
    border-radius: 10px;
    overflow: hidden;
    cursor: grab;
    border: 2px solid transparent;
    transition: opacity 0.15s, border-color 0.15s, transform 0.1s;
    aspect-ratio: 1;
    background: var(--db-panel);
    user-select: none;
}
.ec-photo-item:hover { border-color: var(--db-accent); }
.ec-photo-item--dragging { opacity: 0.35; cursor: grabbing; }
.ec-photo-item--over { border-color: var(--db-accent); box-shadow: 0 0 0 3px color-mix(in srgb, var(--db-accent) 25%, transparent); }
.ec-photo-img {
    width: 100%; height: 100%;
    object-fit: cover; display: block;
    pointer-events: none;
}
.ec-photo-badge {
    position: absolute; bottom: 4px; left: 4px;
    background: rgba(0,0,0,0.65); color: #fff;
    font-size: 10px; font-weight: 700;
    padding: 2px 6px; border-radius: 4px;
    line-height: 1.4; pointer-events: none;
}
.ec-photo-drag-hint {
    position: absolute; top: 4px; right: 5px;
    color: rgba(255,255,255,0.8); font-size: 14px;
    text-shadow: 0 1px 2px rgba(0,0,0,0.5);
    pointer-events: none; line-height: 1;
    opacity: 0; transition: opacity 0.15s;
}
.ec-photo-item:hover .ec-photo-drag-hint { opacity: 1; }

/* ── Responsive ──────────────────────────────────────────────────────────── */
@media (max-width: 820px) {
    .ec-wrap { flex-direction: column; }
    .ec-nav {
        width: 100%;
        flex-direction: row;
        flex-wrap: nowrap;
        overflow-x: auto;
        padding: 4px 0;
        gap: 2px;
        position: static;
    }
    .ec-nav-item {
        padding: 10px 12px;
        border-radius: 8px;
        border-left: none;
        border-bottom: 3px solid transparent;
        flex-shrink: 0;
    }
    .ec-nav-item--active {
        background: color-mix(in srgb, var(--db-accent) 10%, transparent);
        border-bottom-color: var(--db-accent);
        border-left: none;
    }
    .ec-nav-item__hint { display: none; }
    .ec-panel { padding-left: 0; padding-top: 16px; }
    .ec-custom-hex { grid-template-columns: 1fr; }
    .ec-cta-grid { grid-template-columns: 1fr; }
    .ec-link-inputs { flex-direction: column; }
    .ec-sec-head-card { flex-direction: column; align-items: flex-start; }
    .ec-sec-head__right { width: 100%; justify-content: space-between; }
}
</style>
