<script setup lang="ts">
import Gallery from '@/components/site/Gallery.vue';
import Contact from '@/components/site/Contact.vue';
import QuickActions from '@/components/site/QuickActions.vue';
import Description from '@/components/site/Description.vue';
import Header from '@/components/site/Header.vue';
import Reviews from '@/components/site/Reviews.vue';
import Services from '@/components/site/Services.vue';
import { Head } from '@inertiajs/vue3';
import { computed, onMounted } from 'vue';
import { getEffectivePalette, paletteToCssVars } from '@/lib/palette';

const props = defineProps({
    data: Object,
    isPremium: Boolean,
    metaTitle: String,
    metaDescription: String,
    siteUrl: String,
    canonicalUrl: String,
    sitemapUrl: String,
    isOwner: Boolean,
    dashboardUrl: String,
})

// Resolve component visibility flags — default to true when absent (legacy sites).
// contact_form is additionally gated behind isPremium so sites that had it enabled
// before premium enforcement was introduced are not affected retroactively.
const components = computed(() => {
    const flags = props.data?.components ?? {};
    return {
        header:        flags.header?.enabled        !== false,
        description:   flags.description?.enabled   !== false,
        gallery:       flags.gallery?.enabled       !== false,
        quick_actions: flags.quick_actions?.enabled !== false,
        reviews:       flags.reviews?.enabled       !== false,
        contact:       flags.contact?.enabled       !== false,
        contact_form:  props.isPremium === true && flags.contact_form?.enabled !== false,
        services:      flags.services?.enabled      !== false,
    };
});

// Direct flat fields — no more overrides nesting
const description   = computed(() => props.data?.description);
const logo          = computed(() => props.data?.logo_path);
const contactEmail  = computed(() => props.data?.contact_email);

// Services
const services         = computed(() => props.data?.services ?? []);
const servicesHeading  = computed(() => props.data?.services_heading || 'Our Services');
const servicesCtaLabel = computed(() => props.data?.services_cta_label || '');
const servicesCtaLink  = computed(() => props.data?.services_cta_link || '');
const hasServices      = computed(() => services.value.length > 0);

// Schema.org JSON-LD for services (injected into <head> via Inertia Head)
const schemaOrgJson = computed(() => {
    const name    = props.data?.business_name ?? '';
    const address = props.data?.formatted_address ?? '';
    const phone   = props.data?.phone ?? '';
    const payload: Record<string, unknown> = {
        '@context':    'https://schema.org',
        '@type':       'LocalBusiness',
        'name':         name,
        'address':      address,
        'telephone':    phone || undefined,
    };

    if (hasServices.value) {
        const items = services.value.map((s: any) => ({
            '@type':        'Offer',
            'name':         s.name,
            'description':  s.description ?? undefined,
            'price':        s.price ?? undefined,
            'priceCurrency': 'GBP',
        }));

        payload.hasOfferCatalog = {
            '@type': 'OfferCatalog',
            'name':  servicesHeading.value,
            'itemListElement': items,
        };
    }

    if (props.data?.rating && props.data?.review_count) {
        payload.aggregateRating = {
            '@type': 'AggregateRating',
            'ratingValue': props.data.rating,
            'reviewCount': props.data.review_count,
        };
    }

    return JSON.stringify(payload);
});

// Colour palette — user-chosen custom colours (stored in settings) take priority
const palette    = computed(() => getEffectivePalette(props.data));
const cssVars    = computed(() => paletteToCssVars(palette.value));

// Hero background — respects the user-chosen override from settings, falls back to auto behaviour
const headerBg = computed(() => props.data?.settings?.header_bg ?? null);

const heroStyle = computed<Record<string, string>>(() => {
    const bg = headerBg.value;

    if (bg?.type === 'none') {
        return {
            background: `linear-gradient(135deg, ${palette.value.primary} 0%, ${palette.value.secondary} 100%)`,
        };
    }

    if (bg?.type === 'color' && bg.value) {
        return { backgroundColor: bg.value };
    }

    if (bg?.type === 'google_image' && bg.value) {
        return {
            backgroundImage:    `url('/${bg.value}')`,
            backgroundSize:     'cover',
            backgroundPosition: 'center',
        };
    }

    if ((bg?.type === 'custom_image' || bg?.type === 'stock') && bg.value) {
        return {
            backgroundImage:    `url('${bg.value}')`,
            backgroundSize:     'cover',
            backgroundPosition: 'center',
        };
    }

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
const googleAnalyticsId = computed(() => props.data?.settings?.google_analytics_id || '');
const allowIndexing     = computed(() => (props.data?.settings?.allow_indexing ?? true) !== false);
const siteTitle         = computed(() => props.metaTitle || props.data?.business_name || 'Our Website');
const metaDescription   = computed(() => props.metaDescription || description.value || '');
const canonicalUrl      = computed(() => props.canonicalUrl || props.siteUrl || '');

const absoluteAssetUrl = (path?: string | null) => {
    if (!path) return '';
    if (/^https?:\/\//i.test(path)) return path;

    const base = canonicalUrl.value || props.siteUrl || '';
    if (!base) return '';

    return `${base}${path.startsWith('/') ? path : `/${path}`}`;
};

const shareImageUrl = computed(() => {
    const firstImage = props.data?.images?.[0];
    if (firstImage) {
        return absoluteAssetUrl(firstImage);
    }

    const backgroundValue = props.data?.settings?.header_bg?.value;
    if (backgroundValue) {
        return absoluteAssetUrl(backgroundValue);
    }

    return absoluteAssetUrl(props.data?.logo_path);
});

const twitterCard = computed(() => shareImageUrl.value ? 'summary_large_image' : 'summary');


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
        <meta v-if="metaDescription" name="description" :content="metaDescription" />
        <meta v-if="!allowIndexing" name="robots" content="noindex,nofollow" />
        <!-- Favicon is injected server-side via app.blade.php from $page.props.data.settings.favicon_path -->
        <link v-if="canonicalUrl" rel="canonical" :href="canonicalUrl" />
        <link v-if="sitemapUrl" rel="sitemap" type="application/xml" :href="sitemapUrl" />
        <meta property="og:type" content="business.business" />
        <meta property="og:title" :content="siteTitle" />
        <meta v-if="metaDescription" property="og:description" :content="metaDescription" />
        <meta v-if="canonicalUrl" property="og:url" :content="canonicalUrl" />
        <meta v-if="shareImageUrl" property="og:image" :content="shareImageUrl" />
        <meta name="twitter:card" :content="twitterCard" />
        <meta name="twitter:title" :content="siteTitle" />
        <meta v-if="metaDescription" name="twitter:description" :content="metaDescription" />
        <meta v-if="shareImageUrl" name="twitter:image" :content="shareImageUrl" />
        <!-- Schema.org structured data — services / offer catalogue.
             Use <component :is="'script'"> to avoid the Vue template compiler
             treating a literal <script> tag as a side-effect node. -->
        <!-- eslint-disable-next-line vue/no-v-text-v-html-on-component -->
        <component :is="'script'" v-if="schemaOrgJson" type="application/ld+json" v-html="schemaOrgJson" />
    </Head>

    <!-- Admin bar — only visible to the logged-in site owner -->
    <div
        v-if="isOwner"
        class="is-admin-bar"
        style="position:fixed;top:0;left:0;right:0;z-index:9999;display:flex;align-items:center;justify-content:space-between;gap:12px;padding:0 16px;height:44px;background:#ffffff;color:#111418;font-family:'Inter','Instrument Sans',ui-sans-serif,system-ui,sans-serif;font-size:13px;font-weight:500;border-bottom:1.5px solid #D9D6CE;box-shadow:0 1px 4px rgba(0,0,0,0.06);"
    >
        <span style="display:flex;align-items:center;gap:8px;color:#6B727D;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            You're viewing your site
        </span>
        <a
            :href="dashboardUrl"
            style="display:inline-flex;align-items:center;gap:6px;padding:0 14px;height:28px;border-radius:6px;background:#1E66F5;color:#fff;text-decoration:none;font-size:13px;font-weight:600;transition:background 0.15s;"
            onmouseover="this.style.background='#1554d4'"
            onmouseout="this.style.background='#1E66F5'"
        >
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
            Edit site
        </a>
    </div>

    <!-- Root — CSS custom properties cascade to all child components -->
    <div class="min-h-screen bg-gray-50 font-sans antialiased" :style="[cssVars, isOwner ? { paddingTop: '44px' } : {}]">

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
                        :name="props.data?.business_name"
                        :business-type="props.data?.business_type"
                        :city="props.data?.city"
                        :region="props.data?.region"
                    />
                </div>
            </div>
        </div>

        <!-- ── Quick action buttons ───────────────────────────────────────── -->
        <div
            v-if="components.quick_actions"
            class="bg-white shadow-sm sticky z-30"
            :style="{ top: isOwner ? '44px' : '0', borderBottom: '2px solid var(--site-primary)' }"
        >
            <div class="max-w-5xl mx-auto px-6 md:px-14 py-4">
                <QuickActions
                    :phone-number="props.data?.phone"
                    :whatsapp-number="props.data?.whatsapp_number"
                    :contact="contactEmail"
                    :show-form="components.contact_form && !!contactEmail"
                    :quick-links="props.data?.quick_links"
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

                <!-- Services / Products -->
                <Services
                    v-if="components.services && hasServices"
                    :services="services"
                    :heading="servicesHeading"
                    :cta-label="servicesCtaLabel"
                    :cta-link="servicesCtaLink"
                />

            </div>
        </div>

        <!-- ── Reviews ────────────────────────────────────────────────────── -->
        <div v-if="components.reviews" class="py-14" style="background-color: var(--site-primary-muted)">
            <div class="max-w-5xl mx-auto px-6 md:px-14">
                <Reviews
                    :reviews="props.data?.reviews ?? []"
                    :rating="props.data?.rating ?? 0"
                    :review-count="props.data?.review_count ?? 0"
                    :google-places-id="props.data?.google_places_id ?? ''"
                />
            </div>
        </div>

        <!-- ── Contact / info strip + inline form ─────────────────────────── -->
        <Contact
            v-if="components.contact || (components.contact_form && contactEmail)"
            :formatted-address="props.data?.formatted_address"
            :phone-number="props.data?.phone"
            :opening-hours="props.data?.opening_hours"
            :socials="props.data?.socials"
            :contact="contactEmail"
            :show-form="components.contact_form && !!contactEmail"
            :business-type="props.data?.business_type"
            :preview="false"
        />

        <!-- ── Footer ─────────────────────────────────────────────────────── -->
        <footer
            class="text-xs text-center py-5"
            style="background-color: var(--site-primary-dark)"
        >
            <span style="color: rgba(255,255,255,0.5)">Website powered by </span>
            <a
                href="https://321sites.com"
                target="_blank"
                rel="noopener noreferrer"
                class="font-medium text-white underline underline-offset-4"
            >
                321Sites
            </a>
        </footer>

    </div>
</template>
