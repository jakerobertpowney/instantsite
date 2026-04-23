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
    'Manual Sync with Google Business Listing',
    'Photo gallery & reviews',
    'Call, message & WhatsApp buttons',
    'Works on any phone',
];

const proFeats = [
    'Everything in Free',
    'Use your own web address (e.g. thompsondecorating.co.uk)',
    'Remove the "Powered by 321Sites" mark',
    'Auto Sync with Google Business Listing',
    'On-page contact form',
    'Priority support',
];

const faqs = [
    {
        q: 'Do I need my own web address?',
        a: "No. You get a free one — yourcompany.321sites.com — the minute you sign up. If you'd rather use your own (like thompsondecorating.co.uk), you can upgrade to Premium any time.",
    },
    {
        q: "What if I don't have a Google Business Profile?",
        a: "You'll need a Google Business Profile before we can build your site — but setting one up is free and takes about 10 minutes. <a href=\"https://business.google.com/en-all/business-profile/\" target=\"_blank\" rel=\"noopener noreferrer\" class=\"mk-faq__link\">Set up your Google Business Profile →</a> Once you're listed, come back here and we'll build your site.",
    },
    {
        q: 'Can I edit things like my description and photos?',
        a: 'Your business details (name, address, hours, photos, reviews) are pulled from Google, so you change them once there and they update everywhere. Everything else — the description, colours, buttons, which sections show — you edit inside 321Sites with plain toggles and text boxes.',
    },
    {
        q: "I'm not a technical person, can I still build my site?",
        a: "That's exactly who it's built for — and there's no real 'building' involved on your end. We pull everything from your Google listing automatically. No drag-and-drop, no templates to pick, no jargon. If you can type your business name, you can use 321Sites.",
    },
    { q: 'Am I locked into a contract?', a: 'Not at all. Cancel Premium any time with one click — no questions asked. You\'ll drop back to the Free plan, your site stays up, and your web address stays yours.' },
    {
        q: 'Will it show up on Google search?',
        a: 'Your 321Sites website and your Google Business Profile work together — the same name, address, and info in both places is one of the most effective things you can do for local search. We also set up the technical bits (meta title, description, structured data) so Google can index your site correctly.',
    },
];

const features = [
    { t: 'Your info, auto-filled', d: 'Name, address, hours, phone, photos, reviews — all pulled from Google. Change it there, it updates here.' },
    { t: 'Perfect on every phone', d: 'Most of your customers will find you on a phone. Your site works perfectly on the smallest screen.' },
    { t: 'One big "Call" button', d: 'Tap the number and it dials. No fiddly forms. Add WhatsApp too if you like.' },
    {
        t: 'Show your best work',
        d: 'A gallery of your photos, right at the top. Add or change them in Google Business Profile and they appear here.',
    },
    { t: 'Let your reviews do the talking', d: 'Your Google star rating and best reviews shown on your site automatically. The social proof that turns visitors into customers.' },
    {
        t: 'Linked to your Google listing',
        d: 'Your site and your Google Business Profile work together — consistent info across both is one of the best things you can do for local search.',
    },
    { t: 'Your site, your way', d: 'Toggle sections on or off, update your description, swap your logo. Done in a few taps.' },
    {
        t: 'Your own web address',
        d: 'You get a free web address like yourcompany.321sites.com. Upgrade to premium and use your own domain whenever you like.',
    },
];
</script>

<template>
    <Head title="Your business website, built from Google — 321Sites">
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="" />
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet" />
    </Head>

    <div class="mk-page">
        <!-- ══════════════════════════════════════════════
             NAV
        ══════════════════════════════════════════════ -->
        <header class="mk-nav" :class="{ 'mk-nav--scrolled': scrolled }">
            <div class="mk-container mk-nav__inner">
                <!-- Logo -->
                <a href="/" class="mk-nav__logo">
                    <AppLogo />
                </a>

                <!-- Desktop nav links -->
                <nav class="mk-nav__links">
                    <button class="mk-nav__link" @click="scrollTo('how')">How it works</button>
                    <button class="mk-nav__link" @click="scrollTo('features')">Features</button>
                    <button class="mk-nav__link" @click="scrollTo('pricing')">Pricing</button>
                    <button class="mk-nav__link" @click="scrollTo('faq')">FAQ</button>
                </nav>

                <!-- Desktop CTA — auth-aware -->
                <div class="mk-nav__cta mk-nav__cta--desktop">
                    <template v-if="isLoggedIn">
                        <Link href="/dashboard" class="mk-btn mk-btn--primary">Dashboard</Link>
                    </template>
                    <template v-else>
                        <Link href="/login" class="mk-nav__signin">Sign in</Link>
                        <button class="mk-btn mk-btn--primary" @click="scrollToSearch">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                            Find my business
                        </button>
                    </template>
                </div>

                <!-- Mobile hamburger -->
                <button class="mk-nav__hamburger" :class="{ 'mk-nav__hamburger--open': mobileMenuOpen }" :aria-expanded="mobileMenuOpen" aria-label="Toggle menu" @click="toggleMobileMenu">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>

            <!-- Mobile menu panel -->
            <div class="mk-mobile-menu" :class="{ 'mk-mobile-menu--open': mobileMenuOpen }" aria-hidden="!mobileMenuOpen">
                <nav class="mk-mobile-menu__links">
                    <button class="mk-mobile-menu__link" @click="scrollTo('how')">How it works</button>
                    <button class="mk-mobile-menu__link" @click="scrollTo('features')">Features</button>
                    <button class="mk-mobile-menu__link" @click="scrollTo('pricing')">Pricing</button>
                    <button class="mk-mobile-menu__link" @click="scrollTo('faq')">FAQ</button>
                </nav>
                <div class="mk-mobile-menu__actions">
                    <template v-if="isLoggedIn">
                        <Link href="/dashboard" class="mk-btn mk-btn--primary mk-btn--full" @click="closeMobileMenu">Dashboard</Link>
                    </template>
                    <template v-else>
                        <Link href="/login" class="mk-mobile-menu__signin" @click="closeMobileMenu">Sign in</Link>
                        <button class="mk-btn mk-btn--primary mk-btn--full" @click="scrollToSearch">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                            Find my business
                        </button>
                    </template>
                </div>
            </div>
        </header>

        <!-- ══════════════════════════════════════════════
             HERO
        ══════════════════════════════════════════════ -->
        <section class="mk-hero">
            <div class="mk-container">
                <div class="mk-hero__split">

                    <!-- ── Left: content ── -->
                    <div class="mk-hero__left">
                        <!-- Badge pill -->
                        <div class="mk-pill">
                            <span class="mk-pill__dot"></span>
                            Free · No credit card · 2 minute setup
                        </div>

                        <!-- Headline -->
                        <h1 class="mk-hero__h1">Your Google listing.<br>Now a free website.</h1>
                        <p class="mk-hero__sub">Turn your Google Business listing into a website in under 2 minutes. Free forever.</p>

                        <!-- Search box -->
                        <div id="hero-search" class="mk-search-wrap">
                            <!-- Idle: search form -->
                            <div v-if="searchPhase === 'idle'" class="mk-search-stack">
                                <div class="mk-search" :class="{ 'mk-search--focused': searchFocused }">
                                    <svg
                                        class="mk-search__icon"
                                        width="26"
                                        height="26"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        aria-hidden="true"
                                    >
                                        <circle cx="11" cy="11" r="8" />
                                        <path d="m21 21-4.35-4.35" />
                                    </svg>
                                    <input
                                        v-model="query"
                                        type="text"
                                        class="mk-search__input"
                                        placeholder="Your business name, e.g. Thompson Decorating"
                                        autofocus
                                        @focus="searchFocused = true"
                                        @blur="searchFocused = false"
                                        @keydown.enter="searchPlaces"
                                    />
                                </div>
                                <button class="mk-btn mk-btn--primary mk-btn--lg mk-search-stack__btn" @click="searchPlaces">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                                    Find my business
                                </button>
                            </div>

                            <!-- Loading -->
                            <div v-else-if="searchPhase === 'loading'" class="mk-search-state">
                                <svg
                                    class="mk-spin"
                                    width="22"
                                    height="22"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="var(--mk-accent)"
                                    stroke-width="2.5"
                                    stroke-linecap="round"
                                    aria-hidden="true"
                                >
                                    <path
                                        d="M12 2v4m0 12v4M4.93 4.93l2.83 2.83m8.48 8.48 2.83 2.83M2 12h4m12 0h4M4.93 19.07l2.83-2.83m8.48-8.48 2.83-2.83"
                                    />
                                </svg>
                                <span>Searching Google Business…</span>
                            </div>

                            <!-- Results -->
                            <div v-else-if="searchPhase === 'results'" class="mk-results">
                                <p class="mk-results__label">Is one of these your business?</p>
                                <div v-for="place in places" :key="place.id" class="mk-result-row">
                                    <div class="mk-result-row__icon">
                                        <svg
                                            width="18"
                                            height="18"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                        >
                                            <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z" />
                                            <circle cx="12" cy="10" r="3" />
                                        </svg>
                                    </div>
                                    <div class="mk-result-row__info">
                                        <div class="mk-result-row__name">{{ place.displayName.text }}</div>
                                        <div class="mk-result-row__addr">{{ place.formattedAddress }}</div>
                                    </div>
                                    <span v-if="place.taken" class="mk-result-row__taken">
                                        <span class="mk-btn mk-btn--sm mk-btn--taken" aria-disabled="true">Already claimed</span>
                                        <Link href="/login" class="mk-result-row__taken-link">Sign in →</Link>
                                    </span>
                                    <Link v-else :href="show.url(place.id)" class="mk-btn mk-btn--primary mk-btn--sm"> This is me → </Link>
                                </div>
                                <button class="mk-reset-btn" @click="resetSearch">← Search again</button>
                            </div>

                            <!-- No results -->
                            <div v-else-if="searchPhase === 'empty'" class="mk-results mk-results--empty">
                                <p class="mk-results__label">No results found</p>
                                <p class="mk-results__sub">Try searching with your postcode or the full business name.</p>
                                <button class="mk-reset-btn" @click="resetSearch">← Search again</button>
                            </div>
                        </div>

                        <!-- Trust signals -->
                        <div class="mk-trust">
                            <span class="mk-trust__item">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--mk-accent)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5" /></svg>
                                Free yourcompany.321sites.com web address
                            </span>
                            <span class="mk-trust__item">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--mk-accent)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5" /></svg>
                                No credit card required
                            </span>
                            <span class="mk-trust__item">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--mk-accent)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5" /></svg>
                                Live in under 2 minutes
                            </span>
                        </div>
                    </div>

                    <!-- ── Right: live website preview ── -->
                    <div class="mk-hero__right">
                        <div class="mk-preview-browser">
                            <!-- Browser chrome bar -->
                            <div class="mk-preview-browser__bar">
                                <div class="mk-preview-browser__dots">
                                    <span class="mk-preview-browser__dot"></span>
                                    <span class="mk-preview-browser__dot"></span>
                                    <span class="mk-preview-browser__dot"></span>
                                </div>
                                <div class="mk-preview-browser__urlbar">
                                    <svg width="9" height="11" viewBox="0 0 9 11" fill="currentColor" aria-hidden="true">
                                        <path d="M4.5 0C3.12 0 2 1.12 2 2.5V3H1a1 1 0 0 0-1 1v6a1 1 0 0 0 1 1h7a1 1 0 0 0 1-1V4a1 1 0 0 0-1-1H7v-.5C7 1.12 5.88 0 4.5 0zm0 1C5.33 1 6 1.67 6 2.5V3H3v-.5C3 1.67 3.67 1 4.5 1zM4.5 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>
                                    </svg>
                                    davespainting.321sites.com
                                </div>
                            </div>
                            <!-- Scaled iframe -->
                            <div ref="previewClip" class="mk-preview-iframe-clip">
                                <iframe
                                    ref="previewIframe"
                                    src="/demo"
                                    class="mk-preview-iframe"
                                    loading="lazy"
                                    tabindex="-1"
                                    aria-hidden="true"
                                    title="Example business website"
                                    sandbox="allow-scripts allow-same-origin"
                                ></iframe>
                            </div>
                        </div>
                        <p class="mk-preview-caption">
                            Example site — built from a Google Business listing
                        </p>
                    </div>

                </div>
            </div>
        </section>

        <!-- ══════════════════════════════════════════════
             HOW IT WORKS
        ══════════════════════════════════════════════ -->
        <section id="how" class="mk-section">
            <div class="mk-container">
                <div class="mk-section-head mk-section-head--center">
                    <div class="mk-eyebrow">How it works</div>
                    <h2 class="mk-section-head__h2">A website from your Google Business listing in three steps.</h2>
                    <p class="mk-section-head__sub">
                        No drag-and-drop. No templates to pick. We build the whole thing from what Google already knows about your business.
                    </p>
                </div>
                <div class="mk-steps-grid">
                    <div
                        v-for="(step, i) in [
                            {
                                n: '01',
                                t: 'Search for your business',
                                d: 'Type your business name. We\'ll find you on Google in seconds.',
                                icon: 'search',
                            },
                            {
                                n: '02',
                                t: 'Add your finishing touches',
                                d: 'Upload a logo, write a description, change colour schemes. Or skip — it works out of the box.',
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
                        class="mk-step-card"
                    >
                        <div class="mk-step-card__icon">
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
                        <div class="mk-step-card__num">{{ step.n }}</div>
                        <h3 class="mk-step-card__title">{{ step.t }}</h3>
                        <p class="mk-step-card__desc">{{ step.d }}</p>
                    </div>
                </div>
                <div class="mk-steps-cta">
                    <button class="mk-btn mk-btn--primary mk-btn--lg" @click="scrollToSearch">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                        Find my business
                    </button>
                    <div class="mk-steps-cta__note">No sign-up needed to start. Really.</div>
                </div>
            </div>
        </section>

        <!-- ══════════════════════════════════════════════
             FEATURES / WHAT YOU GET
        ══════════════════════════════════════════════ -->
        <section id="features" class="mk-section mk-section--panel">
            <div class="mk-container">
                <div class="mk-section-head mk-section-head--center">
                    <div class="mk-eyebrow">What you get</div>
                    <h2 class="mk-section-head__h2">Everything a small business site needs. Nothing it doesn't.</h2>
                </div>
                <div class="mk-features-grid">
                    <div v-for="(feat, i) in features" :key="i" class="mk-feature-cell">
                        <div class="mk-feature-cell__icon">
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
                        <div class="mk-feature-cell__title">{{ feat.t }}</div>
                        <div class="mk-feature-cell__desc">{{ feat.d }}</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ══════════════════════════════════════════════
             PRICING
        ══════════════════════════════════════════════ -->
        <section id="pricing" class="mk-section">
            <div class="mk-container">
                <div class="mk-section-head mk-section-head--center">
                    <div class="mk-eyebrow">Pricing</div>
                    <h2 class="mk-section-head__h2">Free forever. £9/month when you're ready.</h2>
                    <p class="mk-section-head__sub">No hidden fees. No surprises. Cancel any time.</p>
                </div>
                <div class="mk-pricing-grid">
                    <!-- Free plan -->
                    <div class="mk-plan">
                        <div class="mk-plan__tier">Free</div>
                        <div class="mk-plan__price-row">
                            <span class="mk-plan__price">£0</span>
                            <span class="mk-plan__cadence">forever</span>
                        </div>
                        <p class="mk-plan__sub">Get a website up today, at no cost.</p>
                        <ul class="mk-plan__feats">
                            <li v-for="f in freeFeats" :key="f" class="mk-plan__feat">
                                <span class="mk-plan__feat-check">
                                    <svg
                                        width="14"
                                        height="14"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="var(--mk-accent)"
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
                        <button class="mk-btn mk-btn--secondary mk-btn--full" @click="scrollToSearch">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                            Find my business
                        </button>
                    </div>
                    <!-- Premium plan -->
                    <div class="mk-plan mk-plan--premium">
                        <div class="mk-plan__popular">Best value</div>
                        <div class="mk-plan__tier mk-plan__tier--light">Premium</div>
                        <div class="mk-plan__price-row">
                            <span class="mk-plan__price">£9</span>
                            <span class="mk-plan__cadence mk-plan__cadence--light">a month</span>
                        </div>
                        <p class="mk-plan__sub mk-plan__sub--light">For businesses who want a custom address and the full feature set.</p>
                        <ul class="mk-plan__feats">
                            <li v-for="f in proFeats" :key="f" class="mk-plan__feat">
                                <span class="mk-plan__feat-check mk-plan__feat-check--light">
                                    <svg
                                        width="14"
                                        height="14"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="#fff"
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
                        <button class="mk-btn mk-btn--white mk-btn--full">Start Premium — £9/mo</button>
                    </div>
                </div>
                <p class="mk-pricing-note">Prices in GBP. VAT included where applicable. No hidden fees. No price hikes at renewal. Cancel any time.</p>
            </div>
        </section>

        <!-- ══════════════════════════════════════════════
             FAQ
        ══════════════════════════════════════════════ -->
        <section id="faq" class="mk-section mk-section--flush-top">
            <div class="mk-container mk-container--narrow">
                <div class="mk-section-head mk-section-head--center">
                    <div class="mk-eyebrow">FAQ</div>
                    <h2 class="mk-section-head__h2">Your questions, answered.</h2>
                </div>
                <div class="mk-faq">
                    <div v-for="(item, i) in faqs" :key="i" class="mk-faq__item">
                        <button class="mk-faq__btn" @click="toggleFaq(i)">
                            <span class="mk-faq__q">{{ item.q }}</span>
                            <span class="mk-faq__chevron" :class="{ 'mk-faq__chevron--open': faqOpen === i }">
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
                        <div v-if="faqOpen === i" class="mk-faq__a" v-html="item.a"></div>
                    </div>
                </div>
                <p class="mk-faq__note">
                    Still got questions? <a href="mailto:support@321sites.com" class="mk-faq__note-link">Drop us a line</a> — a real person will reply
                    within a day.
                </p>
            </div>
        </section>

        <!-- ══════════════════════════════════════════════
             CTA BAND
        ══════════════════════════════════════════════ -->
        <section class="mk-section mk-section--flush-top">
            <div class="mk-container">
                <div class="mk-cta-band">
                    <div class="mk-cta-band__copy">
                        <h2 class="mk-cta-band__h2">A website for your business in the time it takes to make a brew.</h2>
                        <p class="mk-cta-band__sub">Free to start, no credit card, no pushy sales calls. Give it a go — there's no commitment.</p>
                    </div>
                    <div class="mk-cta-band__action">
                        <button class="mk-btn mk-btn--accent-white mk-btn--lg" @click="scrollToSearch">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                            Find my business
                        </button>
                        <div class="mk-cta-band__trust">
                            <span>
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
                            <span>
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
        <footer class="mk-footer">
            <div class="mk-container">
                <div class="mk-footer__top">
                    <div class="mk-footer__brand">
                        <div class="mk-footer__logo" style="color: #ffffff">
                            <AppLogo />
                        </div>
                        <p class="mk-footer__tagline">A website for your business in the time it takes to make a brew.</p>
                    </div>
                    <div class="mk-footer__cols">
                        <div class="mk-footer__col">
                            <div class="mk-footer__col-head">Product</div>
                            <a class="mk-footer__link" @click.prevent="scrollTo('how')">How it works</a>
                            <a class="mk-footer__link" @click.prevent="scrollTo('features')">Features</a>
                            <a class="mk-footer__link" @click.prevent="scrollTo('pricing')">Pricing</a>
                            <a class="mk-footer__link" @click.prevent="scrollTo('faq')">FAQ</a>
                        </div>
                        <div class="mk-footer__col">
                            <div class="mk-footer__col-head">Support</div>
                            <Link href="/help" class="mk-footer__link">Help centre</Link>
                            <a href="mailto:support@321sites.com" class="mk-footer__link">Contact us</a>
                        </div>
                        <div class="mk-footer__col">
                            <div class="mk-footer__col-head">Legal</div>
                            <Link href="/terms" class="mk-footer__link">Terms</Link>
                            <Link href="/privacy" class="mk-footer__link">Privacy</Link>
                        </div>
                    </div>
                </div>
                <div class="mk-footer__bottom">
                    <div>© 2026 321Sites Ltd. Made in the UK.</div>
                    <div class="mk-footer__status">
                        <span class="mk-footer__status-dot"></span>
                        All systems go
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>

<style>
/* ── Design tokens ──────────────────────────────────────────────────────────── */
:root {
    --mk-bg: #ffffff;
    --mk-surface: #ffffff;
    --mk-ink: #0f172a;
    --mk-ink-mid: #3d4a5c;
    --mk-ink-soft: #64748b;
    --mk-line: #dde1e8;
    --mk-line-soft: #e8ecf1;
    --mk-panel: #edf1f8;
    --mk-accent: #1e66f5;
    --mk-accent-soft: #e6eefe;
    --mk-accent-fg: #ffffff;
}
@keyframes mk-spin {
    to {
        transform: rotate(360deg);
    }
}
</style>

<style scoped>
/* ── Base ───────────────────────────────────────────────────────────────────── */
.mk-page {
    font-family: 'Inter', ui-sans-serif, system-ui, sans-serif;
    background: var(--mk-bg);
    color: var(--mk-ink);
    -webkit-font-smoothing: antialiased;
    min-height: 100vh;
    overflow-x: clip;
}

/* ── Container ──────────────────────────────────────────────────────────────── */
.mk-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 24px;
}
.mk-container--narrow {
    max-width: 860px;
}

/* ── Buttons ────────────────────────────────────────────────────────────────── */
.mk-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    height: 48px;
    padding: 0 20px;
    border-radius: 10px;
    font-family: inherit;
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
    border: 1.5px solid transparent;
    transition:
        opacity 0.1s ease,
        background 0.1s ease;
    white-space: nowrap;
    flex-shrink: 0;
}
.mk-btn--primary {
    background: var(--mk-accent);
    color: var(--mk-accent-fg);
    border-color: var(--mk-accent);
}
.mk-btn--primary:hover {
    opacity: 0.9;
}
.mk-btn--secondary {
    background: var(--mk-surface);
    color: var(--mk-ink);
    border-color: var(--mk-line);
}
.mk-btn--secondary:hover {
    background: var(--mk-panel);
}
.mk-btn--white {
    background: #fff;
    color: var(--mk-ink);
    border-color: transparent;
}
.mk-btn--white:hover {
    opacity: 0.9;
}
.mk-btn--accent-white {
    background: var(--mk-accent);
    color: #fff;
    border-color: var(--mk-accent);
    font-size: 17px;
    height: 56px;
    padding: 0 28px;
}
.mk-btn--accent-white:hover {
    opacity: 0.9;
}
.mk-btn--lg {
    height: 56px;
    font-size: 17px;
    padding: 0 28px;
}
.mk-btn--sm {
    height: 38px;
    font-size: 13px;
    padding: 0 14px;
}
.mk-btn--full {
    width: 100%;
    justify-content: center;
}
.mk-btn--taken {
    background: var(--mk-panel);
    color: var(--mk-muted);
    border: 1px solid var(--mk-border);
    cursor: not-allowed;
    pointer-events: none;
    white-space: nowrap;
}

/* ── Nav ────────────────────────────────────────────────────────────────────── */
.mk-nav {
    position: sticky;
    top: 0;
    z-index: 40;
    background: rgba(238, 244, 255, 0.92);
    backdrop-filter: saturate(140%) blur(8px);
    -webkit-backdrop-filter: saturate(140%) blur(8px);
    border-bottom: 1px solid transparent;
    transition: border-color 0.2s ease, background 0.2s ease;
}
.mk-nav--scrolled {
    background: rgba(255, 255, 255, 0.92);
    border-bottom-color: var(--mk-line-soft);
}
.mk-nav__inner {
    display: flex;
    align-items: center;
    gap: 20px;
    padding-top: 14px;
    padding-bottom: 14px;
}
.mk-nav__logo {
    display: flex;
    align-items: center;
    gap: 10px;
    text-decoration: none;
    color: var(--mk-ink);
    flex-shrink: 0;
}
.mk-nav__logo-mark {
    width: 34px;
    height: 34px;
    border-radius: 8px;
    background: var(--mk-ink);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.mk-nav__logo-name {
    font-size: 20px;
    font-weight: 800;
    letter-spacing: -0.4px;
}
.mk-nav__links {
    display: flex;
    align-items: center;
    gap: 4px;
    flex: 1;
    margin-left: 24px;
}
.mk-nav__link {
    padding: 8px 12px;
    border: none;
    background: transparent;
    cursor: pointer;
    font-family: inherit;
    font-size: 15px;
    font-weight: 600;
    color: var(--mk-ink-mid);
    border-radius: 8px;
    transition:
        background 0.1s ease,
        color 0.1s ease;
}
.mk-nav__link:hover {
    background: var(--mk-panel);
    color: var(--mk-ink);
}
.mk-nav__cta {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-shrink: 0;
}
.mk-nav__signin {
    height: 42px;
    padding: 0 16px;
    font-size: 15px;
    font-weight: 600;
    color: var(--mk-ink);
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    border-radius: 8px;
    transition: background 0.1s ease;
}
.mk-nav__signin:hover {
    background: var(--mk-panel);
}

/* ── Hero ───────────────────────────────────────────────────────────────────── */
.mk-hero {
    padding: 0;
    background: linear-gradient(180deg, #eef4ff 0%, #ffffff 100%);
}
.mk-hero__split {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 56px;
    align-items: start;
    padding: 80px 0 72px;
}
.mk-hero__left {
    text-align: left;
    padding-top: 24px;
}
.mk-hero__right {
    position: relative;
}

/* ── Preview browser mockup ──────────────────────────────────────────────────── */
.mk-preview-browser {
    border-radius: 12px;
    overflow: hidden;
    border: 1.5px solid var(--mk-line);
    box-shadow:
        0 24px 60px -12px rgba(0, 0, 0, 0.14),
        0 8px 20px -8px rgba(0, 0, 0, 0.08);
}
.mk-preview-browser__bar {
    height: 40px;
    background: #f5f7fa;
    border-bottom: 1.5px solid var(--mk-line);
    display: flex;
    align-items: center;
    padding: 0 14px;
    gap: 10px;
}
.mk-preview-browser__dots {
    display: flex;
    gap: 5px;
    flex-shrink: 0;
}
.mk-preview-browser__dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
}
.mk-preview-browser__dot:nth-child(1) { background: #ff5f57; }
.mk-preview-browser__dot:nth-child(2) { background: #febc2e; }
.mk-preview-browser__dot:nth-child(3) { background: #28c840; }
.mk-preview-browser__urlbar {
    flex: 1;
    height: 24px;
    background: #e9ecf0;
    border-radius: 12px;
    display: flex;
    align-items: center;
    padding: 0 10px;
    gap: 5px;
    font-size: 11px;
    color: #718096;
    overflow: hidden;
    white-space: nowrap;
    font-family: ui-monospace, 'Menlo', monospace;
}
.mk-preview-iframe-clip {
    width: 100%;
    overflow: clip;
    position: relative;
    height: 520px;
    background: white;
    cursor: default;
}
.mk-preview-iframe {
    position: absolute;
    top: 0;
    left: 0;
    width: 900px; /* JS overrides width (900 desktop / 390 mobile) and height at runtime */
    height: 900px;
    border: none;
    transform-origin: top left;
}
.mk-preview-caption {
    text-align: center;
    font-size: 12px;
    color: var(--mk-ink-soft);
    margin-top: 10px;
    font-weight: 500;
}

/* Pill badge */
.mk-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 14px;
    border-radius: 999px;
    background: var(--mk-accent-soft);
    color: var(--mk-accent);
    border: 1.5px solid rgba(30, 102, 245, 0.15);
    font-size: 14px;
    font-weight: 700;
    margin-bottom: 28px;
}
.mk-pill__dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--mk-accent);
    flex-shrink: 0;
}

.mk-hero__h1 {
    font-size: clamp(32px, 4.2vw, 54px);
    font-weight: 900;
    letter-spacing: -2px;
    line-height: 1.05;
    margin: 0;
    color: var(--mk-ink);
}

.mk-hero__sub {
    font-size: clamp(18px, 2vw, 22px);
    color: var(--mk-ink-mid);
    line-height: 1.5;
    margin: 24px 0 0;
    max-width: 520px;
}

/* Search */
.mk-search-wrap {
    margin-top: 44px;
    position: relative;
    max-width: 600px;
}
.mk-search-stack {
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.mk-search-stack__btn {
    width: 100%;
    justify-content: center;
    border-radius: 12px;
}
.mk-search {
    display: flex;
    align-items: center;
    gap: 6px;
    background: #fff;
    border: 2px solid var(--mk-line);
    border-radius: 16px;
    padding: 8px;
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.06);
    transition:
        box-shadow 0.15s ease,
        border-color 0.15s ease;
}
.mk-search--focused {
    border-color: var(--mk-accent);
    box-shadow:
        0 0 0 6px rgba(30, 102, 245, 0.13),
        0 8px 24px rgba(0, 0, 0, 0.08);
}
.mk-search__icon {
    padding-left: 10px;
    color: var(--mk-ink-soft);
    flex-shrink: 0;
}
.mk-search__input {
    flex: 1;
    border: none;
    outline: none;
    background: transparent;
    font-family: inherit;
    font-size: 20px;
    font-weight: 500;
    color: var(--mk-ink);
    padding: 14px 6px;
    min-width: 0;
}
.mk-search__input::placeholder {
    color: var(--mk-ink-soft);
}

/* Search states */
.mk-search-state {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    padding: 24px;
    background: #fff;
    border: 1.5px solid var(--mk-line);
    border-radius: 16px;
    color: var(--mk-ink-mid);
    font-size: 16px;
    font-weight: 500;
}
.mk-spin {
    animation: mk-spin 0.8s linear infinite;
}

.mk-results {
    background: #fff;
    border: 1.5px solid var(--mk-line);
    border-radius: 16px;
    overflow: hidden;
    text-align: left;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.08);
}
.mk-results__label {
    font-size: 14px;
    font-weight: 700;
    color: var(--mk-ink-soft);
    padding: 16px 20px 8px;
    margin: 0;
}
.mk-results__sub {
    font-size: 14px;
    color: var(--mk-ink-soft);
    padding: 0 20px 4px;
    margin: 0;
}
.mk-result-row {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 20px;
    border-top: 1px solid var(--mk-line-soft);
    cursor: pointer;
    transition: background 0.1s ease;
}
.mk-result-row:hover {
    background: var(--mk-bg);
}
.mk-result-row__icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: var(--mk-panel);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--mk-ink-mid);
    flex-shrink: 0;
}
.mk-result-row__info {
    flex: 1;
    min-width: 0;
}
.mk-result-row__name {
    font-size: 15px;
    font-weight: 700;
    color: var(--mk-ink);
}
.mk-result-row__addr {
    font-size: 13px;
    color: var(--mk-ink-soft);
    margin-top: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.mk-result-row__taken {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 4px;
}
.mk-result-row__taken-link {
    font-size: 12px;
    color: var(--mk-accent);
    text-decoration: none;
    white-space: nowrap;
}
.mk-result-row__taken-link:hover {
    text-decoration: underline;
}
.mk-reset-btn {
    display: block;
    width: 100%;
    padding: 12px 20px;
    background: var(--mk-panel);
    border: none;
    font-family: inherit;
    font-size: 14px;
    font-weight: 600;
    color: var(--mk-ink-mid);
    cursor: pointer;
    text-align: left;
    border-top: 1px solid var(--mk-line-soft);
    transition: background 0.1s ease;
}
.mk-reset-btn:hover {
    background: var(--mk-line-soft);
}

/* Trust */
.mk-trust {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 10px;
    margin-top: 24px;
}
.mk-trust__item {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 14px;
    color: var(--mk-ink-soft);
    font-weight: 500;
}

/* ── Before / After graphic ─────────────────────────────────────────────────── */
.mk-hero__below {
    margin-top: 72px;
    padding-bottom: 0;
}
.mk-before-after {
    display: grid;
    grid-template-columns: 1fr auto 1fr;
    gap: 32px;
    align-items: center;
    max-width: 960px;
    margin: 0 auto;
    padding: 40px 40px 40px;
}
.mk-ba-item {
    min-width: 0;
}
.mk-ba-label {
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: var(--mk-ink-soft);
    margin: 0 0 12px;
}

/* Google card mockup */
.mk-google-card {
    background: var(--mk-surface);
    border: 1.5px solid var(--mk-line);
    border-radius: 14px;
    overflow: hidden;
    font-size: 13px;
}
.mk-google-card__photos {
    display: grid;
    grid-template-columns: 1fr 80px;
    height: 110px;
    gap: 2px;
}
.mk-google-card__photo-main {
    background: linear-gradient(135deg, #c8d8f0 0%, #a8c4e0 100%);
}
.mk-google-card__photo-grid {
    display: grid;
    grid-template-rows: 1fr 1fr;
    gap: 2px;
}
.mk-google-card__photo-grid div:first-child {
    background: #b8d4b0;
}
.mk-google-card__photo-grid div:last-child {
    background: #d4c4a0;
}
.mk-google-card__body {
    padding: 14px;
}
.mk-google-card__name {
    font-size: 15px;
    font-weight: 800;
    color: var(--mk-ink);
    margin: 0 0 2px;
}
.mk-google-card__type {
    font-size: 13px;
    color: var(--mk-ink-soft);
    margin: 0 0 8px;
}
.mk-google-card__rating {
    display: flex;
    align-items: center;
    gap: 4px;
    margin-bottom: 8px;
}
.mk-stars {
    display: inline-flex;
    align-items: center;
    gap: 1px;
}
.mk-google-card__rating-num {
    font-weight: 700;
    font-size: 13px;
    color: var(--mk-ink);
}
.mk-google-card__rating-count {
    color: var(--mk-ink-soft);
    font-size: 13px;
}
.mk-google-card__open {
    color: #1f7a3a;
    font-size: 13px;
    font-weight: 600;
}
.mk-google-card__addr {
    font-size: 13px;
    color: var(--mk-ink-mid);
    margin: 0 0 4px;
}
.mk-google-card__no-web {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 13px;
    color: var(--mk-ink-soft);
    margin-bottom: 10px;
}
.mk-strikethrough {
    text-decoration: line-through;
    color: var(--mk-ink-soft);
}
.mk-google-card__actions {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
}
.mk-google-card__action-btn {
    padding: 5px 10px;
    border: 1.5px solid var(--mk-line);
    border-radius: 6px;
    background: transparent;
    font-family: inherit;
    font-size: 12px;
    font-weight: 600;
    color: var(--mk-ink-mid);
    cursor: default;
}

/* Arrow */
.mk-ba-arrow {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
    flex-shrink: 0;
}
.mk-ba-arrow__logo {
    display: flex;
    align-items: center;
    justify-content: center;
}
.mk-ba-arrow__label {
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 1.5px;
    color: var(--mk-ink-soft);
}

/* Site card mockup */
.mk-site-card {
    background: var(--mk-surface);
    border: 1.5px solid var(--mk-line);
    border-radius: 14px;
    overflow: hidden;
}
.mk-site-card__header {
    background: var(--mk-ink);
    padding: 20px 18px 16px;
    position: relative;
}
.mk-site-card__accent-bar {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: var(--mk-accent);
}
.mk-site-card__logo-mark {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    background: var(--mk-accent);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    font-weight: 800;
    color: #fff;
    margin-bottom: 8px;
}
.mk-site-card__name {
    font-size: 15px;
    font-weight: 800;
    color: #fff;
    margin: 0 0 2px;
}
.mk-site-card__loc {
    font-size: 12px;
    color: rgba(255, 255, 255, 0.65);
    margin: 0;
}
.mk-site-card__actions {
    display: flex;
    gap: 6px;
    padding: 12px;
    border-bottom: 1px solid var(--mk-line-soft);
}
.mk-site-card__btn {
    flex: 1;
    padding: 8px 4px;
    border-radius: 8px;
    font-family: inherit;
    font-size: 12px;
    font-weight: 700;
    cursor: default;
    border: 1.5px solid;
}
.mk-site-card__btn--primary {
    background: var(--mk-accent);
    color: #fff;
    border-color: var(--mk-accent);
}
.mk-site-card__btn--outline {
    background: transparent;
    color: var(--mk-ink);
    border-color: var(--mk-line);
}
.mk-site-card__btn--book {
    background: transparent;
    color: var(--mk-ink);
    border-color: var(--mk-line);
}
.mk-site-card__about {
    padding: 12px;
    border-bottom: 1px solid var(--mk-line-soft);
}
.mk-site-card__reviews {
    padding: 12px;
}
.mk-site-card__section-label {
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    color: var(--mk-ink-soft);
    margin: 0 0 6px;
}
.mk-site-card__about-text {
    font-size: 12px;
    color: var(--mk-ink-mid);
    line-height: 1.5;
    margin: 0;
}
.mk-site-card__review-row {
    display: flex;
    align-items: center;
    gap: 6px;
}
.mk-site-card__rating-num {
    font-size: 14px;
    font-weight: 700;
    color: var(--mk-ink);
}
.mk-site-card__review-count {
    font-size: 12px;
    color: var(--mk-ink-soft);
}

/* ── Section shared ──────────────────────────────────────────────────────────── */
.mk-section {
    padding: 100px 0;
}
.mk-section--panel {
    background: var(--mk-ink);
}
.mk-section--panel .mk-eyebrow {
    color: #6ea3ff;
}
.mk-section--panel .mk-section-head__h2 {
    color: #ffffff;
}
.mk-section--panel .mk-features-grid {
    gap: 1px;
    background: rgba(255, 255, 255, 0.08);
}
.mk-section--panel .mk-feature-cell {
    background: var(--mk-ink);
}
.mk-section--panel .mk-feature-cell__icon {
    background: rgba(30, 102, 245, 0.18);
}
.mk-section--panel .mk-feature-cell__title {
    color: #ffffff;
}
.mk-section--panel .mk-feature-cell__desc {
    color: rgba(255, 255, 255, 0.55);
}
.mk-section--flush-top {
    padding-top: 40px;
}
.mk-section-head {
    margin-bottom: 56px;
}
.mk-section-head--center {
    text-align: center;
}
.mk-eyebrow {
    font-size: 13px;
    font-weight: 800;
    color: var(--mk-ink-soft);
    text-transform: uppercase;
    letter-spacing: 1.4px;
    margin-bottom: 12px;
}
.mk-section-head__h2 {
    font-size: clamp(30px, 4vw, 48px);
    font-weight: 800;
    color: var(--mk-ink);
    letter-spacing: -1px;
    line-height: 1.08;
    margin: 0;
}
.mk-section-head--center .mk-section-head__h2 {
    max-width: 720px;
    margin: 0 auto;
}
.mk-section-head__sub {
    font-size: 18px;
    color: var(--mk-ink-mid);
    line-height: 1.55;
    margin: 16px auto 0;
    max-width: 620px;
}

/* ── How it works ────────────────────────────────────────────────────────────── */
.mk-steps-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 24px;
}
.mk-step-card {
    background: var(--mk-surface);
    border: 1.5px solid var(--mk-line);
    border-radius: 18px;
    padding: 32px;
    display: flex;
    flex-direction: column;
    gap: 12px;
    position: relative;
}
.mk-step-card__icon {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    background: var(--mk-accent-soft);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.mk-step-card__num {
    position: absolute;
    top: 24px;
    right: 24px;
    font-size: 60px;
    font-weight: 900;
    color: var(--mk-panel);
    line-height: 1;
    letter-spacing: -3px;
}
.mk-step-card__title {
    font-size: 21px;
    font-weight: 800;
    color: var(--mk-ink);
    letter-spacing: -0.3px;
    margin: 6px 0 0;
}
.mk-step-card__desc {
    font-size: 15px;
    color: var(--mk-ink-mid);
    line-height: 1.55;
    margin: 0;
}
.mk-steps-cta {
    margin-top: 48px;
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
}
.mk-steps-cta__note {
    font-size: 14px;
    color: var(--mk-ink-soft);
}

/* ── Features grid ───────────────────────────────────────────────────────────── */
.mk-features-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 2px;
    background: var(--mk-line);
    border: 1.5px solid var(--mk-line);
    border-radius: 18px;
    overflow: hidden;
}
.mk-feature-cell {
    background: var(--mk-surface);
    padding: 28px 24px;
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.mk-feature-cell__icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: var(--mk-accent-soft);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.mk-feature-cell__title {
    font-size: 16px;
    font-weight: 800;
    color: var(--mk-ink);
    letter-spacing: -0.2px;
}
.mk-feature-cell__desc {
    font-size: 14px;
    color: var(--mk-ink-mid);
    line-height: 1.55;
}

/* ── Pricing ─────────────────────────────────────────────────────────────────── */
.mk-pricing-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 24px;
    max-width: 860px;
    margin: 0 auto;
}
.mk-plan {
    background: var(--mk-surface);
    border: 1.5px solid var(--mk-line);
    border-radius: 20px;
    padding: 36px;
    display: flex;
    flex-direction: column;
    gap: 20px;
    position: relative;
}
.mk-plan--premium {
    background: var(--mk-ink);
    color: #fff;
    border-color: var(--mk-ink);
}
.mk-plan__popular {
    position: absolute;
    top: -14px;
    right: 28px;
    padding: 6px 14px;
    border-radius: 999px;
    background: var(--mk-accent);
    color: #fff;
    font-size: 13px;
    font-weight: 800;
}
.mk-plan__tier {
    font-size: 13px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    color: var(--mk-ink-soft);
}
.mk-plan__tier--light {
    color: rgba(255, 255, 255, 0.65);
}
.mk-plan__price-row {
    display: flex;
    align-items: baseline;
    gap: 8px;
}
.mk-plan__price {
    font-size: 54px;
    font-weight: 900;
    letter-spacing: -2px;
    line-height: 1;
}
.mk-plan__cadence {
    font-size: 16px;
    color: var(--mk-ink-soft);
}
.mk-plan__cadence--light {
    color: rgba(255, 255, 255, 0.55);
}
.mk-plan__sub {
    font-size: 15px;
    color: var(--mk-ink-mid);
    line-height: 1.5;
    margin: 0;
}
.mk-plan__sub--light {
    color: rgba(255, 255, 255, 0.7);
}
.mk-plan__feats {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 12px;
    flex: 1;
}
.mk-plan__feat {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    font-size: 15px;
    line-height: 1.45;
}
.mk-plan__feat-check {
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: var(--mk-accent-soft);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    margin-top: 1px;
}
.mk-plan__feat-check--light {
    background: rgba(255, 255, 255, 0.15);
}
.mk-pricing-note {
    text-align: center;
    font-size: 14px;
    color: var(--mk-ink-soft);
    margin-top: 24px;
}

/* ── FAQ ─────────────────────────────────────────────────────────────────────── */
.mk-faq {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-top: 48px;
}
.mk-faq__item {
    background: var(--mk-surface);
    border: 1.5px solid var(--mk-line);
    border-radius: 14px;
    overflow: hidden;
}
.mk-faq__btn {
    width: 100%;
    padding: 20px 24px;
    background: transparent;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    font-family: inherit;
    text-align: left;
}
.mk-faq__q {
    font-size: 17px;
    font-weight: 700;
    color: var(--mk-ink);
    letter-spacing: -0.2px;
}
.mk-faq__chevron {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: var(--mk-panel);
    color: var(--mk-ink);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: transform 0.2s ease;
}
.mk-faq__chevron--open {
    transform: rotate(180deg);
}
.mk-faq__a {
    padding: 0 24px 20px;
    font-size: 15px;
    color: var(--mk-ink-mid);
    line-height: 1.65;
    margin: 0;
    max-width: 760px;
}
.mk-faq__note {
    text-align: center;
    margin-top: 36px;
    font-size: 15px;
    color: var(--mk-ink-soft);
}
.mk-faq__note-link {
    color: var(--mk-ink);
    font-weight: 700;
    text-underline-offset: 3px;
}
.mk-faq__link {
    color: var(--mk-accent);
    font-weight: 600;
    text-decoration: none;
    display: inline-block;
    margin: 8px 0;
}
.mk-faq__link:hover {
    text-decoration: underline;
    text-underline-offset: 3px;
}

/* ── CTA Band ────────────────────────────────────────────────────────────────── */
.mk-cta-band {
    background: var(--mk-ink);
    color: #fff;
    border-radius: 24px;
    padding: clamp(36px, 5vw, 72px) clamp(28px, 5vw, 64px);
    display: grid;
    grid-template-columns: 1.4fr 1fr;
    gap: 48px;
    align-items: center;
}
.mk-cta-band__h2 {
    font-size: clamp(26px, 3.5vw, 42px);
    font-weight: 900;
    letter-spacing: -0.8px;
    line-height: 1.08;
    margin: 0;
}
.mk-cta-band__sub {
    font-size: 17px;
    color: rgba(255, 255, 255, 0.72);
    line-height: 1.5;
    margin: 16px 0 0;
    max-width: 480px;
}
.mk-cta-band__action {
    display: flex;
    flex-direction: column;
    gap: 16px;
}
.mk-cta-band__trust {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 20px;
    font-size: 14px;
    color: rgba(255, 255, 255, 0.55);
}
.mk-cta-band__trust span {
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

/* ── Footer ──────────────────────────────────────────────────────────────────── */
.mk-footer {
    background: var(--mk-ink);
    color: rgba(255, 255, 255, 0.7);
    margin-top: 0;
}
.mk-footer__top {
    display: grid;
    grid-template-columns: 1.4fr 1fr;
    gap: 48px;
    padding: 72px 24px 48px;
    max-width: 1200px;
    margin: 0 auto;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}
.mk-footer__brand {
    max-width: 320px;
}
.mk-footer__logo {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 16px;
}
.mk-footer__logo-mark {
    width: 34px;
    height: 34px;
    border-radius: 8px;
    background: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 900;
    font-size: 15px;
    color: var(--mk-ink);
}
.mk-footer__logo-name {
    font-size: 20px;
    font-weight: 800;
    color: #fff;
    letter-spacing: -0.4px;
}
.mk-footer__tagline {
    font-size: 15px;
    line-height: 1.6;
    margin: 0;
}
.mk-footer__cols {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 32px;
}
.mk-footer__col {
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.mk-footer__col-head {
    font-size: 12px;
    font-weight: 800;
    color: #fff;
    text-transform: uppercase;
    letter-spacing: 1.4px;
    margin-bottom: 4px;
}
.mk-footer__link {
    font-size: 15px;
    color: rgba(255, 255, 255, 0.65);
    text-decoration: none;
    cursor: pointer;
    transition: color 0.1s ease;
}
.mk-footer__link:hover {
    color: #fff;
}
.mk-footer__bottom {
    max-width: 1200px;
    margin: 0 auto;
    padding: 28px 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 14px;
    flex-wrap: wrap;
    gap: 12px;
}
.mk-footer__status {
    display: flex;
    align-items: center;
    gap: 8px;
}
.mk-footer__status-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #5bd46a;
}

/* ── Hamburger button ────────────────────────────────────────────────────────── */
.mk-nav__hamburger {
    display: none;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    gap: 5px;
    width: 40px;
    height: 40px;
    border: none;
    background: transparent;
    cursor: pointer;
    padding: 4px;
    border-radius: 8px;
    flex-shrink: 0;
    transition: background 0.1s ease;
}
.mk-nav__hamburger:hover {
    background: var(--mk-panel);
}
.mk-nav__hamburger span {
    display: block;
    width: 22px;
    height: 2px;
    background: var(--mk-ink);
    border-radius: 2px;
    transition: transform 0.2s ease, opacity 0.2s ease;
    transform-origin: center;
}
/* Animate to ✕ when open */
.mk-nav__hamburger--open span:nth-child(1) {
    transform: translateY(7px) rotate(45deg);
}
.mk-nav__hamburger--open span:nth-child(2) {
    opacity: 0;
    transform: scaleX(0);
}
.mk-nav__hamburger--open span:nth-child(3) {
    transform: translateY(-7px) rotate(-45deg);
}

/* ── Mobile menu panel ───────────────────────────────────────────────────────── */
.mk-mobile-menu {
    display: none;
    flex-direction: column;
    background: #fff;
    /* transparent border so the 1px line doesn't show when collapsed */
    border-top: 1px solid transparent;
    /* zero vertical padding — content-box sizing means padding bleeds past max-height:0 */
    padding: 0 24px;
    gap: 4px;
    overflow: hidden;
    max-height: 0;
    transition: max-height 0.28s ease, padding 0.28s ease, border-color 0.15s ease;
}
.mk-mobile-menu--open {
    max-height: 480px;
    padding: 16px 24px 28px;
    border-top-color: var(--mk-line-soft);
}
.mk-mobile-menu__links {
    display: flex;
    flex-direction: column;
    gap: 2px;
    margin-bottom: 16px;
}
.mk-mobile-menu__link {
    width: 100%;
    text-align: left;
    padding: 13px 12px;
    border: none;
    background: transparent;
    font-family: inherit;
    font-size: 17px;
    font-weight: 600;
    color: var(--mk-ink);
    cursor: pointer;
    border-radius: 10px;
    transition: background 0.1s ease;
}
.mk-mobile-menu__link:hover {
    background: var(--mk-panel);
}
.mk-mobile-menu__actions {
    display: flex;
    flex-direction: column;
    gap: 10px;
    border-top: 1px solid var(--mk-line-soft);
    padding-top: 16px;
}
.mk-mobile-menu__signin {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 48px;
    border-radius: 10px;
    border: 1.5px solid var(--mk-line);
    font-family: inherit;
    font-size: 15px;
    font-weight: 700;
    color: var(--mk-ink);
    text-decoration: none;
    transition: background 0.1s ease;
}
.mk-mobile-menu__signin:hover {
    background: var(--mk-panel);
}

/* ── Responsive ──────────────────────────────────────────────────────────────── */
@media (max-width: 900px) {
    .mk-nav__links {
        display: none;
    }
    .mk-nav__cta--desktop {
        display: none;
    }
    .mk-nav__hamburger {
        display: flex;
    }
    .mk-mobile-menu {
        display: flex;
    }
    .mk-nav__inner {
        justify-content: space-between;
    }
    /* Stack hero split on tablet */
    .mk-hero__split {
        grid-template-columns: 1fr;
        padding: 56px 0 48px;
        gap: 40px;
    }
    .mk-hero__left {
        text-align: center;
    }
    .mk-hero__sub {
        max-width: 560px;
        margin-left: auto;
        margin-right: auto;
    }
    .mk-search-wrap {
        margin-left: auto;
        margin-right: auto;
    }
    /* Trust items: keep icon pinned to text on wrap */
    .mk-trust {
        align-items: flex-start;
        display: inline-flex;
        flex-direction: column;
        margin: 0 auto;
    }
    .mk-trust__item {
        display: flex;
        align-items: flex-start;
        text-align: left;
    }
    .mk-preview-iframe-clip {
        height: 400px;
    }
    /* Tighter section spacing on tablet/mobile */
    .mk-section {
        padding: 72px 0;
    }
    .mk-section--flush-top {
        padding-top: 32px;
    }
    .mk-section-head {
        margin-bottom: 40px;
    }
    .mk-steps-grid {
        grid-template-columns: 1fr;
    }
    .mk-features-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    .mk-pricing-grid {
        grid-template-columns: 1fr;
    }
    /* CTA band: centre-align content on mobile */
    .mk-cta-band {
        grid-template-columns: 1fr;
        text-align: center;
    }
    .mk-cta-band__sub {
        margin-left: auto;
        margin-right: auto;
    }
    .mk-cta-band__action {
        align-items: center;
    }
    .mk-cta-band__action .mk-btn {
        width: 100%;
        max-width: 360px;
        justify-content: center;
    }
    /* FAQ */
    .mk-faq {
        margin-top: 32px;
    }
    /* Footer: stack brand above a 3-col link grid */
    .mk-footer__top {
        grid-template-columns: 1fr;
        gap: 32px;
        padding-top: 48px;
        padding-bottom: 32px;
    }
    .mk-footer__cols {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 640px) {
    .mk-hero__split {
        padding: 44px 0 40px;
    }
    .mk-hero__h1 {
        letter-spacing: -1px;
    }
    .mk-hero__right {
        display: none;
    }
    .mk-section {
        padding: 64px 0;
    }
    .mk-section-head {
        margin-bottom: 32px;
    }
    .mk-features-grid {
        grid-template-columns: 1fr;
    }
    /* Remove extra top padding on hero left col — split already has top padding */
    .mk-hero__left {
        padding-top: 0;
    }
    /* Reduce pill bottom margin so headline sits closer */
    .mk-pill {
        margin-bottom: 20px;
        white-space: normal;
        text-align: center;
    }
    /* Footer: 2-col links on small mobile, Legal below */
    .mk-footer__cols {
        grid-template-columns: repeat(2, 1fr);
    }
    /* Mobile menu links: tighter tap targets */
    .mk-mobile-menu__link {
        padding: 11px 12px;
        font-size: 16px;
    }
    /* Step cards: tighter padding on small screens */
    .mk-step-card {
        padding: 24px;
    }
    /* Feature cells: tighter padding on small screens */
    .mk-feature-cell {
        padding: 24px;
    }
}

</style>
