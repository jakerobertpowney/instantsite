<script setup lang="ts">
import Gallery from '@/components/site/Gallery.vue';
import Contact from '@/components/site/Contact.vue';
import QuickActions from '@/components/site/QuickActions.vue';
import Description from '@/components/site/Description.vue';
import Header from '@/components/site/Header.vue';
import Reviews from '@/components/site/Reviews.vue';
import { Head } from '@inertiajs/vue3';
import { computed, onMounted } from 'vue';
import { getEffectivePalette, paletteToCssVars } from '@/lib/palette';

const props = defineProps({
    data: Object
})

// Resolve component visibility flags — default to true when absent (legacy sites)
const components = computed(() => {
    const flags = props.data?.components ?? {};
    return {
        header:        flags.header?.enabled        !== false,
        description:   flags.description?.enabled   !== false,
        gallery:       flags.gallery?.enabled       !== false,
        quick_actions: flags.quick_actions?.enabled !== false,
        reviews:       flags.reviews?.enabled       !== false,
        contact:       flags.contact?.enabled       !== false,
        contact_form:  flags.contact_form?.enabled  !== false,
    };
});

// Use overrides when present, fall back to Google Places data
const description   = computed(() => props.data?.overrides?.description || props.data?.editorialSummary?.text || props.data?.description);
const logo          = computed(() => props.data?.overrides?.logo_path || props.data?.logo);
const contactEmail  = computed(() => props.data?.overrides?.contact_email || props.data?.contact);

// Colour palette — user-chosen custom colours take priority over auto-detected
const palette    = computed(() => getEffectivePalette(props.data));
const cssVars    = computed(() => paletteToCssVars(palette.value));

// Hero background — respects the user-chosen override, falls back to auto behaviour
const headerBg = computed(() => props.data?.overrides?.header_bg ?? null);

const heroStyle = computed<Record<string, string>>(() => {
    const bg = headerBg.value;

    // Explicit colour
    if (bg?.type === 'color' && bg.value) {
        return { backgroundColor: bg.value };
    }

    // Google image — relative path stored in site.data.images[]
    if (bg?.type === 'google_image' && bg.value) {
        return {
            backgroundImage:    `url('/${bg.value}')`,
            backgroundSize:     'cover',
            backgroundPosition: 'center',
        };
    }

    // Custom uploaded image or stock photo — absolute URL
    if ((bg?.type === 'custom_image' || bg?.type === 'stock') && bg.value) {
        return {
            backgroundImage:    `url('${bg.value}')`,
            backgroundSize:     'cover',
            backgroundPosition: 'center',
        };
    }

    // Auto (default) — first Google photo if available, else gradient
    const first = props.data?.images?.[0];
    if (first) {
        return {
            backgroundImage:    `url('/${first}')`,
            backgroundSize:     'cover',
            backgroundPosition: 'center',
        };
    }
    return {
        background: `linear-gradient(135deg, ${palette.value.primary} 0%, ${palette.value.secondary} 100%)`,
    };
});

// SEO / analytics settings
const googleAnalyticsId = computed(() => props.data?.google_analytics_id || '');
const allowIndexing     = computed(() => props.data?.allow_indexing !== false);
const siteTitle         = computed(() => props.data?.displayName?.text ?? 'Our Website');

onMounted(() => {
    if (googleAnalyticsId.value) {
        const script = document.createElement('script');
        script.async = true;
        script.src = `https://www.googletagmanager.com/gtag/js?id=${googleAnalyticsId.value}`;
        document.head.appendChild(script);

        (window as any).dataLayer = (window as any).dataLayer || [];
        function gtag(...args: any[]) { (window as any).dataLayer.push(args); }
        gtag('js', new Date());
        gtag('config', googleAnalyticsId.value);
    }
});
</script>

<template>
    <Head>
        <title>{{ siteTitle }}</title>
        <meta v-if="!allowIndexing" name="robots" content="noindex,nofollow" />
    </Head>

    <!-- Root — CSS custom properties cascade to all child components -->
    <div class="min-h-screen bg-gray-50 font-sans antialiased" :style="cssVars">

        <!-- ── Hero ───────────────────────────────────────────────────────── -->
        <div class="relative overflow-hidden" style="min-height: 380px">
            <!-- Background photo or gradient -->
            <div class="absolute inset-0" :style="heroStyle" />

            <!-- Dark gradient scrim so text is always readable -->
            <div class="absolute inset-0" style="background: linear-gradient(to top, rgba(0,0,0,0.75) 0%, rgba(0,0,0,0.35) 55%, rgba(0,0,0,0.15) 100%)" />

            <!-- Primary colour accent strip at the very bottom edge -->
            <div class="absolute bottom-0 left-0 right-0 h-1" style="background-color: var(--site-primary)" />

            <!-- Pexels attribution (required when a stock photo is used) -->
            <a
                v-if="headerBg?.type === 'stock' && headerBg.credit"
                :href="headerBg.credit_url || 'https://www.pexels.com'"
                target="_blank"
                rel="noopener noreferrer"
                class="absolute top-2 right-3 z-20 text-[10px] text-white/60 hover:text-white/90 transition-colors"
            >
                Photo by {{ headerBg.credit }} on Pexels
            </a>

            <!-- Hero content -->
            <div class="relative z-10 flex flex-col justify-end" style="min-height: 380px">
                <div class="px-6 pb-8 md:px-14 md:pb-12 max-w-5xl mx-auto w-full">
                    <Header
                        v-if="components.header"
                        :logo="logo"
                        :name="props.data?.displayName?.text"
                        :business-type="props.data?.primaryTypeDisplayName?.text"
                        :address-components="props.data?.addressComponents"
                    />
                </div>
            </div>
        </div>

        <!-- ── Quick action buttons ───────────────────────────────────────── -->
        <div
            v-if="components.quick_actions"
            class="bg-white shadow-sm sticky top-0 z-30"
            style="border-bottom: 2px solid var(--site-primary)"
        >
            <div class="max-w-5xl mx-auto px-6 md:px-14 py-4">
                <QuickActions
                    :phone-number="props.data?.nationalPhoneNumber"
                    :whatsapp-number="props.data?.whatsapp_number"
                    :contact="contactEmail"
                    :quick-links="props.data?.quickLinks"
                    :preview="true"
                />
            </div>
        </div>

        <!-- ── Main content ───────────────────────────────────────────────── -->
        <div class="bg-white">
            <div class="max-w-5xl mx-auto px-6 md:px-14">

                <!-- Description -->
                <Description
                    v-if="components.description && description"
                    :description="description"
                />

                <!-- Gallery -->
                <Gallery
                    v-if="components.gallery"
                    :photos="props.data?.images"
                />

            </div>
        </div>

        <!-- ── Reviews ────────────────────────────────────────────────────── -->
        <div v-if="components.reviews" class="py-14" style="background-color: var(--site-primary-muted)">
            <div class="max-w-5xl mx-auto px-6 md:px-14">
                <Reviews
                    :reviews="props.data?.reviews"
                    :rating="props.data?.rating"
                    :review-count="props.data?.userRatingCount"
                    :hidden-reviews="props.data?.overrides?.hidden_reviews ?? []"
                />
            </div>
        </div>

        <!-- ── Contact / info strip + inline form ─────────────────────────── -->
        <Contact
            v-if="components.contact || (components.contact_form && contactEmail)"
            :formatted-address="props.data?.formattedAddress"
            :phone-number="props.data?.nationalPhoneNumber"
            :opening-hours="props.data?.regularOpeningHours"
            :socials="props.data?.socials"
            :contact="contactEmail"
            :show-form="components.contact_form && !!contactEmail"
            :preview="false"
        />

        <!-- ── Footer ─────────────────────────────────────────────────────── -->
        <footer
            class="text-xs text-center py-5"
            style="background-color: var(--site-primary-dark)"
        >
            <span style="color: rgba(255,255,255,0.5)">Website powered by </span>
            <span class="font-medium text-white">InstantSite</span>
        </footer>

    </div>
</template>
