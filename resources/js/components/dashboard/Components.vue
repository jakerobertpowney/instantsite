<script setup lang="ts">
import { useForm, usePage, router } from '@inertiajs/vue3';
import { computed, inject, ref, watch } from 'vue';
import { components as dashboardComponents } from '@/routes/dashboard';
import { suggestLabelForUrl, getFaviconUrl, detectPlatformBrand } from '@/lib/platformBrands';
import { toast } from 'vue-sonner';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { Input } from '@/components/ui/input';
import { CheckCircle2, Image, Loader2, Lock, MapPin, MessageCircle, Palette, Pencil, Phone, Plus, Sparkles, Star, Trash2, Upload, Wand2, X } from 'lucide-vue-next';
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
const site = computed(() => (page.props.site as SiteData | undefined) ?? {});

// Derive component flags from site columns directly
const siteData = computed(() => site.value);
const siteComponents = computed(() => site.value.components ?? {});
// settings holds misc settings (palette, header_bg, hidden_reviews, etc.)
const siteSettings = computed(() => site.value.settings ?? {});
// backwards-compat alias so existing code referencing siteOverrides still works
const siteOverrides = computed(() => siteSettings.value);

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
const logoPreview = ref<string | null>(site.value.logo_path ?? null);

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

const siteSocials = computed(() => site.value.socials ?? {});

function buildInitialSocials(): Socials {
    return {
        instagram: siteSocials.value.instagram ?? '',
        facebook: siteSocials.value.facebook ?? '',
        x: siteSocials.value.x ?? '',
        linkedin: siteSocials.value.linkedin ?? '',
    };
}

const siteQuickLinks = computed<QuickLink[]>(() => site.value.quick_links ?? []);

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
const existingPalette = computed(() => siteSettings.value.palette ?? {});
const recommendedThemeId = computed(() => getRecommendedThemeId(site.value.business_type));
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

const existingBg = computed(() => siteSettings.value.header_bg ?? { type: 'auto', value: '' });

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
const siteServices = computed<Service[]>(() => site.value.services ?? []);

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

type OpeningHourRow = { day: string; open: string; close: string; closed: boolean };
type ReviewEntry = { id: string; author: string; text: string; rating: number; date: string };

function buildInitialOpeningHours(): OpeningHourRow[] {
    const raw = site.value.opening_hours as OpeningHourRow[] | undefined;
    if (raw && raw.length > 0) {
        return raw.map(h => ({
            day:    h.day    ?? '',
            open:   h.open   ?? '09:00',
            close:  h.close  ?? '17:00',
            closed: h.closed === true || (h.closed as any) === 1 || (h.closed as any) === '1',
        }));
    }
    const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
    return days.map(day => ({ day, open: '09:00', close: '17:00', closed: day === 'Saturday' || day === 'Sunday' }));
}

function makeIsOpen(index: number) {
    return computed({
        get: () => !form.opening_hours[index].closed,
        set: (v: boolean) => { form.opening_hours[index].closed = !v; },
    });
}

function buildInitialReviews(): ReviewEntry[] {
    const raw = site.value.reviews as Array<Record<string, any>> | undefined;
    if (!raw) return [];
    return raw.map(r => ({
        id:     r.id     ?? Math.random().toString(36).slice(2),
        author: r.author ?? '',
        text:   r.text   ?? '',
        rating: r.rating ?? 5,
        date:   r.date   ?? '',
    }));
}

function addReview() {
    form.reviews.push({ id: Math.random().toString(36).slice(2), author: '', text: '', rating: 5, date: '' });
}

function removeReview(index: number) {
    form.reviews.splice(index, 1);
}

// Photo upload state
const photoInput = ref<HTMLInputElement | null>(null);
const photoNewPreviews = ref<{ file: File; url: string }[]>([]);

function triggerPhotoUpload() {
    photoInput.value?.click();
}

function onPhotoFilesChange(event: Event) {
    const target = event.target as HTMLInputElement;
    if (!target.files) return;
    for (const file of Array.from(target.files)) {
        form.photos.push(file);
        photoNewPreviews.value.push({ file, url: URL.createObjectURL(file) });
    }
    target.value = '';
}

function removeNewPhoto(index: number) {
    URL.revokeObjectURL(photoNewPreviews.value[index].url);
    photoNewPreviews.value.splice(index, 1);
    form.photos.splice(index, 1);
}

function removeExistingPhoto(path: string) {
    form.remove_photos.push(path);
    form.images_order = form.images_order.filter(p => p !== path);
}

const form = useForm<{
    components: ComponentState;
    overrides: { description: string; contact_email: string };
    business_name: string;
    business_type: string;
    city: string;
    region: string;
    phone: string;
    formatted_address: string;
    opening_hours: OpeningHourRow[];
    rating: number | null;
    review_count: number | null;
    reviews: ReviewEntry[];
    socials: Socials;
    quickLinks: QuickLink[];
    whatsapp_number: string;
    logo: File | null;
    photos: File[];
    remove_photos: string[];
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
        description:   site.value.description ?? '',
        contact_email: (site.value.contact_email as string | undefined) ?? '',
    },
    business_name:     site.value.business_name     ?? '',
    business_type:     site.value.business_type     ?? '',
    city:              site.value.city              ?? '',
    region:            site.value.region            ?? '',
    phone:             site.value.phone             ?? '',
    formatted_address: site.value.formatted_address ?? '',
    opening_hours:     buildInitialOpeningHours(),
    rating:            site.value.rating            ?? null,
    review_count:      site.value.review_count      ?? null,
    reviews:           buildInitialReviews(),
    socials: buildInitialSocials(),
    quickLinks: buildInitialQuickLinks(),
    whatsapp_number: site.value.whatsapp_number ?? '',
    logo: null,
    photos: [],
    remove_photos: [],
    palette_primary:   initPalette.primary,
    palette_secondary: initPalette.secondary,
    header_bg_type:      existingBg.value.type       ?? 'auto',
    header_bg_value:     existingBg.value.value      ?? '',
    header_bg_thumb:     existingBg.value.thumb      ?? '',
    header_bg_credit:    existingBg.value.credit     ?? '',
    header_bg_credit_url: existingBg.value.credit_url ?? '',
    header_bg_image: null,
    services:          buildInitialServices(),
    services_heading:  site.value.services_heading  ?? '',
    services_cta_label: site.value.services_cta_label ?? '',
    services_cta_link:  site.value.services_cta_link  ?? '',
    images_order: (site.value.images as string[] | undefined) ?? [],
});

watch(
    () => page.props.site,
    () => {
        form.components = buildInitialComponents();
        form.overrides.description   = site.value.description ?? '';
        form.overrides.contact_email = (site.value.contact_email as string | undefined) ?? '';
        form.business_name     = site.value.business_name     ?? '';
        form.business_type     = site.value.business_type     ?? '';
        form.city              = site.value.city              ?? '';
        form.region            = site.value.region            ?? '';
        form.phone             = site.value.phone             ?? '';
        form.formatted_address = site.value.formatted_address ?? '';
        form.opening_hours     = buildInitialOpeningHours();
        form.rating            = site.value.rating            ?? null;
        form.review_count      = site.value.review_count      ?? null;
        form.reviews           = buildInitialReviews();
        form.socials = buildInitialSocials();
        form.quickLinks = buildInitialQuickLinks();
        form.whatsapp_number = site.value.whatsapp_number ?? '';
        form.services          = buildInitialServices();
        form.services_heading  = site.value.services_heading  ?? '';
        form.services_cta_label = site.value.services_cta_label ?? '';
        form.services_cta_link  = site.value.services_cta_link  ?? '';
        editingServiceIndex.value = null;
        form.logo = null;
        form.photos = [];
        form.remove_photos = [];
        photoNewPreviews.value = [];
        logoPreview.value = site.value.logo_path ?? null;
        form.images_order = (site.value.images as string[] | undefined) ?? [];
        const reinitPalette = initPaletteValues();
        form.palette_primary   = reinitPalette.primary;
        form.palette_secondary = reinitPalette.secondary;
        customPrimaryInput.value   = reinitPalette.primary;
        customSecondaryInput.value = reinitPalette.secondary;
        selectedThemeId.value = existingPalette.value.primary
            ? COLOUR_THEMES.find(t => t.palette.primary === existingPalette.value.primary)?.id ?? 'custom'
            : recommendedThemeId.value;
        const bg = siteSettings.value.header_bg ?? { type: 'auto', value: '' };
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

/** Post only the specified section data — avoids other sections' validation blocking saves. */
function postSection(data: Record<string, any>) {
    saving.value = true;
    router.post(dashboardComponents.url(), data, {
        forceFormData: true,
        onSuccess: () => {
            toast('Saved!');
            saving.value = false;
        },
        onError: (errors) => {
            saving.value = false;
            const firstError = Object.values(errors as Record<string, string>)[0];
            toast.error(firstError ?? 'Could not save — please check your inputs.');
        },
    });
}

function saveDesign() {
    postSection({
        palette_primary:      form.palette_primary,
        palette_secondary:    form.palette_secondary,
        header_bg_type:       form.header_bg_type,
        header_bg_value:      form.header_bg_value,
        header_bg_thumb:      form.header_bg_thumb,
        header_bg_credit:     form.header_bg_credit,
        header_bg_credit_url: form.header_bg_credit_url,
        header_bg_image:      form.header_bg_image,
    });
}

function saveHeader() {
    postSection({
        components:    { header: form.components.header },
        business_name: form.business_name,
        business_type: form.business_type,
        city:          form.city,
        region:        form.region,
        logo:          form.logo,
    });
}

function saveAbout() {
    postSection({
        components: { description: form.components.description },
        overrides:  { description: form.overrides.description },
    });
}

function saveGallery() {
    postSection({
        components:   { gallery: form.components.gallery },
        photos:       form.photos,
        remove_photos: form.remove_photos,
        images_order:  form.images_order,
    });
}

function saveQuickActions() {
    postSection({
        components:      { quick_actions: form.components.quick_actions },
        phone:           form.phone,
        whatsapp_number: form.whatsapp_number,
        quickLinks:      form.quickLinks,
    });
}

function saveReviews() {
    postSection({
        components:   { reviews: form.components.reviews },
        rating:       form.rating,
        review_count: form.review_count,
        reviews:      form.reviews,
    });
}

function saveContact() {
    postSection({
        components:        { contact: form.components.contact },
        formatted_address: form.formatted_address,
        opening_hours:     form.opening_hours,
        socials:           form.socials,
    });
}

function saveServices() {
    flushDraft();
    postSection({
        components:         { services: form.components.services },
        services:           form.services,
        services_heading:   form.services_heading,
        services_cta_label: form.services_cta_label,
        services_cta_link:  form.services_cta_link,
    });
}

// ─── Computed helpers for read-only preview data ──────────────────────────────

// Header
const businessName = computed(() => site.value.business_name ?? '');
const businessType = computed(() => site.value.business_type ?? '');
const businessLocation = computed(() => {
    const city   = site.value.city   ?? '';
    const region = site.value.region ?? '';
    if (city && region) return `${city}, ${region}`;
    return city || region || '';
});

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
const googleImages = computed<string[]>(() =>
    ((site.value.images ?? []) as string[]).map((img) => {
        if (img.startsWith('storage/') || img.startsWith('/') || /^https?:\/\//.test(img)) {
            return img;
        }
        // Old format: "images/uuid.jpg" → "storage/images/uuid.jpg"
        return 'storage/' + img;
    }),
);

// Quick Actions
const phoneNumber = computed(
    () => site.value.phone ?? '',
);

// Contact Info — no Google read-only computed needed; form fields are directly editable
// ─── Section navigation ────────────────────────────────────────────────────────
type SectionId = 'design' | 'header' | 'about' | 'gallery' | 'quick_actions' | 'reviews' | 'contact' | 'services';

const props = defineProps<{ initialSection?: string }>();
const activeSec = ref<SectionId>(
    (props.initialSection as SectionId | undefined) ?? 'design'
);
const sections: { id: SectionId; label: string; hint: string; icon: any }[] = [
    { id: 'design',        label: 'Colours & look',     hint: 'Pick colours for buttons',   icon: Palette  },
    { id: 'header',        label: 'Top of page',         hint: 'Logo + header background',   icon: Image    },
    { id: 'about',         label: 'About',               hint: 'A sentence about you',       icon: Pencil   },
    { id: 'gallery',       label: 'Photos',              hint: 'Upload your photos',         icon: Image    },
    { id: 'quick_actions', label: 'Buttons',             hint: 'Call, WhatsApp, links',      icon: Phone    },
    { id: 'reviews',       label: 'Reviews',             hint: 'Your star rating',           icon: Star     },
    { id: 'contact',       label: 'Contact details',     hint: 'Address, phone, hours',      icon: MapPin   },
    { id: 'services',      label: 'Services & Pricing',  hint: 'List what you offer',        icon: Sparkles },
];
</script>


<template>
    <div class="flex gap-0 items-start min-h-0">

        <!-- ── Left nav ──────────────────────────────────────────────────────── -->
        <nav class="w-64 flex-shrink-0 flex flex-col gap-0.5 p-1 sticky top-24 self-start">
            <button
                v-for="sec in sections"
                :key="sec.id"
                type="button"
                class="flex items-center gap-3.5 px-4 py-3.5 rounded-lg border-l-4 cursor-pointer text-left w-full transition-colors"
                :class="activeSec === sec.id ? 'bg-brand-blue-soft border-brand-blue' : 'border-transparent bg-transparent hover:bg-brand-panel'"
                @click="activeSec = sec.id"
            >
                <component :is="sec.icon" class="w-5.5 h-5.5 text-brand-ink-soft flex-shrink-0" :class="{ 'text-brand-blue': activeSec === sec.id }" />
                <div class="flex flex-col gap-0.5">
                    <div class="text-sm font-bold text-brand-ink" :class="{ 'text-brand-blue': activeSec === sec.id }">{{ sec.label }}</div>
                    <div class="text-xs text-brand-ink-soft">{{ sec.hint }}</div>
                </div>
            </button>
        </nav>

        <!-- ── Right panel ───────────────────────────────────────────────────── -->
        <div class="flex-1 min-w-0 flex flex-col gap-4 pl-7">

            <!-- ── 0. Design / Colours ────────────────────────────────────────── -->
            <div v-if="activeSec === 'design'" class="flex flex-col gap-4">
                <div class="bg-brand-surface border border-brand-line rounded-2xl p-5">
                    <div class="text-2xl font-black text-brand-ink">Colours &amp; look</div>
                    <div class="text-sm text-brand-ink-soft mt-1.5 leading-relaxed max-w-md">Pick a colour theme that matches your business. It'll be used for buttons and highlights across your site.</div>
                </div>

                <div class="bg-brand-surface border border-brand-line rounded-2xl p-6">
                    <div class="flex items-center gap-2.5 mb-5 flex-wrap">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-yellow-100 text-yellow-900 text-xs font-bold border border-yellow-200">✦ Recommended for your business type</span>
                        <span class="text-sm font-bold text-brand-ink">{{ COLOUR_THEMES.find(t => t.id === recommendedThemeId)?.name }}</span>
                    </div>

                    <div class="grid grid-cols-[repeat(auto-fill,minmax(110px,1fr))] gap-2.5 mb-5">
                        <button
                            v-for="theme in COLOUR_THEMES"
                            :key="theme.id"
                            type="button"
                            class="relative border-2 border-transparent rounded-xl p-3 bg-brand-panel cursor-pointer text-left transition-all hover:bg-brand-line-soft"
                            :class="{ 'border-brand-ink': selectedThemeId === theme.id }"
                            @click="selectTheme(theme.id)"
                        >
                            <div class="flex gap-1 mb-2">
                                <span class="w-5 h-5 rounded-full flex-shrink-0" :style="{ background: theme.palette.primary }" />
                                <span class="w-5 h-5 rounded-full flex-shrink-0" :style="{ background: theme.palette.secondary }" />
                                <span class="w-5 h-5 flex-shrink-0" :style="{ background: theme.palette.primaryMuted }" />
                            </div>
                            <p class="text-xs font-bold text-brand-ink">{{ theme.name }}</p>
                            <span v-if="theme.id === recommendedThemeId" class="absolute top-2 right-2 text-xs text-yellow-600" title="Recommended">✦</span>
                            <CheckCircle2 v-if="selectedThemeId === theme.id" class="absolute bottom-2 right-2 w-3.5 h-3.5 text-brand-ink" />
                        </button>
                        <button
                            type="button"
                            class="relative border-2 border-transparent rounded-xl p-3 bg-brand-panel cursor-pointer text-left transition-all hover:bg-brand-line-soft"
                            :class="{ 'border-brand-ink': selectedThemeId === 'custom' }"
                            @click="selectTheme('custom')"
                        >
                            <div class="flex gap-1 mb-2">
                                <span class="w-5 h-5 rounded-full border-2 border-dashed border-brand-line bg-transparent" />
                                <span class="w-5 h-5 rounded-full border-2 border-dashed border-brand-line bg-transparent" />
                            </div>
                            <p class="text-xs font-bold text-brand-ink">Custom</p>
                            <CheckCircle2 v-if="selectedThemeId === 'custom'" class="absolute bottom-2 right-2 w-3.5 h-3.5 text-brand-ink" />
                        </button>
                    </div>

                    <div v-if="selectedThemeId === 'custom'" class="grid grid-cols-2 gap-4 border border-brand-line rounded-xl p-4 bg-brand-panel mb-5">
                        <div class="flex flex-col gap-2">
                            <label for="palette-primary" class="text-sm font-bold text-brand-ink">Primary colour</label>
                            <div class="flex items-center gap-2">
                                <span class="w-9 h-9 rounded-lg border border-brand-line flex-shrink-0" :style="{ background: customPrimaryInput || '#1e293b' }" />
                                <Input id="palette-primary" v-model="customPrimaryInput" placeholder="#1e40af" class="flex-1 h-9 px-3 py-2 border border-brand-line rounded-lg text-sm text-brand-ink bg-white focus:outline-none focus:border-brand-blue" maxlength="7" />
                            </div>
                            <span class="text-xs text-brand-ink-soft leading-relaxed">Buttons, accents, and highlights</span>
                        </div>
                        <div class="flex flex-col gap-2">
                            <label for="palette-secondary" class="text-sm font-bold text-brand-ink">Secondary colour <span class="font-normal text-brand-ink-soft">(optional)</span></label>
                            <div class="flex items-center gap-2">
                                <span class="w-9 h-9 rounded-lg border border-brand-line flex-shrink-0" :style="{ background: customSecondaryInput || previewPalette.secondary }" />
                                <Input id="palette-secondary" v-model="customSecondaryInput" :placeholder="previewPalette.secondary" class="flex-1 h-9 px-3 py-2 border border-brand-line rounded-lg text-sm text-brand-ink bg-white focus:outline-none focus:border-brand-blue" maxlength="7" />
                            </div>
                            <span class="text-xs text-brand-ink-soft leading-relaxed">Leave blank to auto-generate from primary</span>
                        </div>
                    </div>

                    <div>
                        <p class="text-sm font-bold text-brand-ink mb-2">Preview</p>
                        <div class="flex items-center gap-3 border border-brand-line rounded-xl p-4 flex-wrap">
                            <div class="flex gap-2">
                                <span class="w-7.5 h-7.5 rounded-full" :style="{ background: previewPalette.primary }" />
                                <span class="w-7.5 h-7.5 rounded-full" :style="{ background: previewPalette.secondary }" />
                                <span class="w-7.5 h-7.5 rounded-lg border border-brand-line" :style="{ background: previewPalette.primaryMuted }" />
                            </div>
                            <span class="inline-flex items-center rounded-full py-1.5 px-4 text-sm font-bold" :style="{ background: previewPalette.primary, color: previewPalette.primaryFg }">Call us</span>
                            <span class="inline-flex items-center rounded-full py-1.5 px-4 text-sm font-bold border-2" :style="{ borderColor: previewPalette.primary, color: previewPalette.primary, background: 'transparent' }">Message</span>
                        </div>
                    </div>
                    <p v-if="form.errors.palette_primary" class="text-xs text-brand-danger font-medium mt-2">{{ form.errors.palette_primary }}</p>
                </div>
                <div class="flex justify-end pt-1">
                    <button type="button" class="inline-flex items-center gap-2 h-11 px-7 rounded-lg bg-brand-blue text-white font-bold text-sm cursor-pointer border-none transition-opacity hover:opacity-90 disabled:opacity-50 disabled:cursor-not-allowed" :disabled="saving" @click.prevent="saveDesign">
                        <Loader2 v-show="saving" class="w-4 h-4 animate-spin" />{{ saving ? 'Saving…' : 'Save' }}
                    </button>
                </div>
            </div>

            <!-- ── 1. Header / Top of page ───────────────────────────────────── -->
            <div v-if="activeSec === 'header'" class="flex flex-col gap-4">
                <div class="bg-brand-surface border border-brand-line rounded-2xl p-5 flex items-center justify-between gap-4">
                    <div class="flex-1">
                        <div class="text-2xl font-black text-brand-ink">Top of page</div>
                        <div class="text-sm text-brand-ink-soft mt-1.5 leading-relaxed max-w-sm">Your business name, logo, and the background image shown at the top of your site.</div>
                    </div>
                    <div class="flex items-center gap-2.5 flex-shrink-0">
                        <span class="text-xs text-brand-ink-soft whitespace-nowrap">{{ form.components['header'].enabled ? 'Shows on your site' : 'Hidden from your site' }}</span>
                        <Switch id="toggle-header" v-model="form.components['header'].enabled" />
                    </div>
                </div>

                <div class="bg-brand-surface border border-brand-line rounded-2xl p-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="flex flex-col gap-2">
                            <label for="business-name" class="text-sm font-bold text-brand-ink">Business name</label>
                            <Input id="business-name" v-model="form.business_name" placeholder="Your Business Name" class="w-full h-12 px-3 py-2 border border-brand-line rounded-lg text-sm text-brand-ink bg-white focus:outline-none focus:border-brand-blue" />
                        </div>
                        <div class="flex flex-col gap-2">
                            <label for="business-type" class="text-sm font-bold text-brand-ink">Type of business</label>
                            <Input id="business-type" v-model="form.business_type" placeholder="e.g. Plumber, Hair Salon" class="w-full h-12 px-3 py-2 border border-brand-line rounded-lg text-sm text-brand-ink bg-white focus:outline-none focus:border-brand-blue" />
                        </div>
                        <div class="flex flex-col gap-2">
                            <label for="business-city" class="text-sm font-bold text-brand-ink">City</label>
                            <Input id="business-city" v-model="form.city" placeholder="e.g. London" class="w-full h-12 px-3 py-2 border border-brand-line rounded-lg text-sm text-brand-ink bg-white focus:outline-none focus:border-brand-blue" />
                        </div>
                        <div class="flex flex-col gap-2">
                            <label for="business-region" class="text-sm font-bold text-brand-ink">Region / County</label>
                            <Input id="business-region" v-model="form.region" placeholder="e.g. Greater London" class="w-full h-12 px-3 py-2 border border-brand-line rounded-lg text-sm text-brand-ink bg-white focus:outline-none focus:border-brand-blue" />
                        </div>
                    </div>
                </div>

                <div class="bg-brand-surface border border-brand-line rounded-2xl p-6">
                    <div class="flex flex-col gap-2">
                        <label class="text-sm font-bold text-brand-ink">Your logo</label>
                        <div class="flex items-center gap-4 flex-wrap">
                            <img v-if="logoPreview" :src="logoPreview" alt="Logo preview" class="w-20 h-20 rounded-lg border border-brand-line object-contain bg-white flex-shrink-0" />
                            <div v-else class="w-20 h-20 rounded-lg border border-brand-line border-dashed bg-brand-panel flex items-center justify-center text-xs text-brand-ink-soft text-center p-2 flex-shrink-0">No logo yet</div>
                            <div class="flex flex-col gap-2">
                                <button type="button" class="inline-flex items-center gap-2 h-12 px-5 rounded-lg border border-brand-line bg-transparent text-brand-ink font-bold text-sm cursor-pointer transition-colors hover:bg-brand-panel" @click="triggerLogoUpload">
                                    <Upload class="w-4.5 h-4.5" />
                                    {{ logoPreview ? 'Change logo' : 'Upload a logo' }}
                                </button>
                                <span class="text-xs text-brand-ink-soft leading-relaxed">JPG, PNG or SVG. Max 2MB.</span>
                            </div>
                            <input ref="logoInput" type="file" accept="image/*" class="hidden" @change="onLogoChange" />
                        </div>
                        <p v-if="form.errors.logo" class="text-xs text-brand-danger font-medium">{{ form.errors.logo }}</p>
                    </div>
                </div>

                <div class="bg-brand-surface border border-brand-line rounded-2xl p-6">
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
                <div class="flex justify-end pt-1">
                    <button type="button" class="inline-flex items-center gap-2 h-11 px-7 rounded-lg bg-brand-blue text-white font-bold text-sm cursor-pointer border-none transition-opacity hover:opacity-90 disabled:opacity-50 disabled:cursor-not-allowed" :disabled="saving" @click.prevent="saveHeader">
                        <Loader2 v-show="saving" class="w-4 h-4 animate-spin" />{{ saving ? 'Saving…' : 'Save' }}
                    </button>
                </div>
            </div>

            <!-- ── 2. About ───────────────────────────────────────────────────── -->
            <div v-if="activeSec === 'about'" class="flex flex-col gap-4">
                <div class="bg-brand-surface border border-brand-line rounded-2xl p-5 flex items-center justify-between gap-4">
                    <div class="flex-1">
                        <div class="text-2xl font-black text-brand-ink">About</div>
                        <div class="text-sm text-brand-ink-soft mt-1.5 leading-relaxed max-w-sm">A short description of your business shown near the top of your site.</div>
                    </div>
                    <div class="flex items-center gap-2.5 flex-shrink-0">
                        <span class="text-xs text-brand-ink-soft whitespace-nowrap">{{ form.components['description'].enabled ? 'Shows on your site' : 'Hidden from your site' }}</span>
                        <Switch id="toggle-description" v-model="form.components['description'].enabled" />
                    </div>
                </div>

                <div class="bg-brand-surface border border-brand-line rounded-2xl p-6">
                    <div class="flex flex-col gap-2">
                        <label for="override-description" class="text-sm font-bold text-brand-ink">Description</label>
                        <Textarea
                            id="override-description"
                            v-model="form.overrides.description"
                            placeholder="Tell customers what you do, where you work, and what makes you stand out..."
                            rows="5"
                            class="w-full px-3 py-2 border border-brand-line rounded-lg text-sm text-brand-ink bg-white focus:outline-none focus:border-brand-blue resize-vertical"
                        />
                        <p v-if="form.errors['overrides.description']" class="text-xs text-brand-danger font-medium">{{ form.errors['overrides.description'] }}</p>
                        <button
                            type="button"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg text-xs font-bold bg-brand-panel border border-brand-line text-brand-ink cursor-pointer transition-all hover:border-brand-blue hover:bg-blue-50 hover:text-brand-blue disabled:opacity-50 disabled:cursor-not-allowed self-start"
                            :disabled="isGeneratingDescription"
                            @click="generateDescription"
                        >
                            <Loader2 v-if="isGeneratingDescription" :size="14" class="animate-spin" />
                            <Wand2 v-else :size="14" />
                            {{ isGeneratingDescription ? 'Writing…' : 'Write it for me with AI' }}
                        </button>
                        <p v-if="generateDescriptionError" class="text-xs text-brand-danger font-medium">{{ generateDescriptionError }}</p>
                    </div>
                </div>
                <div class="flex justify-end pt-1">
                    <button type="button" class="inline-flex items-center gap-2 h-11 px-7 rounded-lg bg-brand-blue text-white font-bold text-sm cursor-pointer border-none transition-opacity hover:opacity-90 disabled:opacity-50 disabled:cursor-not-allowed" :disabled="saving" @click.prevent="saveAbout">
                        <Loader2 v-show="saving" class="w-4 h-4 animate-spin" />{{ saving ? 'Saving…' : 'Save' }}
                    </button>
                </div>
            </div>

            <!-- ── 3. Gallery / Photos ─────────────────────────────────────────── -->
            <div v-if="activeSec === 'gallery'" class="flex flex-col gap-4">
                <div class="bg-brand-surface border border-brand-line rounded-2xl p-5 flex items-center justify-between gap-4">
                    <div class="flex-1">
                        <div class="text-2xl font-black text-brand-ink">Photos</div>
                        <div class="text-sm text-brand-ink-soft mt-1.5 leading-relaxed max-w-sm">Photos shown in the gallery on your site. Drag to reorder — the first photo is used as the hero background.</div>
                    </div>
                    <div class="flex items-center gap-2.5 flex-shrink-0">
                        <span class="text-xs text-brand-ink-soft whitespace-nowrap">{{ form.components['gallery'].enabled ? 'Shows on your site' : 'Hidden from your site' }}</span>
                        <Switch id="toggle-gallery" v-model="form.components['gallery'].enabled" />
                    </div>
                </div>

                <div class="bg-brand-surface border border-brand-line rounded-2xl p-6">
                    <!-- Existing photos grid -->
                    <template v-if="form.images_order.length > 0">
                        <p class="text-sm font-bold text-brand-ink mb-2.5">
                            {{ form.images_order.length }} photo{{ form.images_order.length === 1 ? '' : 's' }}
                            <span class="text-xs font-normal text-brand-ink-soft ml-1.5">Drag to reorder.</span>
                        </p>
                        <div class="grid grid-cols-[repeat(auto-fill,minmax(100px,1fr))] gap-2 mb-4">
                            <div
                                v-for="(rawPath, index) in form.images_order"
                                :key="rawPath"
                                class="relative rounded-lg overflow-hidden cursor-grab border-2 border-transparent transition-all aspect-square bg-brand-panel user-select-none hover:border-brand-blue group"
                                :class="{
                                    'opacity-35 cursor-grabbing': dragSourceIndex === index,
                                    'border-brand-blue shadow-[0_0_0_3px_rgba(30,102,245,0.25)]': dragOverIndex === index && dragSourceIndex !== index,
                                }"
                                draggable="true"
                                @dragstart="onPhotoDragStart(index)"
                                @dragover="onPhotoDragOver($event, index)"
                                @dragend="onPhotoDragEnd"
                            >
                                <img
                                    :src="imageDisplayUrl(rawPath)"
                                    :alt="`Photo ${index + 1}`"
                                    class="w-full h-full object-cover block pointer-events-none"
                                    draggable="false"
                                />
                                <span v-if="index === 0" class="absolute bottom-1 left-1 bg-black/65 text-white text-xs font-bold px-1.5 py-0.5 rounded pointer-events-none leading-relaxed">Featured</span>
                                <button
                                    type="button"
                                    class="absolute top-1 right-1 w-6 h-6 rounded-full bg-black/60 text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer border-none"
                                    @click.stop="removeExistingPhoto(rawPath)"
                                    title="Remove photo"
                                >
                                    <X class="w-3 h-3" />
                                </button>
                            </div>
                        </div>
                    </template>

                    <!-- New photos queued for upload -->
                    <template v-if="photoNewPreviews.length > 0">
                        <p class="text-sm font-bold text-brand-ink mb-2">{{ photoNewPreviews.length }} new photo{{ photoNewPreviews.length === 1 ? '' : 's' }} ready to upload</p>
                        <div class="grid grid-cols-[repeat(auto-fill,minmax(100px,1fr))] gap-2 mb-4">
                            <div
                                v-for="(preview, index) in photoNewPreviews"
                                :key="preview.url"
                                class="relative rounded-lg overflow-hidden border-2 border-brand-blue aspect-square bg-brand-panel group"
                            >
                                <img :src="preview.url" :alt="`New photo ${index + 1}`" class="w-full h-full object-cover block" />
                                <button
                                    type="button"
                                    class="absolute top-1 right-1 w-6 h-6 rounded-full bg-black/60 text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer border-none"
                                    @click.stop="removeNewPhoto(index)"
                                    title="Remove"
                                >
                                    <X class="w-3 h-3" />
                                </button>
                            </div>
                        </div>
                    </template>

                    <p v-if="form.images_order.length === 0 && photoNewPreviews.length === 0" class="text-sm italic text-brand-ink-soft mb-4">No photos yet. Upload some to bring your site to life.</p>

                    <button type="button" class="inline-flex items-center gap-2 h-12 px-5 rounded-lg border border-brand-line bg-transparent text-brand-ink font-bold text-sm cursor-pointer transition-colors hover:bg-brand-panel" @click="triggerPhotoUpload">
                        <Upload class="w-4.5 h-4.5" /> Upload photos
                    </button>
                    <input ref="photoInput" type="file" accept="image/*" multiple class="hidden" @change="onPhotoFilesChange" />
                    <p class="text-xs text-brand-ink-soft leading-relaxed mt-2">JPG, PNG or WEBP. Max 10 MB per photo.</p>
                </div>
                <div class="flex justify-end pt-1">
                    <button type="button" class="inline-flex items-center gap-2 h-11 px-7 rounded-lg bg-brand-blue text-white font-bold text-sm cursor-pointer border-none transition-opacity hover:opacity-90 disabled:opacity-50 disabled:cursor-not-allowed" :disabled="saving" @click.prevent="saveGallery">
                        <Loader2 v-show="saving" class="w-4 h-4 animate-spin" />{{ saving ? 'Saving…' : 'Save' }}
                    </button>
                </div>
            </div>

            <!-- ── 4. Buttons / Quick Actions ─────────────────────────────────── -->
            <div v-if="activeSec === 'quick_actions'" class="flex flex-col gap-4">
                <div class="bg-brand-surface border border-brand-line rounded-2xl p-5 flex items-center justify-between gap-4">
                    <div class="flex-1">
                        <div class="text-2xl font-black text-brand-ink">Buttons</div>
                        <div class="text-sm text-brand-ink-soft mt-1.5 leading-relaxed max-w-sm">Buttons that let visitors call you, message you, or click a link.</div>
                    </div>
                    <div class="flex items-center gap-2.5 flex-shrink-0">
                        <span class="text-xs text-brand-ink-soft whitespace-nowrap">{{ form.components['quick_actions'].enabled ? 'Shows on your site' : 'Hidden from your site' }}</span>
                        <Switch id="toggle-quick_actions" v-model="form.components['quick_actions'].enabled" />
                    </div>
                </div>

                <div class="bg-brand-surface border border-brand-line rounded-2xl p-6">
                    <div class="flex flex-col gap-2">
                        <label for="phone-number" class="text-sm font-bold text-brand-ink flex items-center gap-1.5">
                            <Phone class="w-4 h-4" />
                            Phone number
                        </label>
                        <Input
                            id="phone-number"
                            v-model="form.phone"
                            type="tel"
                            placeholder="+44 7911 123456"
                            class="w-full h-12 px-3 py-2 border border-brand-line rounded-lg text-sm text-brand-ink bg-white focus:outline-none focus:border-brand-blue"
                        />
                        <span class="text-xs text-brand-ink-soft leading-relaxed">Shown on the Call button and Contact section.</span>
                        <p v-if="form.errors.phone" class="text-xs text-brand-danger font-medium">{{ form.errors.phone }}</p>
                    </div>
                </div>

                <div class="bg-brand-surface border border-brand-line rounded-2xl p-6">
                    <div class="flex flex-col gap-2">
                        <label for="whatsapp-number" class="text-sm font-bold text-brand-ink flex items-center gap-1.5">
                            <MessageCircle class="w-4 h-4" style="color: #25D366;" />
                            WhatsApp number
                        </label>
                        <Input
                            id="whatsapp-number"
                            v-model="form.whatsapp_number"
                            placeholder="447911123456"
                            class="w-full h-12 px-3 py-2 border border-brand-line rounded-lg text-sm text-brand-ink bg-white focus:outline-none focus:border-brand-blue"
                        />
                        <span class="text-xs text-brand-ink-soft leading-relaxed">Adds a WhatsApp button. Include your country code without the + sign — UK numbers start with 44, e.g. <span class="font-mono text-xs bg-brand-panel px-1.5 py-0.5 rounded">447911123456</span>.</span>
                        <p v-if="form.errors.whatsapp_number" class="text-xs text-brand-danger font-medium">{{ form.errors.whatsapp_number }}</p>
                    </div>
                </div>

                <div class="bg-brand-surface border border-brand-line rounded-2xl p-6">
                    <div class="mb-4">
                        <div class="text-sm font-bold text-brand-ink">Custom buttons</div>
                        <p class="text-xs text-brand-ink-soft leading-relaxed">Add buttons that link to booking pages, menus, price lists, or anywhere else.</p>
                    </div>

                    <div v-if="form.quickLinks.length > 0" class="flex flex-col gap-3 mb-1">
                        <div
                            v-for="(link, index) in form.quickLinks"
                            :key="index"
                            class="flex flex-col gap-2.5 border border-brand-line rounded-lg p-3.5"
                        >
                            <div class="flex gap-3">
                                <div class="flex-1 flex flex-col gap-2">
                                    <label :for="`ql-label-${index}`" class="text-sm font-bold text-brand-ink">Button label</label>
                                    <Input :id="`ql-label-${index}`" v-model="link.label" placeholder="e.g. Book a quote" class="w-full h-12 px-3 py-2 border border-brand-line rounded-lg text-sm text-brand-ink bg-white focus:outline-none focus:border-brand-blue" />
                                </div>
                                <div class="flex-1 flex flex-col gap-2">
                                    <label :for="`ql-link-${index}`" class="text-sm font-bold text-brand-ink">Link</label>
                                    <Input :id="`ql-link-${index}`" v-model="link.link" placeholder="https://..." type="url" class="w-full h-12 px-3 py-2 border border-brand-line rounded-lg text-sm text-brand-ink bg-white focus:outline-none focus:border-brand-blue" @blur="onLinkBlur(index)" />
                                </div>
                            </div>
                            <div class="flex items-center justify-between gap-4">
                                <div v-if="link.label || link.link" class="flex items-center gap-2">
                                    <span class="text-xs text-brand-ink-soft">Preview:</span>
                                    <span
                                        :style="linkBrandStyle(link.link)"
                                        class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium"
                                        :class="!detectPlatformBrand(link.link) ? 'bg-brand-ink text-white' : ''"
                                    >
                                        {{ link.label || 'Button label' }}
                                        <img
                                            v-if="linkFavicon(link.link)"
                                            :src="linkFavicon(link.link)!"
                                            class="w-3.5 h-3.5 rounded object-contain"
                                            aria-hidden="true"
                                        />
                                    </span>
                                </div>
                                <button
                                    type="button"
                                    class="inline-flex items-center justify-center w-9.5 h-9.5 border-none bg-transparent rounded-lg cursor-pointer text-brand-ink-soft transition-all hover:bg-brand-panel hover:text-brand-ink flex-shrink-0 hover:bg-red-50 hover:text-red-600"
                                    @click="removeQuickLink(index)"
                                    :aria-label="`Remove button ${index + 1}`"
                                >
                                    <Trash2 class="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                    <p v-else class="text-sm text-brand-ink-soft italic">No custom buttons yet.</p>

                    <button type="button" class="inline-flex items-center justify-center gap-2 w-full h-12 px-5 rounded-lg border border-brand-line bg-transparent text-brand-ink font-bold text-sm cursor-pointer transition-colors hover:bg-brand-panel mt-3" @click="addQuickLink">
                        <Plus class="w-4.5 h-4.5" /> Add a button
                    </button>
                </div>
                <div class="flex justify-end pt-1">
                    <button type="button" class="inline-flex items-center gap-2 h-11 px-7 rounded-lg bg-brand-blue text-white font-bold text-sm cursor-pointer border-none transition-opacity hover:opacity-90 disabled:opacity-50 disabled:cursor-not-allowed" :disabled="saving" @click.prevent="saveQuickActions">
                        <Loader2 v-show="saving" class="w-4 h-4 animate-spin" />{{ saving ? 'Saving…' : 'Save' }}
                    </button>
                </div>
            </div>

            <!-- ── 5. Reviews ──────────────────────────────────────────────────── -->
            <div v-if="activeSec === 'reviews'" class="flex flex-col gap-4">
                <div class="bg-brand-surface border border-brand-line rounded-2xl p-5 flex items-center justify-between gap-4">
                    <div class="flex-1">
                        <div class="text-2xl font-black text-brand-ink">Reviews</div>
                        <div class="text-sm text-brand-ink-soft mt-1.5 leading-relaxed max-w-sm">Your overall star rating, review count, and any customer testimonials to highlight.</div>
                    </div>
                    <div class="flex items-center gap-2.5 flex-shrink-0">
                        <span class="text-xs text-brand-ink-soft whitespace-nowrap">{{ form.components['reviews'].enabled ? 'Shows on your site' : 'Hidden from your site' }}</span>
                        <Switch id="toggle-reviews" v-model="form.components['reviews'].enabled" />
                    </div>
                </div>

                <!-- Aggregate rating + count -->
                <div class="bg-brand-surface border border-brand-line rounded-2xl p-6">
                    <div class="text-sm font-bold text-brand-ink mb-4">Overall rating</div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="flex flex-col gap-2">
                            <label for="review-rating" class="text-sm font-bold text-brand-ink">Star rating</label>
                            <div class="flex items-center gap-2">
                                <button
                                    v-for="n in 5"
                                    :key="n"
                                    type="button"
                                    class="border-none bg-transparent cursor-pointer p-0 leading-none"
                                    @click="form.rating = n"
                                >
                                    <Star
                                        class="w-7 h-7 transition-colors"
                                        :class="n <= (form.rating ?? 0) ? 'text-yellow-400' : 'text-brand-line'"
                                        :fill="n <= (form.rating ?? 0) ? 'currentColor' : 'none'"
                                    />
                                </button>
                                <span class="text-sm text-brand-ink-soft ml-1">{{ form.rating ?? '—' }}</span>
                            </div>
                            <span class="text-xs text-brand-ink-soft">Click a star to set your rating.</span>
                        </div>
                        <div class="flex flex-col gap-2">
                            <label for="review-count" class="text-sm font-bold text-brand-ink">Number of reviews</label>
                            <Input
                                id="review-count"
                                v-model.number="form.review_count"
                                type="number"
                                min="0"
                                placeholder="e.g. 142"
                                class="w-full h-12 px-3 py-2 border border-brand-line rounded-lg text-sm text-brand-ink bg-white focus:outline-none focus:border-brand-blue"
                            />
                            <span class="text-xs text-brand-ink-soft">Shown as "142 reviews" on your site.</span>
                        </div>
                    </div>
                </div>

                <!-- Testimonials -->
                <div class="bg-brand-surface border border-brand-line rounded-2xl p-6">
                    <div class="mb-4">
                        <div class="text-sm font-bold text-brand-ink">Testimonials</div>
                        <p class="text-xs text-brand-ink-soft leading-relaxed">Add quotes from happy customers to show on your site.</p>
                    </div>

                    <div v-if="form.reviews.length > 0" class="flex flex-col gap-4 mb-4">
                        <div
                            v-for="(review, i) in form.reviews"
                            :key="review.id"
                            class="border border-brand-line rounded-xl p-4 flex flex-col gap-3"
                        >
                            <div class="flex items-center justify-between gap-3">
                                <div class="flex items-center gap-1">
                                    <button
                                        v-for="n in 5"
                                        :key="n"
                                        type="button"
                                        class="border-none bg-transparent cursor-pointer p-0 leading-none"
                                        @click="review.rating = n"
                                    >
                                        <Star
                                            class="w-5 h-5 transition-colors"
                                            :class="n <= review.rating ? 'text-yellow-400' : 'text-brand-line'"
                                            :fill="n <= review.rating ? 'currentColor' : 'none'"
                                        />
                                    </button>
                                </div>
                                <button
                                    type="button"
                                    class="inline-flex items-center justify-center w-8 h-8 border-none bg-transparent rounded-lg cursor-pointer text-brand-ink-soft transition-all hover:bg-red-50 hover:text-red-600"
                                    @click="removeReview(i)"
                                >
                                    <Trash2 class="w-4 h-4" />
                                </button>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-xs font-bold text-brand-ink-soft uppercase tracking-wide">Customer name</label>
                                    <Input v-model="review.author" placeholder="Jane Smith" class="h-10 px-3 border border-brand-line rounded-lg text-sm text-brand-ink bg-white focus:outline-none focus:border-brand-blue" />
                                </div>
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-xs font-bold text-brand-ink-soft uppercase tracking-wide">Date (optional)</label>
                                    <Input v-model="review.date" placeholder="e.g. March 2024" class="h-10 px-3 border border-brand-line rounded-lg text-sm text-brand-ink bg-white focus:outline-none focus:border-brand-blue" />
                                </div>
                            </div>
                            <div class="flex flex-col gap-1.5">
                                <label class="text-xs font-bold text-brand-ink-soft uppercase tracking-wide">Review text</label>
                                <Textarea v-model="review.text" placeholder="What did they say about you?" rows="2" class="px-3 py-2 border border-brand-line rounded-lg text-sm text-brand-ink bg-white focus:outline-none focus:border-brand-blue resize-none" />
                            </div>
                        </div>
                    </div>
                    <p v-else class="text-sm text-brand-ink-soft italic mb-4">No testimonials added yet.</p>

                    <button type="button" class="inline-flex items-center justify-center gap-2 w-full h-12 px-5 rounded-lg border border-brand-line bg-transparent text-brand-ink font-bold text-sm cursor-pointer transition-colors hover:bg-brand-panel" @click="addReview">
                        <Plus class="w-4.5 h-4.5" /> Add a testimonial
                    </button>
                </div>
                <div class="flex justify-end pt-1">
                    <button type="button" class="inline-flex items-center gap-2 h-11 px-7 rounded-lg bg-brand-blue text-white font-bold text-sm cursor-pointer border-none transition-opacity hover:opacity-90 disabled:opacity-50 disabled:cursor-not-allowed" :disabled="saving" @click.prevent="saveReviews">
                        <Loader2 v-show="saving" class="w-4 h-4 animate-spin" />{{ saving ? 'Saving…' : 'Save' }}
                    </button>
                </div>
            </div>

            <!-- ── 6. Contact ─────────────────────────────────────────────────── -->
            <div v-if="activeSec === 'contact'" class="flex flex-col gap-4">
                <div class="bg-brand-surface border border-brand-line rounded-2xl p-5 flex items-center justify-between gap-4">
                    <div class="flex-1">
                        <div class="text-2xl font-black text-brand-ink">Contact details</div>
                        <div class="text-sm text-brand-ink-soft mt-1.5 leading-relaxed max-w-sm">Your address, phone number, opening hours, and social media links.</div>
                    </div>
                    <div class="flex items-center gap-2.5 flex-shrink-0">
                        <span class="text-xs text-brand-ink-soft whitespace-nowrap">{{ form.components['contact'].enabled ? 'Shows on your site' : 'Hidden from your site' }}</span>
                        <Switch id="toggle-contact" v-model="form.components['contact'].enabled" />
                    </div>
                </div>

                <!-- Address -->
                <div class="bg-brand-surface border border-brand-line rounded-2xl p-6">
                    <div class="flex flex-col gap-2">
                        <label for="formatted-address" class="text-sm font-bold text-brand-ink">Address</label>
                        <Input
                            id="formatted-address"
                            v-model="form.formatted_address"
                            placeholder="123 High Street, London, EC1A 1BB"
                            class="w-full h-12 px-3 py-2 border border-brand-line rounded-lg text-sm text-brand-ink bg-white focus:outline-none focus:border-brand-blue"
                        />
                        <p v-if="form.errors.formatted_address" class="text-xs text-brand-danger font-medium">{{ form.errors.formatted_address }}</p>
                    </div>
                </div>

                <!-- Contact email -->
                <div class="bg-brand-surface border border-brand-line rounded-2xl p-6">
                    <div class="flex flex-col gap-2">
                        <label for="contact-email" class="text-sm font-bold text-brand-ink">Contact email</label>
                        <Input
                            id="contact-email"
                            v-model="form.overrides.contact_email"
                            type="email"
                            placeholder="hello@yourbusiness.com"
                            class="w-full h-12 px-3 py-2 border border-brand-line rounded-lg text-sm text-brand-ink bg-white focus:outline-none focus:border-brand-blue"
                        />
                        <span class="text-xs text-brand-ink-soft leading-relaxed">Used for the Message button{{ isPremium ? ' and contact form' : '' }}.</span>
                        <p v-if="form.errors['overrides.contact_email']" class="text-xs text-brand-danger font-medium">{{ form.errors['overrides.contact_email'] }}</p>
                    </div>
                </div>

                <!-- Opening hours -->
                <div class="bg-brand-surface border border-brand-line rounded-2xl p-6">
                    <div class="text-sm font-bold text-brand-ink mb-4">Opening hours</div>
                    <div class="flex flex-col gap-2">
                        <div
                            v-for="(row, index) in form.opening_hours"
                            :key="row.day"
                            class="grid grid-cols-[7rem_auto_1fr_auto_1fr] items-center gap-3"
                        >
                            <span class="text-sm font-medium text-brand-ink">{{ row.day }}</span>
                            <Switch :id="`oh-toggle-${index}`" :model-value="makeIsOpen(index).value" @update:model-value="makeIsOpen(index).value = $event" />
                            <template v-if="!row.closed">
                                <Input v-model="row.open" type="time" class="h-9 px-2 border border-brand-line rounded-lg text-sm text-brand-ink bg-white focus:outline-none focus:border-brand-blue" />
                                <span class="text-xs text-brand-ink-soft text-center">to</span>
                                <Input v-model="row.close" type="time" class="h-9 px-2 border border-brand-line rounded-lg text-sm text-brand-ink bg-white focus:outline-none focus:border-brand-blue" />
                            </template>
                            <template v-else>
                                <span class="col-span-3 text-sm italic text-brand-ink-soft">Closed</span>
                            </template>
                        </div>
                    </div>
                </div>

                <div class="bg-brand-surface border border-brand-line rounded-2xl p-6" :class="{ 'opacity-60': !isPremium }">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <div class="text-base font-bold text-brand-ink flex items-center gap-2">
                                Contact form
                                <span v-if="!isPremium" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-yellow-100 text-yellow-900 text-xs font-bold">
                                    <Sparkles class="w-2.75 h-2.75" /> Premium
                                </span>
                            </div>
                            <div class="text-sm text-brand-ink-soft leading-relaxed mt-1">Show a "Send us a message" form so visitors can email you directly.</div>
                        </div>
                        <div class="flex items-center gap-2.5 flex-shrink-0">
                            <template v-if="isPremium">
                                <span class="text-xs text-brand-ink-soft whitespace-nowrap">{{ form.components['contact_form'].enabled ? 'Shows on your site' : 'Hidden from your site' }}</span>
                                <Switch id="toggle-contact_form" v-model="form.components['contact_form'].enabled" />
                            </template>
                            <Lock v-else class="w-4.5 h-4.5 text-brand-ink-soft" />
                        </div>
                    </div>
                </div>

                <div class="bg-brand-surface border border-brand-line rounded-2xl p-6">
                    <div class="mb-4">
                        <div class="text-sm font-bold text-brand-ink">Social media links</div>
                        <p class="text-xs text-brand-ink-soft leading-relaxed">Add links to your social media pages so customers can follow you.</p>
                    </div>
                    <div class="flex flex-col gap-4">
                        <div class="flex flex-col gap-2">
                            <label for="social-instagram" class="text-sm font-bold text-brand-ink">Instagram page link</label>
                            <Input id="social-instagram" v-model="form.socials.instagram" placeholder="https://www.instagram.com/yourbusiness" type="url" class="w-full h-12 px-3 py-2 border border-brand-line rounded-lg text-sm text-brand-ink bg-white focus:outline-none focus:border-brand-blue" />
                            <p v-if="form.errors['socials.instagram']" class="text-xs text-brand-danger font-medium">{{ form.errors['socials.instagram'] }}</p>
                        </div>
                        <div class="flex flex-col gap-2">
                            <label for="social-facebook" class="text-sm font-bold text-brand-ink">Facebook page link</label>
                            <Input id="social-facebook" v-model="form.socials.facebook" placeholder="https://www.facebook.com/yourbusiness" type="url" class="w-full h-12 px-3 py-2 border border-brand-line rounded-lg text-sm text-brand-ink bg-white focus:outline-none focus:border-brand-blue" />
                            <p v-if="form.errors['socials.facebook']" class="text-xs text-brand-danger font-medium">{{ form.errors['socials.facebook'] }}</p>
                        </div>
                        <div class="flex flex-col gap-2">
                            <label for="social-x" class="text-sm font-bold text-brand-ink">X (Twitter) page link</label>
                            <Input id="social-x" v-model="form.socials.x" placeholder="https://x.com/yourbusiness" type="url" class="w-full h-12 px-3 py-2 border border-brand-line rounded-lg text-sm text-brand-ink bg-white focus:outline-none focus:border-brand-blue" />
                            <p v-if="form.errors['socials.x']" class="text-xs text-brand-danger font-medium">{{ form.errors['socials.x'] }}</p>
                        </div>
                        <div class="flex flex-col gap-2">
                            <label for="social-linkedin" class="text-sm font-bold text-brand-ink">LinkedIn page link</label>
                            <Input id="social-linkedin" v-model="form.socials.linkedin" placeholder="https://www.linkedin.com/company/yourbusiness" type="url" class="w-full h-12 px-3 py-2 border border-brand-line rounded-lg text-sm text-brand-ink bg-white focus:outline-none focus:border-brand-blue" />
                            <p v-if="form.errors['socials.linkedin']" class="text-xs text-brand-danger font-medium">{{ form.errors['socials.linkedin'] }}</p>
                        </div>
                    </div>
                </div>
                <div class="flex justify-end pt-1">
                    <button type="button" class="inline-flex items-center gap-2 h-11 px-7 rounded-lg bg-brand-blue text-white font-bold text-sm cursor-pointer border-none transition-opacity hover:opacity-90 disabled:opacity-50 disabled:cursor-not-allowed" :disabled="saving" @click.prevent="saveContact">
                        <Loader2 v-show="saving" class="w-4 h-4 animate-spin" />{{ saving ? 'Saving…' : 'Save' }}
                    </button>
                </div>
            </div>

            <!-- ── 7. Services ────────────────────────────────────────────────── -->
            <div v-if="activeSec === 'services'" class="flex flex-col gap-4">
                <div class="bg-brand-surface border border-brand-line rounded-2xl p-5 flex items-center justify-between gap-4">
                    <div class="flex-1">
                        <div class="text-2xl font-black text-brand-ink">Services &amp; Pricing</div>
                        <div class="text-sm text-brand-ink-soft mt-1.5 leading-relaxed max-w-sm">List what you offer so customers know what to expect.</div>
                    </div>
                    <div class="flex items-center gap-2.5 flex-shrink-0">
                        <span class="text-xs text-brand-ink-soft whitespace-nowrap">{{ form.components['services'].enabled ? 'Shows on your site' : 'Hidden from your site' }}</span>
                        <Switch id="toggle-services" v-model="form.components['services'].enabled" />
                    </div>
                </div>

                <div class="bg-brand-surface border border-brand-line rounded-2xl p-6">
                    <div class="flex flex-col gap-2">
                        <label for="services-heading" class="text-sm font-bold text-brand-ink">Section heading</label>
                        <Input id="services-heading" v-model="form.services_heading" placeholder="Our Services" class="w-full h-12 px-3 py-2 border border-brand-line rounded-lg text-sm text-brand-ink bg-white focus:outline-none focus:border-brand-blue" />
                    </div>
                </div>

                <div class="bg-brand-surface border border-brand-line rounded-2xl p-6">
                    <div v-if="form.services.length > 0" class="flex flex-col gap-2 mb-1">
                        <div
                            v-for="(service, index) in form.services"
                            :key="service.id"
                            class="border border-brand-line rounded-xl overflow-hidden"
                        >
                            <div class="flex items-center gap-3 px-3.5 py-3 bg-brand-panel">
                                <button
                                    type="button"
                                    class="bg-none border-none cursor-pointer p-1 text-brand-ink-soft transition-colors flex-shrink-0 hover:text-yellow-500"
                                    :class="{ 'text-yellow-500': service.featured }"
                                    @click="toggleServiceFeatured(index)"
                                    :title="service.featured ? 'Remove from featured' : 'Mark as featured'"
                                >
                                    <Star class="w-4 h-4" :fill="service.featured ? 'currentColor' : 'none'" />
                                </button>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-bold text-brand-ink whitespace-nowrap overflow-hidden text-ellipsis">{{ service.name || '(no name)' }}</p>
                                    <p class="text-xs text-brand-ink-soft" :class="{ 'italic': !service.price || !service.show_price }">{{ service.price && service.show_price ? formatPrice(service.price, service.currency) : 'No price' }}</p>
                                </div>
                                <div class="flex items-center gap-1 flex-shrink-0">
                                    <button type="button" class="inline-flex items-center justify-center w-9.5 h-9.5 border-none bg-transparent rounded-lg cursor-pointer text-brand-ink-soft transition-all hover:bg-brand-panel hover:text-brand-ink" @click="toggleServiceEdit(index)">
                                        <X v-if="editingServiceIndex === index" class="w-4 h-4" />
                                        <Pencil v-else class="w-4 h-4" />
                                    </button>
                                    <button type="button" class="inline-flex items-center justify-center w-9.5 h-9.5 border-none bg-transparent rounded-lg cursor-pointer text-brand-ink-soft transition-all hover:bg-red-50 hover:text-red-600" @click="removeService(index)">
                                        <Trash2 class="w-4 h-4" />
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
                    <p v-else class="text-sm text-brand-ink-soft italic">No services added yet. Click "Add a service" below to get started.</p>

                    <button type="button" class="inline-flex items-center justify-center gap-2 w-full h-12 px-5 rounded-lg border border-brand-line bg-transparent text-brand-ink font-bold text-sm cursor-pointer transition-colors hover:bg-brand-panel mt-3" @click="addService">
                        <Plus class="w-4.5 h-4.5" /> Add a service
                    </button>
                </div>

                <div class="bg-brand-surface border border-brand-line rounded-2xl p-6">
                    <div class="mb-4">
                        <div class="text-sm font-bold text-brand-ink">Call-to-action button <span class="font-normal text-brand-ink-soft">(optional)</span></div>
                        <p class="text-xs text-brand-ink-soft leading-relaxed">A button shown below your services list. Leave the link blank to use your phone number instead.</p>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="flex flex-col gap-2">
                            <label for="services-cta-label" class="text-sm font-bold text-brand-ink">Button text</label>
                            <Input id="services-cta-label" v-model="form.services_cta_label" placeholder="e.g. Get a free quote" class="w-full h-12 px-3 py-2 border border-brand-line rounded-lg text-sm text-brand-ink bg-white focus:outline-none focus:border-brand-blue" />
                        </div>
                        <div class="flex flex-col gap-2">
                            <label for="services-cta-link" class="text-sm font-bold text-brand-ink">Button link</label>
                            <Input id="services-cta-link" v-model="form.services_cta_link" type="url" placeholder="https://..." class="w-full h-12 px-3 py-2 border border-brand-line rounded-lg text-sm text-brand-ink bg-white focus:outline-none focus:border-brand-blue" />
                            <p v-if="form.errors['services_cta_link']" class="text-xs text-brand-danger font-medium">{{ form.errors['services_cta_link'] }}</p>
                        </div>
                    </div>
                </div>
                <div class="flex justify-end pt-1">
                    <button type="button" class="inline-flex items-center gap-2 h-11 px-7 rounded-lg bg-brand-blue text-white font-bold text-sm cursor-pointer border-none transition-opacity hover:opacity-90 disabled:opacity-50 disabled:cursor-not-allowed" :disabled="saving" @click.prevent="saveServices">
                        <Loader2 v-show="saving" class="w-4 h-4 animate-spin" />{{ saving ? 'Saving…' : 'Save' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
