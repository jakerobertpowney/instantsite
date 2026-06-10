<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { show } from '@/actions/App/Http/Controllers/PreviewController';
import { places as searchPlacesRoute } from '@/routes/search';

// ── Auth detection ─────────────────────────────────────────────────────────────
const page = usePage();
const isLoggedIn = computed(() => !!(page.props.auth as any)?.user);

// ── Search state ──────────────────────────────────────────────────────────────
type SearchPhase = 'idle' | 'loading' | 'results' | 'empty';
const query = ref('');
const searchPhase = ref<SearchPhase>('idle');
const places = ref<any[]>([]);
const searchFocused = ref(false);
const showSearch = ref(false);

const searchPlaces = async () => {
    if (!query.value.trim()) return;
    searchPhase.value = 'loading';
    try {
        const res = await axios.post(searchPlacesRoute.url(), { query: query.value });
        const found = res.data.places ?? [];
        places.value = found;
        searchPhase.value = found.length > 0 ? 'results' : 'empty';
    } catch {
        searchPhase.value = 'empty';
        places.value = [];
    }
};

const resetSearch = () => {
    searchPhase.value = 'idle';
    query.value = '';
    places.value = [];
    showSearch.value = false;
};

const openSearch = () => {
    showSearch.value = true;
    scrollToSearch();
};

// ── Nav scroll ────────────────────────────────────────────────────────────────
const scrolled = ref(false);
const handleScroll = () => {
    scrolled.value = window.scrollY > 60;
};

// ── Mobile menu ───────────────────────────────────────────────────────────────
const mobileMenuOpen = ref(false);
const toggleMobileMenu = () => { mobileMenuOpen.value = !mobileMenuOpen.value; };
const closeMobileMenu = () => { mobileMenuOpen.value = false; };

// ── FAQ accordion ─────────────────────────────────────────────────────────────
const faqOpen = ref<number | null>(0);
const toggleFaq = (i: number) => {
    faqOpen.value = faqOpen.value === i ? null : i;
};

// ── Smooth scroll helper ──────────────────────────────────────────────────────
const scrollTo = (id: string) => {
    closeMobileMenu();
    const el = document.getElementById(id);
    if (el) window.scrollTo({ top: el.offsetTop - 72, behavior: 'smooth' });
};

const scrollToSearch = () => {
    closeMobileMenu();
    const el = document.getElementById('hero-search');
    if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
};

// ── Preview iframe scaling ────────────────────────────────────────────────────
const previewClip = ref<HTMLElement | null>(null);
const previewIframe = ref<HTMLIFrameElement | null>(null);

const updatePreviewScale = () => {
    if (!previewClip.value || !previewIframe.value) return;
    const containerWidth = previewClip.value.offsetWidth;
    // Always render the desktop version of the demo site.
    const designWidth = 900;
    const scale = containerWidth / designWidth;
    // iframeHeight = visible clip height ÷ scale → ensures site content
    // overflows the iframe viewport so the iframe can scroll internally.
    const iframeHeight = Math.round(previewClip.value.offsetHeight / scale);
    previewIframe.value.style.width = `${designWidth}px`;
    previewIframe.value.style.transform = `scale(${scale})`;
    previewIframe.value.style.height = `${iframeHeight}px`;
};

// Inject a style rule that disables all anchor clicks in the demo iframe.
// tel: / wa.me / map links are built by the site component from props, so
// we can't zero them out at the data level — CSS pointer-events is easier.
const disableDemoLinks = () => {
    try {
        const doc = previewIframe.value?.contentDocument;
        if (!doc || doc.getElementById('demo-no-links')) return;
        const style = doc.createElement('style');
        style.id = 'demo-no-links';
        style.textContent = 'a { pointer-events: none !important; cursor: default !important; }';
        (doc.head ?? doc.documentElement).appendChild(style);
    } catch {
        // cross-origin guard — should not fire since demo is same-origin
    }
};

onMounted(() => {
    window.addEventListener('scroll', handleScroll, { passive: true });
    updatePreviewScale();
    window.addEventListener('resize', updatePreviewScale);
    previewIframe.value?.addEventListener('load', () => {
        disableDemoLinks();
        updatePreviewScale();
    });
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
    window.removeEventListener('resize', updatePreviewScale);
});

// ── Static data ───────────────────────────────────────────────────────────────
const freeFeats = [
    'Your own one-page website',
    'Free address: yourname.321sites.com',
    'Photo gallery & reviews',
    'Call, message & WhatsApp buttons',
    'Works on any phone',
    'Edit everything from your dashboard',
];

const proFeats = [
    'Everything in Free',
    'Use your own web address (e.g. thompsondecorating.co.uk)',
    'Remove the "Powered by 321Sites" mark',
    'On-page contact form',
    'Priority support',
];

const faqs = [
    {
        q: 'Do I need my own web address?',
        a: "No. You get a free one — yourcompany.321sites.com — the minute you sign up. If you'd rather use your own (like thompsondecorating.co.uk), you can upgrade to Premium any time.",
    },
    {
        q: 'Do I need a Google Business Profile?',
        a: "No. You can build your site entirely from scratch — just fill in your business name, address, hours, photos, and services yourself. If you do have a Google listing, you can search for it and we'll pre-fill the basics (name, address, phone, hours) for you to review before submitting.",
    },
    {
        q: 'Can I edit my site after it goes live?',
        a: 'Yes — everything is editable from your dashboard at any time. Update your description, swap photos, change your opening hours, add services or links. No technical knowledge needed.',
    },
    {
        q: "I'm not a technical person, can I still build my site?",
        a: "That's exactly who it's built for. No drag-and-drop, no templates to pick, no jargon. Fill in a form, review what it looks like, and publish. If you can write an email, you can build a 321Sites website.",
    },
    { q: 'Am I locked into a contract?', a: "Not at all. Cancel Premium any time with one click — no questions asked. You'll drop back to the Free plan, your site stays up, and your web address stays yours." },
    {
        q: 'Will it show up on Google search?',
        a: "It can. Make sure indexing is turned on in your SEO settings, and fill in your meta title and description. 321Sites also sets up the technical bits — structured data, a sitemap, canonical URLs — so Google can index your site correctly.",
    },
];

const features = [
    { t: 'Set up in minutes', d: 'Fill in your details directly, or search for your Google listing to pre-fill the basics. Either way, you can be live in minutes.' },
    { t: 'Perfect on every phone', d: 'Most of your customers will find you on a phone. Your site works perfectly on the smallest screen.' },
    { t: 'One big "Call" button', d: 'Tap the number and it dials. No fiddly forms. Add WhatsApp too if you like.' },
    {
        t: 'Show your best work',
        d: 'A gallery of your photos, right at the top. Upload them during setup or add more any time from your dashboard.',
    },
    { t: 'Let your reviews do the talking', d: 'Add your best reviews during setup. The social proof that turns visitors into customers.' },
    {
        t: 'Quick links & booking',
        d: 'Add buttons for booking, quotes, menus, or any other link you want customers to tap first.',
    },
    { t: 'Your site, your way', d: 'Toggle sections on or off, update your description, swap your logo. Done in a few taps from your dashboard.' },
    {
        t: 'Your own web address',
        d: 'You get a free web address like yourcompany.321sites.com. Upgrade to Premium and use your own domain whenever you like.',
    },
];
</script>

<template>
    <Head title="Your free business website — 321Sites">
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="" />
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet" />
    </Head>

    <div class="w-full max-w-full overflow-x-hidden bg-brand-surface text-brand-ink min-h-screen antialiased" style="font-family: 'Inter', ui-sans-serif, system-ui, sans-serif;">
        <!-- ══════════════════════════════════════════════
             NAV
        ══════════════════════════════════════════════ -->
        <header :class="[
            'sticky top-0 z-40 transition-all duration-200 ease-out border-b border-transparent',
            scrolled ? 'bg-white/92 border-brand-line-soft backdrop-blur-[8px] -webkit-backdrop-filter:blur(8px)' : 'bg-brand-blue-soft/92 backdrop-blur-[8px] -webkit-backdrop-filter:blur(8px)'
        ]">
            <div class="max-w-[1200px] mx-auto px-6 w-full box-border flex items-center gap-5 py-3.5">
                <!-- Logo -->
                <a href="/" class="flex items-center gap-2.5 no-underline text-brand-ink flex-shrink-0">
                    <AppLogo />
                </a>

                <!-- Desktop nav links -->
                <nav class="hidden lg:flex items-center gap-1 flex-1 ml-6">
                    <button class="px-3 py-2 border-none bg-transparent cursor-pointer font-inherit text-sm font-semibold text-brand-ink-mid rounded-lg transition-colors hover:bg-brand-panel hover:text-brand-ink" @click="scrollTo('how')">How it works</button>
                    <button class="px-3 py-2 border-none bg-transparent cursor-pointer font-inherit text-sm font-semibold text-brand-ink-mid rounded-lg transition-colors hover:bg-brand-panel hover:text-brand-ink" @click="scrollTo('features')">Features</button>
                    <button class="px-3 py-2 border-none bg-transparent cursor-pointer font-inherit text-sm font-semibold text-brand-ink-mid rounded-lg transition-colors hover:bg-brand-panel hover:text-brand-ink" @click="scrollTo('pricing')">Pricing</button>
                    <button class="px-3 py-2 border-none bg-transparent cursor-pointer font-inherit text-sm font-semibold text-brand-ink-mid rounded-lg transition-colors hover:bg-brand-panel hover:text-brand-ink" @click="scrollTo('faq')">FAQ</button>
                </nav>

                <!-- Desktop CTA — auth-aware -->
                <div class="hidden lg:flex items-center gap-2 flex-shrink-0">
                    <template v-if="isLoggedIn">
                        <Link href="/dashboard" class="inline-flex items-center justify-center gap-2 h-12 px-5 rounded-lg font-bold text-sm cursor-pointer border border-brand-blue transition-opacity bg-brand-blue text-white hover:opacity-90">Dashboard</Link>
                    </template>
                    <template v-else>
                        <Link href="/login" class="h-10.5 px-4 text-sm font-semibold text-brand-ink no-underline inline-flex items-center rounded-lg transition-colors hover:bg-brand-panel">Sign in</Link>
                        <button class="inline-flex items-center justify-center gap-2 h-12 px-5 rounded-lg font-bold text-sm cursor-pointer border border-brand-blue transition-opacity bg-brand-blue text-white hover:opacity-90" @click="openSearch">
                            Get started free
                        </button>
                    </template>
                </div>

                <!-- Mobile hamburger -->
                <button :class="[
                    'lg:hidden ml-auto flex flex-col justify-center items-center gap-1.25 w-10 h-10 border-none bg-transparent cursor-pointer p-1 rounded-lg flex-shrink-0 transition-colors hover:bg-brand-panel',
                    mobileMenuOpen && 'bg-brand-panel'
                ]" :aria-expanded="mobileMenuOpen" aria-label="Toggle menu" @click="toggleMobileMenu">
                    <span :class="[
                        'block w-5.5 h-0.5 bg-brand-ink rounded-sm transition-all duration-200',
                        mobileMenuOpen && 'translate-y-1.75 rotate-45'
                    ]"></span>
                    <span :class="[
                        'block w-5.5 h-0.5 bg-brand-ink rounded-sm transition-all duration-200',
                        mobileMenuOpen && 'opacity-0 scale-x-0'
                    ]"></span>
                    <span :class="[
                        'block w-5.5 h-0.5 bg-brand-ink rounded-sm transition-all duration-200',
                        mobileMenuOpen && '-translate-y-1.75 -rotate-45'
                    ]"></span>
                </button>
            </div>

            <!-- Mobile menu panel -->
            <div :class="[
                'lg:hidden flex flex-col bg-white border-t border-transparent px-6 gap-1 overflow-hidden max-h-0 transition-all duration-280 ease-out',
                mobileMenuOpen && 'max-h-[480px] py-4 pb-7 border-t-brand-line-soft'
            ]" aria-hidden="!mobileMenuOpen">
                <nav class="flex flex-col gap-0.5 mb-4">
                    <button class="w-full text-left px-3 py-3.25 border-none bg-transparent font-inherit text-base font-semibold text-brand-ink cursor-pointer rounded-lg transition-colors hover:bg-brand-panel" @click="scrollTo('how')">How it works</button>
                    <button class="w-full text-left px-3 py-3.25 border-none bg-transparent font-inherit text-base font-semibold text-brand-ink cursor-pointer rounded-lg transition-colors hover:bg-brand-panel" @click="scrollTo('features')">Features</button>
                    <button class="w-full text-left px-3 py-3.25 border-none bg-transparent font-inherit text-base font-semibold text-brand-ink cursor-pointer rounded-lg transition-colors hover:bg-brand-panel" @click="scrollTo('pricing')">Pricing</button>
                    <button class="w-full text-left px-3 py-3.25 border-none bg-transparent font-inherit text-base font-semibold text-brand-ink cursor-pointer rounded-lg transition-colors hover:bg-brand-panel" @click="scrollTo('faq')">FAQ</button>
                </nav>
                <div class="flex flex-col gap-2.5 border-t border-brand-line-soft pt-4">
                    <template v-if="isLoggedIn">
                        <Link href="/dashboard" class="inline-flex items-center justify-center gap-2 h-12 px-5 rounded-lg font-bold text-sm cursor-pointer border border-brand-blue transition-opacity bg-brand-blue text-white hover:opacity-90 w-full justify-center" @click="closeMobileMenu">Dashboard</Link>
                    </template>
                    <template v-else>
                        <Link href="/login" class="flex items-center justify-center h-12 rounded-lg border-[1.5px] border-brand-line font-inherit text-sm font-bold text-brand-ink no-underline transition-colors hover:bg-brand-panel" @click="closeMobileMenu">Sign in</Link>
                        <button class="inline-flex items-center justify-center gap-2 h-12 px-5 rounded-lg font-bold text-sm cursor-pointer border border-brand-blue transition-opacity bg-brand-blue text-white hover:opacity-90 w-full justify-center" @click="openSearch">
                            Get started free
                        </button>
                    </template>
                </div>
            </div>
        </header>

        <!-- ══════════════════════════════════════════════
             HERO
        ══════════════════════════════════════════════ -->
        <section class="bg-gradient-to-b from-brand-blue-soft to-white">
            <div class="max-w-[1200px] mx-auto px-6 w-full box-border">
                <div class="grid lg:grid-cols-2 gap-14 items-start py-12 sm:py-16 lg:py-28 lg:pb-24 sm:gap-10">

                    <!-- ── Left: content ── -->
                    <div class="min-w-0 text-center lg:text-left lg:pt-6">
                        <!-- Badge pill -->
                        <div class="inline-flex items-center gap-2 px-3.5 py-2 rounded-full bg-brand-blue-soft text-brand-blue border-[1.5px] border-brand-blue/15 text-sm font-bold mb-5 lg:mb-7">
                            <span class="w-2 h-2 rounded-full bg-brand-blue flex-shrink-0"></span>
                            Free · No credit card · Live in under 5 minutes
                        </div>

                        <!-- Headline -->
                        <h1 class="text-[clamp(32px,4.2vw,54px)] font-black tracking-[-0.05em] leading-[1.05] m-0 text-brand-ink">The free website your business deserves.</h1>
                        <p class="text-[clamp(18px,2vw,22px)] text-brand-ink-mid leading-[1.5] mt-3 lg:mt-6 max-w-[520px]">Build a professional one-page website in under 5 minutes. No technical skill needed. Free forever.</p>

                        <!-- CTA / Search box -->
                        <div id="hero-search" class="mt-5 lg:mt-11 relative w-full">

                            <!-- Default: two-button choice -->
                            <div v-if="!showSearch && searchPhase === 'idle'" class="flex flex-col gap-3">
                                <div class="flex flex-col sm:flex-row gap-3">
                                    <button
                                        class="flex-1 inline-flex items-center justify-center gap-2 h-14 px-6 rounded-lg font-bold text-base cursor-pointer border border-brand-blue transition-opacity bg-brand-blue text-white hover:opacity-90"
                                        @click="openSearch"
                                    >
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                                        Search for my business
                                    </button>
                                    <Link
                                        href="/setup/new"
                                        class="flex-1 inline-flex items-center justify-center gap-2 h-14 px-6 rounded-lg font-bold text-base no-underline border-2 border-brand-line bg-white text-brand-ink transition-colors hover:bg-brand-panel"
                                    >
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                        Start from scratch
                                    </Link>
                                </div>
                                <p class="text-center text-xs text-brand-ink-soft">Search pre-fills your name, address &amp; hours · or enter everything yourself</p>
                            </div>

                            <!-- Search expanded -->
                            <div v-else-if="showSearch && searchPhase === 'idle'" class="flex flex-col gap-3">
                                <div :class="[
                                    'flex items-center gap-1.5 bg-white border-2 border-brand-line rounded-lg px-2 shadow-sm transition-all duration-150',
                                    searchFocused && 'border-brand-blue shadow-[0_0_0_6px_rgba(30,102,245,0.13),0_8px_24px_rgba(0,0,0,0.08)]'
                                ]">
                                    <svg class="pl-2.5 text-brand-ink-soft flex-shrink-0" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <circle cx="11" cy="11" r="8" />
                                        <path d="m21 21-4.35-4.35" />
                                    </svg>
                                    <input
                                        v-model="query"
                                        type="text"
                                        class="flex-1 border-none outline-none bg-transparent font-inherit text-base lg:text-xl font-medium text-brand-ink py-3 lg:py-3.5 px-1.5 min-w-0 placeholder:text-brand-ink-soft"
                                        placeholder="e.g. Thompson Decorating, London"
                                        autofocus
                                        @focus="searchFocused = true"
                                        @blur="searchFocused = false"
                                        @keydown.enter="searchPlaces"
                                    />
                                </div>
                                <button class="w-full inline-flex items-center justify-center gap-2 h-14 px-7 rounded-lg font-bold text-base cursor-pointer border border-brand-blue transition-opacity bg-brand-blue text-white hover:opacity-90" @click="searchPlaces">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                                    Search for my business
                                </button>
                                <p class="text-center text-xs text-brand-ink-soft">
                                    <button class="bg-transparent border-none p-0 cursor-pointer text-brand-blue font-semibold hover:underline font-inherit text-xs" @click="resetSearch">← Back</button>
                                    &nbsp;·&nbsp;
                                    <Link href="/setup/new" class="text-brand-blue font-semibold no-underline hover:underline">Start from scratch instead →</Link>
                                </p>
                            </div>

                            <!-- Loading -->
                            <div v-else-if="searchPhase === 'loading'" class="flex items-center justify-center gap-3 py-6 px-6 bg-white border-[1.5px] border-brand-line rounded-lg text-brand-ink-mid text-base font-medium">
                                <svg class="animate-spin" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true">
                                    <path d="M12 2v4m0 12v4M4.93 4.93l2.83 2.83m8.48 8.48 2.83 2.83M2 12h4m12 0h4M4.93 19.07l2.83-2.83m8.48-8.48 2.83-2.83" />
                                </svg>
                                <span>Searching…</span>
                            </div>

                            <!-- Results -->
                            <div v-else-if="searchPhase === 'results'" class="bg-white border-[1.5px] border-brand-line rounded-lg overflow-hidden text-left shadow-lg">
                                <p class="text-xs font-bold text-brand-ink-soft py-4 px-5 mb-2 m-0">Is one of these your business?</p>
                                <div v-for="place in places" :key="place.id" class="flex items-center gap-3.5 py-3.5 px-5 border-t border-brand-line-soft cursor-pointer transition-colors hover:bg-white">
                                    <div class="w-10 h-10 rounded-[10px] bg-brand-panel flex items-center justify-center text-brand-ink-mid flex-shrink-0">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z" />
                                            <circle cx="12" cy="10" r="3" />
                                        </svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="text-sm font-bold text-brand-ink">{{ place.displayName.text }}</div>
                                        <div class="text-xs text-brand-ink-soft mt-0.5 whitespace-nowrap overflow-hidden text-ellipsis">{{ place.formattedAddress }}</div>
                                    </div>
                                    <span v-if="place.taken" class="flex flex-col items-end gap-1">
                                        <span class="inline-flex items-center justify-center gap-2 h-9 px-3.5 rounded-lg font-bold text-xs cursor-not-allowed border-none bg-brand-panel text-brand-ink-mid pointer-events-none whitespace-nowrap" aria-disabled="true">Already claimed</span>
                                        <Link href="/login" class="text-xs text-brand-blue no-underline whitespace-nowrap hover:underline">Sign in →</Link>
                                    </span>
                                    <Link v-else :href="show.url(place.id)" class="inline-flex items-center justify-center gap-2 h-9 px-3.5 rounded-lg font-bold text-xs cursor-pointer border border-brand-blue transition-opacity bg-brand-blue text-white hover:opacity-90">This is me →</Link>
                                </div>
                                <button class="w-full block py-3 px-5 bg-brand-panel border-none font-inherit text-sm font-semibold text-brand-ink-mid cursor-pointer text-left border-t border-brand-line-soft transition-colors hover:bg-brand-line-soft" @click="resetSearch">← Search again</button>
                            </div>

                            <!-- No results -->
                            <div v-else-if="searchPhase === 'empty'" class="bg-white border-[1.5px] border-brand-line rounded-lg overflow-hidden text-left shadow-lg">
                                <p class="text-xs font-bold text-brand-ink-soft py-4 px-5 mb-2 m-0">No results found</p>
                                <p class="text-xs text-brand-ink-soft px-5 mb-1 m-0">Try searching with your postcode or the full business name.</p>
                                <div class="px-5 py-3 border-t border-brand-line-soft">
                                    <Link href="/setup/new" class="text-xs font-semibold text-brand-blue no-underline hover:underline">Start from scratch instead →</Link>
                                </div>
                                <button class="w-full block py-3 px-5 bg-brand-panel border-none font-inherit text-sm font-semibold text-brand-ink-mid cursor-pointer text-left border-t border-brand-line-soft transition-colors hover:bg-brand-line-soft" @click="resetSearch">← Search again</button>
                            </div>
                        </div>

                        <!-- Trust signals -->
                        <div class="flex flex-col items-center lg:items-start gap-2.5 mt-6">
                            <span class="inline-flex items-center gap-1.5 text-sm text-brand-ink-soft font-medium">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5" /></svg>
                                Free yourcompany.321sites.com web address
                            </span>
                            <span class="inline-flex items-center gap-1.5 text-sm text-brand-ink-soft font-medium">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5" /></svg>
                                No credit card required
                            </span>
                            <span class="inline-flex items-center gap-1.5 text-sm text-brand-ink-soft font-medium">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5" /></svg>
                                Live in under 5 minutes
                            </span>
                        </div>
                    </div>

                    <!-- ── Right: live website preview ── -->
                    <div class="relative hidden lg:block">
                        <div class="rounded-lg overflow-hidden border-[1.5px] border-brand-line shadow-2xl">
                            <!-- Browser chrome bar -->
                            <div class="h-10 bg-gray-100 border-b border-brand-line flex items-center px-3.5 gap-2.5">
                                <div class="flex gap-1.25 flex-shrink-0">
                                    <span class="w-2.5 h-2.5 rounded-full" style="background: #ff5f57"></span>
                                    <span class="w-2.5 h-2.5 rounded-full" style="background: #febc2e"></span>
                                    <span class="w-2.5 h-2.5 rounded-full" style="background: #28c840"></span>
                                </div>
                                <div class="flex-1 h-6 bg-gray-200 rounded-3xl flex items-center px-2.5 gap-1.25 text-xs text-gray-600 overflow-hidden whitespace-nowrap font-mono">
                                    <svg width="9" height="11" viewBox="0 0 9 11" fill="currentColor" aria-hidden="true">
                                        <path d="M4.5 0C3.12 0 2 1.12 2 2.5V3H1a1 1 0 0 0-1 1v6a1 1 0 0 0 1 1h7a1 1 0 0 0 1-1V4a1 1 0 0 0-1-1H7v-.5C7 1.12 5.88 0 4.5 0zm0 1C5.33 1 6 1.67 6 2.5V3H3v-.5C3 1.67 3.67 1 4.5 1zM4.5 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>
                                    </svg>
                                    davespainting.321sites.com
                                </div>
                            </div>
                            <!-- Scaled iframe -->
                            <div ref="previewClip" class="w-full overflow-clip relative h-[520px] bg-white cursor-default">
                                <iframe
                                    ref="previewIframe"
                                    src="/demo"
                                    class="absolute top-0 left-0 w-[900px] h-[900px] border-none origin-top-left"
                                    loading="lazy"
                                    tabindex="-1"
                                    aria-hidden="true"
                                    title="Example business website"
                                    sandbox="allow-scripts allow-same-origin"
                                ></iframe>
                            </div>
                        </div>
                        <p class="text-center text-xs text-brand-ink-soft mt-2.5 font-medium">
                            Example site — yours could look just like this
                        </p>
                    </div>

                </div>
            </div>
        </section>

        <!-- ══════════════════════════════════════════════
             HOW IT WORKS
        ══════════════════════════════════════════════ -->
        <section id="how" class="py-[72px] lg:py-[100px]">
            <div class="max-w-[1200px] mx-auto px-6 w-full box-border">
                <div class="mb-14 sm:mb-10 text-center">
                    <div class="text-xs font-black text-brand-ink-soft uppercase tracking-wider mb-3">How it works</div>
                    <h2 class="text-[clamp(30px,4vw,48px)] font-black text-brand-ink tracking-[-0.025em] leading-[1.08] m-0 max-w-3xl mx-auto">Your website, up in three steps.</h2>
                    <p class="text-lg text-brand-ink-mid leading-[1.55] mt-4 mx-auto max-w-2xl">
                        No drag-and-drop. No templates to pick. Fill in a form, see your site, publish it.
                    </p>
                </div>
                <div class="grid sm:grid-cols-1 md:grid-cols-1 lg:grid-cols-3 gap-6">
                    <div
                        v-for="(step, i) in [
                            {
                                n: '01',
                                t: 'Fill in your details',
                                d: 'Enter your business name, address, hours, and services yourself — or search for your Google listing to pre-fill the basics.',
                                icon: 'search',
                            },
                            {
                                n: '02',
                                t: 'Add your finishing touches',
                                d: 'Upload a logo, write a description, add photos and reviews. Or skip — it works straight away.',
                                icon: 'palette',
                            },
                            {
                                n: '03',
                                t: 'Share your new website',
                                d: 'You get a free web address like yourcompany.321sites.com. Hand it to customers, stick it on your van.',
                                icon: 'globe',
                            },
                        ]"
                        :key="i"
                        class="bg-brand-surface border-[1.5px] border-brand-line rounded-[18px] p-8 flex flex-col gap-3 relative"
                    >
                        <div class="w-13 h-13 rounded-[14px] bg-brand-blue-soft flex items-center justify-center flex-shrink-0">
                            <svg
                                v-if="step.icon === 'search'"
                                width="28"
                                height="28"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="var(--mk-accent)"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                aria-hidden="true"
                            >
                                <circle cx="11" cy="11" r="8" />
                                <path d="m21 21-4.35-4.35" />
                            </svg>
                            <svg
                                v-else-if="step.icon === 'palette'"
                                width="28"
                                height="28"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="var(--mk-accent)"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                aria-hidden="true"
                            >
                                <circle cx="13.5" cy="6.5" r=".5" fill="var(--mk-accent)" />
                                <circle cx="17.5" cy="10.5" r=".5" fill="var(--mk-accent)" />
                                <circle cx="8.5" cy="7.5" r=".5" fill="var(--mk-accent)" />
                                <circle cx="6.5" cy="12.5" r=".5" fill="var(--mk-accent)" />
                                <path
                                    d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 0 1 1.668-1.668h1.996c3.051 0 5.555-2.503 5.555-5.554C21.965 6.012 17.461 2 12 2z"
                                />
                            </svg>
                            <svg
                                v-else
                                width="28"
                                height="28"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="var(--mk-accent)"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                aria-hidden="true"
                            >
                                <circle cx="12" cy="12" r="10" />
                                <path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z" />
                            </svg>
                        </div>
                        <div class="absolute top-6 right-6 text-[60px] font-black text-brand-panel leading-none tracking-[-0.075em]">{{ step.n }}</div>
                        <h3 class="text-[21px] font-black text-brand-ink tracking-[-0.015em] mt-1.5 m-0">{{ step.t }}</h3>
                        <p class="text-sm text-brand-ink-mid leading-[1.55] m-0">{{ step.d }}</p>
                    </div>
                </div>
                <div class="mt-12 flex flex-col sm:flex-row items-center justify-center gap-3">
                    <button class="inline-flex items-center justify-center gap-2 h-14 px-7 rounded-lg font-bold text-base cursor-pointer border border-brand-blue transition-opacity bg-brand-blue text-white hover:opacity-90" @click="openSearch">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                        Search for my business
                    </button>
                    <Link href="/setup/new" class="inline-flex items-center justify-center gap-2 h-14 px-7 rounded-lg font-bold text-base no-underline border-2 border-brand-line bg-white text-brand-ink transition-colors hover:bg-brand-panel">
                        Start from scratch
                    </Link>
                </div>
            </div>
        </section>

        <!-- ══════════════════════════════════════════════
             FEATURES / WHAT YOU GET
        ══════════════════════════════════════════════ -->
        <section id="features" class="py-[72px] lg:py-[100px] bg-brand-ink">
            <div class="max-w-[1200px] mx-auto px-6 w-full box-border">
                <div class="mb-14 sm:mb-10 text-center">
                    <div class="text-xs font-black text-blue-400 uppercase tracking-wider mb-3">What you get</div>
                    <h2 class="text-[clamp(30px,4vw,48px)] font-black text-white tracking-[-0.025em] leading-[1.08] m-0 max-w-3xl mx-auto">Everything a small business site needs. Nothing it doesn't.</h2>
                </div>
                <div class="grid sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-px bg-white/8 border border-white/8 rounded-[18px] overflow-hidden">
                    <div v-for="(feat, i) in features" :key="i" class="bg-brand-ink p-7 flex flex-col gap-2.5">
                        <div class="w-11 h-11 rounded-[12px] bg-brand-blue/18 flex items-center justify-center flex-shrink-0">
                            <svg
                                v-if="i === 0"
                                width="22"
                                height="22"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="var(--mk-accent)"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                aria-hidden="true"
                            >
                                <circle cx="12" cy="12" r="10" />
                                <path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z" />
                            </svg>
                            <svg
                                v-else-if="i === 1"
                                width="22"
                                height="22"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="var(--mk-accent)"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                aria-hidden="true"
                            >
                                <rect x="5" y="2" width="14" height="20" rx="2" />
                                <line x1="12" y1="18" x2="12.01" y2="18" />
                            </svg>
                            <svg
                                v-else-if="i === 2"
                                width="22"
                                height="22"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="var(--mk-accent)"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                aria-hidden="true"
                            >
                                <path
                                    d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.6 1.32h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 8.91a16 16 0 0 0 6 6l.8-.8a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"
                                />
                            </svg>
                            <svg
                                v-else-if="i === 3"
                                width="22"
                                height="22"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="var(--mk-accent)"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                aria-hidden="true"
                            >
                                <rect x="3" y="3" width="18" height="18" rx="2" />
                                <circle cx="8.5" cy="8.5" r="1.5" />
                                <path d="m21 15-5-5L5 21" />
                            </svg>
                            <svg
                                v-else-if="i === 4"
                                width="22"
                                height="22"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="var(--mk-accent)"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                aria-hidden="true"
                            >
                                <path d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z" />
                            </svg>
                            <svg
                                v-else-if="i === 5"
                                width="22"
                                height="22"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="var(--mk-accent)"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                aria-hidden="true"
                            >
                                <circle cx="11" cy="11" r="8" />
                                <path d="m21 21-4.35-4.35" />
                            </svg>
                            <svg
                                v-else-if="i === 6"
                                width="22"
                                height="22"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="var(--mk-accent)"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                aria-hidden="true"
                            >
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                            </svg>
                            <svg
                                v-else
                                width="22"
                                height="22"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="var(--mk-accent)"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                aria-hidden="true"
                            >
                                <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71" />
                                <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71" />
                            </svg>
                        </div>
                        <div class="text-base font-black text-white tracking-[-0.005em]">{{ feat.t }}</div>
                        <div class="text-sm text-white/55 leading-[1.55]">{{ feat.d }}</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ══════════════════════════════════════════════
             PRICING
        ══════════════════════════════════════════════ -->
        <section id="pricing" class="py-[72px] lg:py-[100px]">
            <div class="max-w-[1200px] mx-auto px-6 w-full box-border">
                <div class="mb-14 sm:mb-10 text-center">
                    <div class="text-xs font-black text-brand-ink-soft uppercase tracking-wider mb-3">Pricing</div>
                    <h2 class="text-[clamp(30px,4vw,48px)] font-black text-brand-ink tracking-[-0.025em] leading-[1.08] m-0 max-w-3xl mx-auto">Free forever. £9/month when you're ready.</h2>
                    <p class="text-lg text-brand-ink-mid leading-[1.55] mt-4 mx-auto max-w-2xl">No hidden fees. No surprises. Cancel any time.</p>
                </div>
                <div class="grid sm:grid-cols-1 lg:grid-cols-2 gap-6 max-w-[860px] mx-auto">
                    <!-- Free plan -->
                    <div class="bg-brand-surface border-[1.5px] border-brand-line rounded-[20px] p-9 flex flex-col gap-5 relative">
                        <div class="text-xs font-black text-brand-ink-soft uppercase tracking-wider">Free</div>
                        <div class="flex items-baseline gap-2">
                            <span class="text-[54px] font-black tracking-[-0.05em] leading-none">£0</span>
                            <span class="text-base text-brand-ink-soft">forever</span>
                        </div>
                        <p class="text-sm text-brand-ink-mid leading-[1.5] m-0">Get a website up today, at no cost.</p>
                        <ul class="list-none p-0 m-0 flex flex-col gap-3 flex-1">
                            <li v-for="f in freeFeats" :key="f" class="flex items-start gap-3 text-sm leading-[1.45]">
                                <span class="w-5.5 h-5.5 rounded-full bg-brand-blue-soft flex items-center justify-center flex-shrink-0 mt-0.25">
                                    <svg
                                        width="14"
                                        height="14"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2.5"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        aria-hidden="true"
                                    >
                                        <path d="M20 6 9 17l-5-5" />
                                    </svg>
                                </span>
                                {{ f }}
                            </li>
                        </ul>
                        <button class="w-full inline-flex items-center justify-center gap-2 h-12 px-5 rounded-lg font-bold text-sm cursor-pointer border border-brand-line transition-colors bg-brand-surface text-brand-ink hover:bg-brand-panel" @click="openSearch">
                            Get started free
                        </button>
                    </div>
                    <!-- Premium plan -->
                    <div class="bg-brand-ink text-white border-brand-ink rounded-[20px] p-9 flex flex-col gap-5 relative">
                        <div class="absolute -top-3.5 right-7 py-1.5 px-3.5 rounded-full bg-brand-blue text-white text-xs font-black">Best value</div>
                        <div class="text-xs font-black text-white/65 uppercase tracking-wider">Premium</div>
                        <div class="flex items-baseline gap-2">
                            <span class="text-[54px] font-black tracking-[-0.05em] leading-none">£9</span>
                            <span class="text-base text-white/55">a month</span>
                        </div>
                        <p class="text-sm text-white/70 leading-[1.5] m-0">For businesses who want a custom address and the full feature set.</p>
                        <ul class="list-none p-0 m-0 flex flex-col gap-3 flex-1">
                            <li v-for="f in proFeats" :key="f" class="flex items-start gap-3 text-sm leading-[1.45]">
                                <span class="w-5.5 h-5.5 rounded-full bg-white/15 flex items-center justify-center flex-shrink-0 mt-0.25">
                                    <svg
                                        width="14"
                                        height="14"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="white"
                                        stroke-width="2.5"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        aria-hidden="true"
                                    >
                                        <path d="M20 6 9 17l-5-5" />
                                    </svg>
                                </span>
                                {{ f }}
                            </li>
                        </ul>
                        <button class="w-full inline-flex items-center justify-center gap-2 h-12 px-5 rounded-lg font-bold text-sm cursor-pointer border-none transition-opacity bg-white text-brand-ink hover:opacity-90">Start Premium — £9/mo</button>
                    </div>
                </div>
                <p class="text-center text-sm text-brand-ink-soft mt-6">Prices in GBP. VAT included where applicable. No hidden fees. No price hikes at renewal. Cancel any time.</p>
            </div>
        </section>

        <!-- ══════════════════════════════════════════════
             FAQ
        ══════════════════════════════════════════════ -->
        <section id="faq" class="py-10 sm:py-8">
            <div class="max-w-[860px] mx-auto px-6 w-full box-border">
                <div class="mb-14 sm:mb-10 text-center">
                    <div class="text-xs font-black text-brand-ink-soft uppercase tracking-wider mb-3">FAQ</div>
                    <h2 class="text-[clamp(30px,4vw,48px)] font-black text-brand-ink tracking-[-0.025em] leading-[1.08] m-0">Your questions, answered.</h2>
                </div>
                <div class="flex flex-col gap-2.5 mt-12 sm:mt-8">
                    <div v-for="(item, i) in faqs" :key="i" class="bg-brand-surface border-[1.5px] border-brand-line rounded-[14px] overflow-hidden">
                        <button class="w-full py-5 px-6 bg-transparent border-none cursor-pointer flex items-center justify-between gap-4 font-inherit text-left" @click="toggleFaq(i)">
                            <span class="text-base font-bold text-brand-ink tracking-[-0.005em]">{{ item.q }}</span>
                            <span :class="[
                                'w-9 h-9 rounded-full bg-brand-panel text-brand-ink flex items-center justify-center flex-shrink-0 transition-transform duration-200',
                                faqOpen === i && 'rotate-180'
                            ]">
                                <svg
                                    width="18"
                                    height="18"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    aria-hidden="true"
                                >
                                    <path d="m6 9 6 6 6-6" />
                                </svg>
                            </span>
                        </button>
                        <div v-if="faqOpen === i" class="px-6 pb-5 text-sm text-brand-ink-mid leading-[1.65] m-0 max-w-2xl" v-html="item.a"></div>
                    </div>
                </div>
                <p class="text-center mt-9 text-sm text-brand-ink-soft">
                    Still got questions? <a href="mailto:support@321sites.com" class="text-brand-ink font-bold underline-offset-1 hover:underline">Drop us a line</a> — a real person will reply
                    within a day.
                </p>
            </div>
        </section>

        <!-- ══════════════════════════════════════════════
             CTA BAND
        ══════════════════════════════════════════════ -->
        <section class="py-10 sm:py-8">
            <div class="max-w-[1200px] mx-auto px-6 w-full box-border">
                <div class="bg-brand-ink text-white rounded-[24px] py-[clamp(36px,5vw,72px)] px-[clamp(28px,5vw,64px)] grid lg:grid-cols-[1.4fr_1fr] gap-12 items-center sm:grid-cols-1">
                    <div>
                        <h2 class="text-[clamp(26px,3.5vw,42px)] font-black tracking-[-0.02em] leading-[1.08] m-0">A website for your business in the time it takes to make a brew.</h2>
                        <p class="text-base text-white/72 leading-[1.5] mt-4 max-w-xs">Free to start, no credit card, no pushy sales calls. Give it a go — there's no commitment.</p>
                    </div>
                    <div class="flex flex-col gap-4">
                        <div class="flex flex-col gap-3">
                            <button class="w-full inline-flex items-center justify-center gap-2 h-14 px-7 rounded-lg font-bold text-base cursor-pointer border border-brand-blue transition-opacity bg-brand-blue text-white hover:opacity-90" @click="openSearch">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                                Search for my business
                            </button>
                            <Link href="/setup/new" class="w-full inline-flex items-center justify-center gap-2 h-14 px-7 rounded-lg font-bold text-base no-underline border-2 border-white/25 bg-transparent text-white transition-colors hover:bg-white/10">
                                Start from scratch
                            </Link>
                        </div>
                        <div class="flex items-center justify-center gap-5 text-sm text-white/55">
                            <span class="inline-flex items-center gap-1.5">
                                <svg
                                    width="16"
                                    height="16"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2.5"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    aria-hidden="true"
                                >
                                    <path d="M20 6 9 17l-5-5" />
                                </svg>
                                Free forever
                            </span>
                            <span class="inline-flex items-center gap-1.5">
                                <svg
                                    width="16"
                                    height="16"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2.5"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    aria-hidden="true"
                                >
                                    <path d="M20 6 9 17l-5-5" />
                                </svg>
                                No card needed
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ══════════════════════════════════════════════
             FOOTER
        ══════════════════════════════════════════════ -->
        <footer class="bg-brand-ink text-white/70 mt-0">
            <div class="max-w-[1200px] mx-auto px-6 w-full box-border">
                <div class="grid lg:grid-cols-[1.4fr_1fr] gap-12 py-12 lg:py-28 border-b border-white/10 sm:grid-cols-1">
                    <div class="max-w-sm">
                        <div class="flex items-center gap-2.5 mb-4" style="color: #ffffff">
                            <AppLogo />
                        </div>
                        <p class="text-sm leading-[1.6] m-0">A website for your business in the time it takes to make a brew.</p>
                    </div>
                    <div class="grid grid-cols-3 gap-8 sm:grid-cols-3">
                        <div class="flex flex-col gap-2.5">
                            <div class="text-xs font-black text-white uppercase tracking-wider mb-1">Product</div>
                            <a class="text-sm text-white/65 no-underline cursor-pointer transition-colors hover:text-white" @click.prevent="scrollTo('how')">How it works</a>
                            <a class="text-sm text-white/65 no-underline cursor-pointer transition-colors hover:text-white" @click.prevent="scrollTo('features')">Features</a>
                            <a class="text-sm text-white/65 no-underline cursor-pointer transition-colors hover:text-white" @click.prevent="scrollTo('pricing')">Pricing</a>
                            <a class="text-sm text-white/65 no-underline cursor-pointer transition-colors hover:text-white" @click.prevent="scrollTo('faq')">FAQ</a>
                        </div>
                        <div class="flex flex-col gap-2.5">
                            <div class="text-xs font-black text-white uppercase tracking-wider mb-1">Support</div>
                            <Link href="/help" class="text-sm text-white/65 no-underline cursor-pointer transition-colors hover:text-white">Help centre</Link>
                            <a href="mailto:support@321sites.com" class="text-sm text-white/65 no-underline cursor-pointer transition-colors hover:text-white">Contact us</a>
                        </div>
                        <div class="flex flex-col gap-2.5">
                            <div class="text-xs font-black text-white uppercase tracking-wider mb-1">Legal</div>
                            <Link href="/terms" class="text-sm text-white/65 no-underline cursor-pointer transition-colors hover:text-white">Terms</Link>
                            <Link href="/privacy" class="text-sm text-white/65 no-underline cursor-pointer transition-colors hover:text-white">Privacy</Link>
                        </div>
                    </div>
                </div>
                <div class="max-w-[1200px] mx-auto px-6 w-full box-border py-7 flex justify-between items-center text-sm flex-wrap gap-3 sm:text-center">
                    <div>© 2026 321Sites Ltd. Made in the UK.</div>
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full" style="background: #5bd46a"></span>
                        All systems go
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>

