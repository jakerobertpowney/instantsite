<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { components as dashboardComponents } from '@/routes/dashboard';
import { suggestLabelForUrl, getFaviconUrl, detectPlatformBrand } from '@/lib/platformBrands';
import { toast } from 'vue-sonner';
import { Button } from '@/components/ui/button';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Input } from '@/components/ui/input';
import { CheckCircle2, Eye, EyeOff, ExternalLink, Loader2, MessageCircle, Palette, Plus, Trash2, Upload, XCircle } from 'lucide-vue-next';
import HeaderBackgroundPicker from '@/components/dashboard/HeaderBackgroundPicker.vue';
import { COLOUR_THEMES, buildPaletteFromPrimary, getRecommendedThemeId } from '@/lib/palette';

type ComponentKey = 'header' | 'description' | 'gallery' | 'quick_actions' | 'reviews' | 'contact' | 'contact_form';
type ComponentState = Record<ComponentKey, { enabled: boolean }>;
type Socials = { instagram: string; facebook: string; x: string; linkedin: string };
type QuickLink = { label: string; link: string };
type SiteData = Record<string, any>;

const componentKeys: ComponentKey[] = ['header', 'description', 'gallery', 'quick_actions', 'reviews', 'contact', 'contact_form'];
const page = usePage();
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
        form.logo = null;
        logoPreview.value = siteOverrides.value.logo_path ?? null;
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

// Photo Gallery
const photoCount = computed(() => siteData.value.photos?.length ?? 0);

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
const currentSocials = computed(() => siteData.value.socials ?? {});
</script>

<template>
    <div class="flex flex-col gap-6">
        <div>
            <h1 class="text-2xl font-bold">Edit My Site</h1>
            <p class="mt-1 text-base text-muted-foreground">Turn sections on or off, and customise your content.</p>
        </div>

        <Tabs default-value="design">
            <TabsList class="flex-wrap h-auto gap-1">
                <TabsTrigger value="design" class="gap-2 py-2 px-3 text-sm">
                    <Palette class="h-4 w-4" />
                    Design
                </TabsTrigger>
                <TabsTrigger value="header" class="gap-2 py-2 px-3 text-sm">
                    <CheckCircle2
                        v-if="form.components.header.enabled"
                        class="h-4 w-4 text-emerald-600 dark:text-emerald-400"
                    />
                    <XCircle
                        v-else
                        class="h-4 w-4 text-rose-600 dark:text-rose-400"
                    />
                    Header
                </TabsTrigger>
                <TabsTrigger value="description" class="gap-2 py-2 px-3 text-sm">
                    <CheckCircle2
                        v-if="form.components.description.enabled"
                        class="h-4 w-4 text-emerald-600 dark:text-emerald-400"
                    />
                    <XCircle
                        v-else
                        class="h-4 w-4 text-rose-600 dark:text-rose-400"
                    />
                    About
                </TabsTrigger>
                <TabsTrigger value="gallery" class="gap-2 py-2 px-3 text-sm">
                    <CheckCircle2
                        v-if="form.components.gallery.enabled"
                        class="h-4 w-4 text-emerald-600 dark:text-emerald-400"
                    />
                    <XCircle
                        v-else
                        class="h-4 w-4 text-rose-600 dark:text-rose-400"
                    />
                    Photos
                </TabsTrigger>
                <TabsTrigger value="quick_actions" class="gap-2 py-2 px-3 text-sm">
                    <CheckCircle2
                        v-if="form.components.quick_actions.enabled"
                        class="h-4 w-4 text-emerald-600 dark:text-emerald-400"
                    />
                    <XCircle
                        v-else
                        class="h-4 w-4 text-rose-600 dark:text-rose-400"
                    />
                    Buttons
                </TabsTrigger>
                <TabsTrigger value="reviews" class="gap-2 py-2 px-3 text-sm">
                    <CheckCircle2
                        v-if="form.components.reviews.enabled"
                        class="h-4 w-4 text-emerald-600 dark:text-emerald-400"
                    />
                    <XCircle
                        v-else
                        class="h-4 w-4 text-rose-600 dark:text-rose-400"
                    />
                    Reviews
                </TabsTrigger>
                <TabsTrigger value="contact" class="gap-2 py-2 px-3 text-sm">
                    <CheckCircle2
                        v-if="form.components.contact.enabled"
                        class="h-4 w-4 text-emerald-600 dark:text-emerald-400"
                    />
                    <XCircle
                        v-else
                        class="h-4 w-4 text-rose-600 dark:text-rose-400"
                    />
                    Contact
                </TabsTrigger>
            </TabsList>

            <!-- ── 0. Design / Colours ───────────────────────────────────── -->
            <TabsContent value="design">
                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-lg">Brand colours</CardTitle>
                        <p class="text-sm text-muted-foreground mt-0.5">Choose a colour theme that matches your business. It'll be used for buttons, accents, and highlights across your site.</p>
                    </CardHeader>
                    <CardContent class="flex flex-col gap-5">

                        <!-- Recommended badge -->
                        <div class="flex items-center gap-2 text-sm text-muted-foreground">
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 dark:bg-amber-950 px-3 py-1 text-xs font-medium text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                                ✦ Recommended for your business type
                            </span>
                            <span class="font-medium text-foreground">{{ COLOUR_THEMES.find(t => t.id === recommendedThemeId)?.name }}</span>
                        </div>

                        <!-- Colour theme grid -->
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                            <button
                                v-for="theme in COLOUR_THEMES"
                                :key="theme.id"
                                type="button"
                                class="group relative rounded-xl border-2 p-3 text-left transition-all focus:outline-none focus-visible:ring-2"
                                :class="selectedThemeId === theme.id
                                    ? 'border-foreground ring-1 ring-foreground'
                                    : 'border-transparent bg-muted/40 hover:bg-muted hover:border-muted-foreground/30'"
                                @click="selectTheme(theme.id)"
                            >
                                <!-- Colour swatch strip -->
                                <div class="flex gap-1 mb-2">
                                    <div class="h-5 w-5 rounded-full shrink-0" :style="{ backgroundColor: theme.palette.primary }" />
                                    <div class="h-5 w-5 rounded-full shrink-0" :style="{ backgroundColor: theme.palette.secondary }" />
                                    <div class="h-5 w-5 rounded-full shrink-0" :style="{ backgroundColor: theme.palette.primaryMuted }" />
                                </div>
                                <p class="text-xs font-semibold text-foreground">{{ theme.name }}</p>
                                <!-- Recommended star -->
                                <span
                                    v-if="theme.id === recommendedThemeId"
                                    class="absolute top-2 right-2 text-amber-500 text-xs"
                                    title="Recommended for your business type"
                                >✦</span>
                                <!-- Selected check -->
                                <div v-if="selectedThemeId === theme.id" class="absolute bottom-2 right-2">
                                    <CheckCircle2 class="h-4 w-4 text-foreground" />
                                </div>
                            </button>

                            <!-- Custom option -->
                            <button
                                type="button"
                                class="group relative rounded-xl border-2 p-3 text-left transition-all focus:outline-none focus-visible:ring-2"
                                :class="selectedThemeId === 'custom'
                                    ? 'border-foreground ring-1 ring-foreground'
                                    : 'border-transparent bg-muted/40 hover:bg-muted hover:border-muted-foreground/30'"
                                @click="selectTheme('custom')"
                            >
                                <div class="flex gap-1 mb-2">
                                    <div class="h-5 w-5 rounded-full shrink-0 border border-dashed border-muted-foreground/50" />
                                    <div class="h-5 w-5 rounded-full shrink-0 border border-dashed border-muted-foreground/50" />
                                </div>
                                <p class="text-xs font-semibold text-foreground">Custom</p>
                                <div v-if="selectedThemeId === 'custom'" class="absolute bottom-2 right-2">
                                    <CheckCircle2 class="h-4 w-4 text-foreground" />
                                </div>
                            </button>
                        </div>

                        <!-- Custom hex inputs (shown when Custom is selected) -->
                        <div v-if="selectedThemeId === 'custom'" class="flex flex-col gap-3 rounded-lg border p-4 bg-muted/30">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="flex flex-col gap-1.5">
                                    <Label for="palette-primary" class="text-sm font-medium">Primary colour</Label>
                                    <div class="flex items-center gap-2">
                                        <div class="h-9 w-9 rounded-md border shrink-0" :style="{ backgroundColor: customPrimaryInput || '#1e293b' }" />
                                        <Input
                                            id="palette-primary"
                                            v-model="customPrimaryInput"
                                            placeholder="#1e40af"
                                            class="h-9 font-mono text-sm flex-1"
                                            maxlength="7"
                                        />
                                    </div>
                                    <p class="text-xs text-muted-foreground">Buttons, accents, and highlights</p>
                                </div>
                                <div class="flex flex-col gap-1.5">
                                    <Label for="palette-secondary" class="text-sm font-medium">Secondary colour <span class="text-muted-foreground">(optional)</span></Label>
                                    <div class="flex items-center gap-2">
                                        <div class="h-9 w-9 rounded-md border shrink-0" :style="{ backgroundColor: customSecondaryInput || previewPalette.secondary }" />
                                        <Input
                                            id="palette-secondary"
                                            v-model="customSecondaryInput"
                                            :placeholder="previewPalette.secondary"
                                            class="h-9 font-mono text-sm flex-1"
                                            maxlength="7"
                                        />
                                    </div>
                                    <p class="text-xs text-muted-foreground">Leave blank to auto-generate from primary</p>
                                </div>
                            </div>
                        </div>

                        <!-- Live palette preview strip -->
                        <div class="flex flex-col gap-2">
                            <p class="text-sm font-medium">Preview</p>
                            <div class="flex gap-3 items-center rounded-xl border p-4">
                                <div class="flex gap-2">
                                    <div class="h-8 w-8 rounded-full" :style="{ backgroundColor: previewPalette.primary }" :title="previewPalette.primary" />
                                    <div class="h-8 w-8 rounded-full" :style="{ backgroundColor: previewPalette.secondary }" :title="previewPalette.secondary" />
                                    <div class="h-8 w-8 rounded-lg border" :style="{ backgroundColor: previewPalette.primaryMuted }" :title="previewPalette.primaryMuted" />
                                </div>
                                <div class="flex gap-2 ml-2">
                                    <span
                                        class="inline-flex items-center rounded-full px-3 py-1 text-sm font-semibold"
                                        :style="{ backgroundColor: previewPalette.primary, color: previewPalette.primaryFg }"
                                    >
                                        Call us
                                    </span>
                                    <span
                                        class="inline-flex items-center rounded-full border-2 px-3 py-1 text-sm font-semibold"
                                        :style="{ borderColor: previewPalette.primary, color: previewPalette.primary }"
                                    >
                                        Message
                                    </span>
                                </div>
                            </div>
                        </div>

                        <p v-if="form.errors.palette_primary" class="text-sm text-destructive">{{ form.errors.palette_primary }}</p>
                    </CardContent>
                </Card>
            </TabsContent>

            <!-- ── 1. Header ──────────────────────────────────────────────── -->
            <TabsContent value="header">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-3">
                        <div>
                            <CardTitle class="text-lg">Header section</CardTitle>
                            <p class="text-sm text-muted-foreground mt-0.5">Your business name, type, and location shown at the top of your site.</p>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <span class="text-sm text-muted-foreground">{{ form.components['header'].enabled ? 'Visible' : 'Hidden' }}</span>
                            <Switch
                                id="toggle-header"
                                v-model="form.components['header'].enabled"
                            />
                        </div>
                    </CardHeader>
                    <CardContent class="flex flex-col gap-4">
                        <p class="flex items-center gap-1.5 text-sm text-muted-foreground">
                            <ExternalLink class="h-3.5 w-3.5 shrink-0" />
                            This information is pulled from your Google Business Profile
                        </p>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div class="rounded-lg bg-muted/50 px-4 py-3 flex flex-col gap-1">
                                <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wide">Business name</p>
                                <p class="text-sm font-medium">{{ businessName || '—' }}</p>
                            </div>
                            <div class="rounded-lg bg-muted/50 px-4 py-3 flex flex-col gap-1">
                                <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wide">Type of business</p>
                                <p class="text-sm font-medium">{{ businessType || '—' }}</p>
                            </div>
                            <div class="rounded-lg bg-muted/50 px-4 py-3 flex flex-col gap-1">
                                <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wide">Location</p>
                                <p class="text-sm font-medium">{{ businessLocation || '—' }}</p>
                            </div>
                        </div>
                        <Separator />
                        <div class="flex flex-col gap-3">
                            <Label class="text-base font-semibold">Your logo</Label>
                            <div class="flex items-center gap-4">
                                <img
                                    v-if="logoPreview"
                                    :src="logoPreview"
                                    alt="Logo preview"
                                    class="h-20 w-20 rounded-lg border object-contain bg-white"
                                />
                                <div
                                    v-else
                                    class="flex h-20 w-20 items-center justify-center rounded-lg border bg-muted text-muted-foreground text-xs text-center px-2"
                                >
                                    No logo yet
                                </div>
                                <div class="flex flex-col gap-2">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="lg"
                                        class="text-base h-11"
                                        @click="triggerLogoUpload"
                                    >
                                        <Upload class="mr-2 h-5 w-5" />
                                        {{ logoPreview ? 'Change logo' : 'Upload a logo' }}
                                    </Button>
                                    <p class="text-sm text-muted-foreground">JPG, PNG or SVG. Max 2MB.</p>
                                </div>
                                <input
                                    ref="logoInput"
                                    type="file"
                                    accept="image/*"
                                    class="hidden"
                                    @change="onLogoChange"
                                />
                            </div>
                            <p v-if="form.errors.logo" class="text-sm text-destructive">
                                {{ form.errors.logo }}
                            </p>
                        </div>
                        <Separator />
                        <HeaderBackgroundPicker
                            :bg-type="(form.header_bg_type as any)"
                            :bg-value="form.header_bg_value"
                            :bg-thumb="form.header_bg_thumb"
                            :bg-credit="form.header_bg_credit"
                            :bg-credit-url="form.header_bg_credit_url"
                            :google-images="(siteData.images ?? [])"
                            :business-name="businessName"
                            :business-type="businessType"
                            @update:bg-type="(v) => form.header_bg_type = v"
                            @update:bg-value="(v) => form.header_bg_value = v"
                            @update:bg-thumb="(v) => form.header_bg_thumb = v"
                            @update:bg-credit="(v) => form.header_bg_credit = v"
                            @update:bg-credit-url="(v) => form.header_bg_credit_url = v"
                            @update:image-file="(v) => form.header_bg_image = v"
                        />
                    </CardContent>
                </Card>
            </TabsContent>

            <!-- ── 2. About / Description ─────────────────────────────────── -->
            <TabsContent value="description">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-3">
                        <div>
                            <CardTitle class="text-lg">About section</CardTitle>
                            <p class="text-sm text-muted-foreground mt-0.5">A short description of your business shown near the top of your site.</p>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <span class="text-sm text-muted-foreground">{{ form.components['description'].enabled ? 'Visible' : 'Hidden' }}</span>
                            <Switch
                                id="toggle-description"
                                v-model="form.components['description'].enabled"
                            />
                        </div>
                    </CardHeader>
                    <CardContent class="flex flex-col gap-4">
                        <p class="flex items-center gap-1.5 text-sm text-muted-foreground">
                            <ExternalLink class="h-3.5 w-3.5 shrink-0" />
                            This information is pulled from your Google Business Profile
                        </p>
                        <div class="rounded-lg bg-muted/50 px-4 py-3 flex flex-col gap-1">
                            <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wide">Google description</p>
                            <p v-if="googleDescription" class="text-sm">{{ googleDescription }}</p>
                            <p v-else class="text-sm italic text-muted-foreground">No description found on your Google Business Profile.</p>
                        </div>
                        <Separator />
                        <div class="flex flex-col gap-2">
                            <Label for="override-description" class="text-base font-semibold">Write your own description</Label>
                            <Textarea
                                id="override-description"
                                v-model="form.overrides.description"
                                :placeholder="googleDescription || 'Tell customers what you do, where you work, and what makes you stand out...'"
                                rows="5"
                                class="text-base resize-none"
                            />
                            <p class="text-sm text-muted-foreground">
                                If you leave this empty, we'll use the description from your Google Business Profile instead.
                            </p>
                            <p v-if="form.errors['overrides.description']" class="text-sm text-destructive">
                                {{ form.errors['overrides.description'] }}
                            </p>
                        </div>
                    </CardContent>
                </Card>
            </TabsContent>

            <!-- ── 3. Photo Gallery ───────────────────────────────────────── -->
            <TabsContent value="gallery">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-3">
                        <div>
                            <CardTitle class="text-lg">Photos section</CardTitle>
                            <p class="text-sm text-muted-foreground mt-0.5">A gallery of photos from your Google Business Profile.</p>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <span class="text-sm text-muted-foreground">{{ form.components['gallery'].enabled ? 'Visible' : 'Hidden' }}</span>
                            <Switch
                                id="toggle-gallery"
                                v-model="form.components['gallery'].enabled"
                            />
                        </div>
                    </CardHeader>
                    <CardContent class="flex flex-col gap-4">
                        <p class="flex items-center gap-1.5 text-sm text-muted-foreground">
                            <ExternalLink class="h-3.5 w-3.5 shrink-0" />
                            Photos are managed through your Google Business Profile
                        </p>
                        <div class="rounded-lg bg-muted/50 px-4 py-3">
                            <p v-if="photoCount > 0" class="text-sm font-medium">
                                {{ photoCount }} photo{{ photoCount === 1 ? '' : 's' }} imported from Google
                            </p>
                            <p v-else class="text-sm text-muted-foreground">No photos found on your Google Business Profile.</p>
                        </div>
                        <p class="text-sm text-muted-foreground">
                            To add or change photos, update them on your Google Business Profile and they'll appear here automatically.
                        </p>
                    </CardContent>
                </Card>
            </TabsContent>

            <!-- ── 4. Quick Actions (Buttons) ─────────────────────────────── -->
            <TabsContent value="quick_actions">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-3">
                        <div>
                            <CardTitle class="text-lg">Call-to-action buttons</CardTitle>
                            <p class="text-sm text-muted-foreground mt-0.5">Buttons that let visitors call you, get directions, or visit a link.</p>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <span class="text-sm text-muted-foreground">{{ form.components['quick_actions'].enabled ? 'Visible' : 'Hidden' }}</span>
                            <Switch
                                id="toggle-quick_actions"
                                v-model="form.components['quick_actions'].enabled"
                            />
                        </div>
                    </CardHeader>
                    <CardContent class="flex flex-col gap-4">
                        <!-- Phone number — read-only from Google -->
                        <p class="flex items-center gap-1.5 text-sm text-muted-foreground">
                            <ExternalLink class="h-3.5 w-3.5 shrink-0" />
                            Phone number is pulled from your Google Business Profile
                        </p>
                        <div class="rounded-lg bg-muted/50 px-4 py-3 flex flex-col gap-1">
                            <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wide">Phone number</p>
                            <p class="text-sm font-medium">{{ phoneNumber || 'No phone number found on Google' }}</p>
                        </div>

                        <Separator />

                        <!-- WhatsApp -->
                        <div class="flex flex-col gap-2">
                            <Label for="whatsapp-number" class="text-base font-semibold flex items-center gap-2">
                                <MessageCircle class="h-4 w-4 text-[#25D366]" />
                                WhatsApp number
                            </Label>
                            <Input
                                id="whatsapp-number"
                                v-model="form.whatsapp_number"
                                placeholder="447911123456"
                                class="h-11 text-base max-w-xs"
                            />
                            <p class="text-sm text-muted-foreground">
                                Adds a WhatsApp button so customers can message you instantly. Include your country code without the + sign — UK numbers start with 44, e.g. <span class="font-mono text-xs bg-muted px-1 py-0.5 rounded">447911123456</span>.
                            </p>
                            <p v-if="form.errors.whatsapp_number" class="text-sm text-destructive">{{ form.errors.whatsapp_number }}</p>
                        </div>

                        <Separator />

                        <!-- Custom quick links editor -->
                        <div class="flex flex-col gap-3">
                            <div>
                                <Label class="text-base font-semibold">Custom buttons</Label>
                                <p class="text-sm text-muted-foreground mt-1">
                                    Add buttons that link to booking pages, menus, price lists, or anywhere else.
                                </p>
                            </div>

                            <div v-if="form.quickLinks.length > 0" class="flex flex-col gap-3">
                                <div
                                    v-for="(link, index) in form.quickLinks"
                                    :key="index"
                                    class="flex flex-col gap-3 rounded-lg border p-3"
                                >
                                    <div class="flex flex-col sm:flex-row gap-2">
                                        <div class="flex flex-col gap-1.5 flex-1">
                                            <Label :for="`ql-label-${index}`" class="text-sm font-medium">Button label</Label>
                                            <Input
                                                :id="`ql-label-${index}`"
                                                v-model="link.label"
                                                placeholder="e.g. Book a quote"
                                                class="h-10 text-base"
                                            />
                                        </div>
                                        <div class="flex flex-col gap-1.5 flex-1">
                                            <Label :for="`ql-link-${index}`" class="text-sm font-medium">Link (web address)</Label>
                                            <Input
                                                :id="`ql-link-${index}`"
                                                v-model="link.link"
                                                placeholder="https://..."
                                                type="url"
                                                class="h-10 text-base"
                                                @blur="onLinkBlur(index)"
                                            />
                                        </div>
                                        <div class="flex items-end">
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon"
                                                class="text-muted-foreground hover:text-destructive h-10 w-10 shrink-0 mt-auto"
                                                @click="removeQuickLink(index)"
                                                :aria-label="`Remove button ${index + 1}`"
                                            >
                                                <Trash2 class="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </div>

                                    <!-- Live button preview -->
                                    <div v-if="link.label || link.link" class="flex items-center gap-2">
                                        <span class="text-xs text-muted-foreground shrink-0">Preview:</span>
                                        <span
                                            :style="linkBrandStyle(link.link)"
                                            class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-medium"
                                            :class="!detectPlatformBrand(link.link) ? 'bg-black text-white' : ''"
                                        >
                                            {{ link.label || 'Button label' }}
                                            <img
                                                v-if="linkFavicon(link.link)"
                                                :src="linkFavicon(link.link)!"
                                                class="h-3.5 w-3.5 rounded-sm object-contain"
                                                aria-hidden="true"
                                            />
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <p v-else class="text-sm text-muted-foreground italic">
                                No custom buttons yet. Click below to add one.
                            </p>

                            <Button
                                type="button"
                                variant="outline"
                                size="lg"
                                class="w-full sm:w-auto text-base h-11 gap-2"
                                @click="addQuickLink"
                            >
                                <Plus class="h-5 w-5" />
                                Add a button
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </TabsContent>

            <!-- ── 5. Reviews ─────────────────────────────────────────────── -->
            <TabsContent value="reviews">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-3">
                        <div>
                            <CardTitle class="text-lg">Reviews section</CardTitle>
                            <p class="text-sm text-muted-foreground mt-0.5">Your Google star rating and customer reviews.</p>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <span class="text-sm text-muted-foreground">{{ form.components['reviews'].enabled ? 'Visible' : 'Hidden' }}</span>
                            <Switch
                                id="toggle-reviews"
                                v-model="form.components['reviews'].enabled"
                            />
                        </div>
                    </CardHeader>
                    <CardContent class="flex flex-col gap-4">
                        <p class="flex items-center gap-1.5 text-sm text-muted-foreground">
                            <ExternalLink class="h-3.5 w-3.5 shrink-0" />
                            Reviews are pulled from your Google Business Profile
                        </p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div class="rounded-lg bg-muted/50 px-4 py-3 flex flex-col gap-1">
                                <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wide">Star rating</p>
                                <p class="text-sm font-medium">{{ rating !== null ? `${rating} ★` : 'No rating yet' }}</p>
                            </div>
                            <div class="rounded-lg bg-muted/50 px-4 py-3 flex flex-col gap-1">
                                <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wide">Total reviews</p>
                                <p class="text-sm font-medium">
                                    {{ reviewCount !== null ? `${reviewCount} review${reviewCount === 1 ? '' : 's'}` : 'No reviews yet' }}
                                </p>
                            </div>
                        </div>

                        <!-- Per-review visibility toggles -->
                        <div v-if="siteReviews.length > 0" class="flex flex-col gap-2">
                            <Separator />
                            <div>
                                <p class="text-base font-semibold">Individual reviews</p>
                                <p class="text-sm text-muted-foreground mt-0.5">
                                    Hide any reviews you don't want to show on your site. Hidden reviews are not deleted from Google — only from your site.
                                </p>
                            </div>
                            <div class="flex flex-col gap-2">
                                <div
                                    v-for="(review, i) in siteReviews"
                                    :key="i"
                                    class="flex items-start gap-3 rounded-lg border p-3 transition-colors"
                                    :class="form.overrides.hidden_reviews.includes(i) ? 'bg-muted/60 opacity-60' : 'bg-background'"
                                >
                                    <!-- Stars + author -->
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-1.5 mb-1">
                                            <span class="text-sm font-semibold truncate">{{ review.author }}</span>
                                            <span class="text-xs text-amber-500 shrink-0">{{ '★'.repeat(review.rating) }}{{ '☆'.repeat(5 - review.rating) }}</span>
                                        </div>
                                        <p class="text-xs text-muted-foreground line-clamp-2">{{ review.text || 'No text' }}</p>
                                    </div>
                                    <!-- Toggle button -->
                                    <button
                                        type="button"
                                        class="shrink-0 flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-xs font-medium transition-colors"
                                        :class="form.overrides.hidden_reviews.includes(i)
                                            ? 'border-muted-foreground/30 text-muted-foreground hover:bg-muted'
                                            : 'border-transparent bg-muted/50 text-foreground hover:bg-muted'"
                                        @click="toggleReviewHidden(i)"
                                        :aria-label="form.overrides.hidden_reviews.includes(i) ? `Show review from ${review.author}` : `Hide review from ${review.author}`"
                                    >
                                        <EyeOff v-if="form.overrides.hidden_reviews.includes(i)" class="h-3.5 w-3.5" />
                                        <Eye v-else class="h-3.5 w-3.5" />
                                        {{ form.overrides.hidden_reviews.includes(i) ? 'Hidden' : 'Visible' }}
                                    </button>
                                </div>
                            </div>
                        </div>
                        <p v-else class="text-sm text-muted-foreground italic">No reviews imported yet.</p>
                    </CardContent>
                </Card>
            </TabsContent>

            <!-- ── 6. Contact Info ────────────────────────────────────────── -->
            <TabsContent value="contact">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-3">
                        <div>
                            <CardTitle class="text-lg">Contact section</CardTitle>
                            <p class="text-sm text-muted-foreground mt-0.5">Your address, phone number, opening hours, and social media links.</p>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <span class="text-sm text-muted-foreground">{{ form.components['contact'].enabled ? 'Visible' : 'Hidden' }}</span>
                            <Switch
                                id="toggle-contact"
                                v-model="form.components['contact'].enabled"
                            />
                        </div>
                    </CardHeader>
                    <CardContent class="flex flex-col gap-4">
                        <p class="flex items-center gap-1.5 text-sm text-muted-foreground">
                            <ExternalLink class="h-3.5 w-3.5 shrink-0" />
                            Contact details are pulled from your Google Business Profile
                        </p>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div class="rounded-lg bg-muted/50 px-4 py-3 flex flex-col gap-1">
                                <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wide">Address</p>
                                <p class="text-sm font-medium">{{ formattedAddress || 'Not available' }}</p>
                            </div>
                            <div class="rounded-lg bg-muted/50 px-4 py-3 flex flex-col gap-1">
                                <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wide">Phone</p>
                                <p class="text-sm font-medium">{{ phoneNumber || 'Not available' }}</p>
                            </div>
                            <div class="rounded-lg bg-muted/50 px-4 py-3 flex flex-col gap-1">
                                <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wide">Opening hours</p>
                                <p class="text-sm font-medium">
                                    {{ openingHoursPeriods !== null ? `${openingHoursPeriods} day${openingHoursPeriods === 1 ? '' : 's'} configured` : 'Not available' }}
                                </p>
                            </div>
                        </div>

                        <!-- Current social links preview -->
                        <div v-if="currentSocials.instagram || currentSocials.facebook || currentSocials.x || currentSocials.linkedin" class="rounded-lg bg-muted/50 px-4 py-3 flex flex-col gap-1">
                            <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wide">Your social links</p>
                            <p v-if="currentSocials.instagram" class="text-sm">Instagram: {{ currentSocials.instagram }}</p>
                            <p v-if="currentSocials.facebook" class="text-sm">Facebook: {{ currentSocials.facebook }}</p>
                            <p v-if="currentSocials.x" class="text-sm">X (Twitter): {{ currentSocials.x }}</p>
                            <p v-if="currentSocials.linkedin" class="text-sm">LinkedIn: {{ currentSocials.linkedin }}</p>
                        </div>

                        <Separator />

                        <!-- Contact email -->
                        <div class="flex flex-col gap-2">
                            <Label for="contact-email" class="text-base font-semibold">Contact email</Label>
                            <div class="rounded-lg bg-muted/50 px-4 py-3 flex flex-col gap-1">
                                <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wide">Email from Google</p>
                                <p v-if="googleEmail" class="text-sm font-medium">{{ googleEmail }}</p>
                                <p v-else class="text-sm italic text-muted-foreground">No email found on your Google Business Profile.</p>
                            </div>
                            <Input
                                id="contact-email"
                                v-model="form.overrides.contact_email"
                                type="email"
                                placeholder="hello@yourbusiness.com"
                                class="h-11 text-base"
                            />
                            <p class="text-sm text-muted-foreground">
                                Used for the contact form and Message button. Leave blank to use the email from your Google Business Profile.
                            </p>
                            <p v-if="form.errors['overrides.contact_email']" class="text-sm text-destructive">
                                {{ form.errors['overrides.contact_email'] }}
                            </p>
                        </div>

                        <Separator />

                        <!-- Contact form toggle -->
                        <div class="flex items-center justify-between rounded-lg border bg-muted/30 px-4 py-3">
                            <div class="flex flex-col gap-0.5">
                                <p class="text-sm font-semibold">Contact form</p>
                                <p class="text-xs text-muted-foreground">Show a "Send us a message" form on your site so visitors can email you directly.</p>
                            </div>
                            <div class="flex items-center gap-2 shrink-0 ml-4">
                                <span class="text-sm text-muted-foreground">{{ form.components['contact_form'].enabled ? 'Visible' : 'Hidden' }}</span>
                                <Switch
                                    id="toggle-contact_form"
                                    v-model="form.components['contact_form'].enabled"
                                />
                            </div>
                        </div>

                        <Separator />

                        <!-- Social links editor -->
                        <div class="flex flex-col gap-4">
                            <div>
                                <Label class="text-base font-semibold">Social media links</Label>
                                <p class="text-sm text-muted-foreground mt-1">Add links to your social media pages so customers can follow you.</p>
                            </div>
                            <div class="flex flex-col gap-4">
                                <div class="flex flex-col gap-1.5">
                                    <Label for="social-instagram" class="text-sm font-medium">Instagram page link</Label>
                                    <Input
                                        id="social-instagram"
                                        v-model="form.socials.instagram"
                                        placeholder="https://www.instagram.com/yourbusiness"
                                        type="url"
                                        class="h-11 text-base"
                                    />
                                    <p v-if="form.errors['socials.instagram']" class="text-sm text-destructive">{{ form.errors['socials.instagram'] }}</p>
                                </div>
                                <div class="flex flex-col gap-1.5">
                                    <Label for="social-facebook" class="text-sm font-medium">Facebook page link</Label>
                                    <Input
                                        id="social-facebook"
                                        v-model="form.socials.facebook"
                                        placeholder="https://www.facebook.com/yourbusiness"
                                        type="url"
                                        class="h-11 text-base"
                                    />
                                    <p v-if="form.errors['socials.facebook']" class="text-sm text-destructive">{{ form.errors['socials.facebook'] }}</p>
                                </div>
                                <div class="flex flex-col gap-1.5">
                                    <Label for="social-x" class="text-sm font-medium">X (Twitter) page link</Label>
                                    <Input
                                        id="social-x"
                                        v-model="form.socials.x"
                                        placeholder="https://x.com/yourbusiness"
                                        type="url"
                                        class="h-11 text-base"
                                    />
                                    <p v-if="form.errors['socials.x']" class="text-sm text-destructive">{{ form.errors['socials.x'] }}</p>
                                </div>
                                <div class="flex flex-col gap-1.5">
                                    <Label for="social-linkedin" class="text-sm font-medium">LinkedIn page link</Label>
                                    <Input
                                        id="social-linkedin"
                                        v-model="form.socials.linkedin"
                                        placeholder="https://www.linkedin.com/company/yourbusiness"
                                        type="url"
                                        class="h-11 text-base"
                                    />
                                    <p v-if="form.errors['socials.linkedin']" class="text-sm text-destructive">{{ form.errors['socials.linkedin'] }}</p>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </TabsContent>
        </Tabs>

        <!-- Save button -->
        <div>
            <Button
                class="w-full sm:w-auto text-base font-semibold px-8 h-12"
                size="lg"
                :disabled="saving"
                @click.prevent="saveForm"
            >
                <Loader2 v-show="saving" class="mr-2 h-5 w-5 animate-spin" />
                Save changes
            </Button>
        </div>
    </div>
</template>
