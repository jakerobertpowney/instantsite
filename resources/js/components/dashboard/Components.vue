<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { components as dashboardComponents } from '@/routes/dashboard';
import { toast } from 'vue-sonner';
import { Button } from '@/components/ui/button';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Input } from '@/components/ui/input';
import { CheckCircle2, ExternalLink, Loader2, Upload, XCircle } from 'lucide-vue-next';

type ComponentKey = 'header' | 'description' | 'gallery' | 'quick_actions' | 'reviews' | 'contact';
type ComponentState = Record<ComponentKey, { enabled: boolean }>;
type Socials = { instagram: string; facebook: string; x: string; linkedin: string };
type SiteData = Record<string, any>;

const componentKeys: ComponentKey[] = ['header', 'description', 'gallery', 'quick_actions', 'reviews', 'contact'];
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

const form = useForm<{
    components: ComponentState;
    overrides: { description: string };
    socials: Socials;
    logo: File | null;
}>({
    components: buildInitialComponents(),
    overrides: {
        description: siteOverrides.value.description ?? '',
    },
    socials: buildInitialSocials(),
    logo: null,
});

watch(
    () => page.props.site,
    () => {
        form.components = buildInitialComponents();
        form.overrides.description = siteOverrides.value.description ?? '';
        form.socials = buildInitialSocials();
        form.logo = null;
        logoPreview.value = siteOverrides.value.logo_path ?? null;
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
const quickLinks = computed<Array<{ label: string }>>(() => siteData.value.quickLinks ?? []);

// Reviews
const rating = computed(() => siteData.value.rating ?? null);
const reviewCount = computed(() => siteData.value.userRatingCount ?? null);

// Contact Info
const formattedAddress = computed(() => siteData.value.formattedAddress ?? '');
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

        <Tabs default-value="header">
            <TabsList class="flex-wrap h-auto gap-1">
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
                        <p class="flex items-center gap-1.5 text-sm text-muted-foreground">
                            <ExternalLink class="h-3.5 w-3.5 shrink-0" />
                            Phone number is pulled from your Google Business Profile
                        </p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div class="rounded-lg bg-muted/50 px-4 py-3 flex flex-col gap-1">
                                <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wide">Phone number</p>
                                <p class="text-sm font-medium">{{ phoneNumber || 'No phone number found' }}</p>
                            </div>
                            <div class="rounded-lg bg-muted/50 px-4 py-3 flex flex-col gap-1">
                                <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wide">Custom links</p>
                                <template v-if="quickLinks.length > 0">
                                    <p v-for="(link, index) in quickLinks" :key="index" class="text-sm font-medium">
                                        {{ link.label }}
                                    </p>
                                </template>
                                <p v-else class="text-sm text-muted-foreground">None added</p>
                            </div>
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
