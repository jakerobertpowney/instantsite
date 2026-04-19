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
const handleScroll = () => { scrolled.value = window.scrollY > 60; };

// ── FAQ accordion ─────────────────────────────────────────────────────────────
const faqOpen = ref<number | null>(0);
const toggleFaq = (i: number) => { faqOpen.value = faqOpen.value === i ? null : i; };

// ── Smooth scroll helper ──────────────────────────────────────────────────────
const scrollTo = (id: string) => {
    const el = document.getElementById(id);
    if (el) window.scrollTo({ top: el.offsetTop - 72, behavior: 'smooth' });
};

const scrollToSearch = () => {
    const el = document.getElementById('hero-search');
    if (el) { el.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
};

onMounted(() => {
    window.addEventListener('scroll', handleScroll, { passive: true });
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
});

// ── Static data ───────────────────────────────────────────────────────────────
const freeFeats = [
    'Your own one-page website',
    'Free address: yourname.321sites.com',
    'Auto-updates from Google',
    'Photo gallery & reviews',
    'Call, message & WhatsApp buttons',
    'Works on any phone',
];

const proFeats = [
    'Everything in Free',
    'Use your own web address (e.g. thompsondecorating.co.uk)',
    'Remove the "Powered by 321Sites" mark',
    'A proper contact form that emails you',
    'Extra colour themes & backgrounds',
    'Priority support',
];

const faqs = [
    { q: 'Do I need my own web address?', a: "No. You get a free one — yourname.321sites.com — the minute you sign up. If you'd rather use your own (like thompsondecorating.co.uk), you can upgrade to Premium any time." },
    { q: "What if I'm not on Google Business Profile?", a: "You'll need to add your business to Google first — it's free, and we can point you through it. Once Google knows about you, we can build your site." },
    { q: "Can I edit things like my description and photos?", a: "Your business details (name, address, hours, photos, reviews) are pulled from Google, so you change them once there and they update everywhere. Everything else — the description, colours, buttons, which sections show — you edit inside 321Sites with plain toggles and text boxes." },
    { q: "I'm not very techy. Will I be able to use it?", a: "That's exactly who it's built for. No drag-and-drop, no picking templates, no jargon. If you can type your business name, you can use 321Sites." },
    { q: "Can I cancel?", a: "Yes, any time, with one click. If you cancel Premium you drop back to Free — your site stays up." },
    { q: "Will it show up on Google search?", a: "We write the hidden SEO bits (page title, description, structured data) for you, so Google knows what your site is about. You can tweak the title yourself in Search & sharing." },
];

const features = [
    { t: 'Your info, auto-filled', d: 'Name, address, hours, phone, photos, reviews — all pulled from Google. Change it there, it updates here.' },
    { t: 'Looks good on a phone', d: 'Most of your customers will find you on a phone. Your site works perfectly on the smallest screen.' },
    { t: 'One big "Call" button', d: 'Tap the number and it dials. No fiddly forms. Add WhatsApp too if you like.' },
    { t: 'Show your best work', d: 'A gallery of your photos, right at the top. Add or change them in Google Business Profile and they appear here.' },
    { t: 'Reviews front and centre', d: 'Your Google star rating and best reviews, shown on your site. No fetching, no copy-paste.' },
    { t: 'Shows up on Google', d: "We write the behind-the-scenes SEO bits so you're findable. You can tweak the title and description yourself." },
    { t: 'Edit anything in plain English', d: "Big toggles, big buttons — no menus hiding from you." },
    { t: 'Your own web address', d: 'Starts free as yourname.321sites.com. Upgrade to use your own domain whenever you like.' },
];
</script>

<template>
    <Head title="Your business, online in minutes — 321Sites">
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

                <!-- Nav links -->
                <nav class="mk-nav__links">
                    <button class="mk-nav__link" @click="scrollTo('how')">How it works</button>
                    <button class="mk-nav__link" @click="scrollTo('features')">Features</button>
                    <button class="mk-nav__link" @click="scrollTo('pricing')">Pricing</button>
                    <button class="mk-nav__link" @click="scrollTo('faq')">FAQ</button>
                </nav>

                <!-- CTA — auth-aware -->
                <div class="mk-nav__cta">
                    <template v-if="isLoggedIn">
                        <Link href="/dashboard" class="mk-btn mk-btn--primary">Dashboard</Link>
                    </template>
                    <template v-else>
                        <Link href="/login" class="mk-nav__signin">Sign in</Link>
                        <button class="mk-btn mk-btn--primary" @click="scrollToSearch">Find my business</button>
                    </template>
                </div>
            </div>
        </header>

        <!-- ══════════════════════════════════════════════
             HERO
        ══════════════════════════════════════════════ -->
        <section class="mk-hero">
            <div class="mk-container">
                <div class="mk-hero__inner">

                    <!-- Badge pill -->
                    <div class="mk-pill">
                        <span class="mk-pill__dot"></span>
                        Free · No credit card · 2 minute setup
                    </div>

                    <!-- Headline -->
                    <h1 class="mk-hero__h1">Your business. Online. Before lunch.</h1>
                    <p class="mk-hero__sub">
                        Type your business name and we'll build you a proper website from what's already on Google. Free forever.
                    </p>

                    <!-- Search box -->
                    <div id="hero-search" class="mk-search-wrap">
                        <!-- Idle: search form -->
                        <div
                            v-if="searchPhase === 'idle'"
                            class="mk-search"
                            :class="{ 'mk-search--focused': searchFocused }"
                        >
                            <svg class="mk-search__icon" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
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
                            <button class="mk-btn mk-btn--primary mk-btn--lg" @click="searchPlaces">
                                Find on Google
                            </button>
                        </div>

                        <!-- Loading -->
                        <div v-else-if="searchPhase === 'loading'" class="mk-search-state">
                            <svg class="mk-spin" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="var(--mk-accent)" stroke-width="2.5" stroke-linecap="round" aria-hidden="true">
                                <path d="M12 2v4m0 12v4M4.93 4.93l2.83 2.83m8.48 8.48 2.83 2.83M2 12h4m12 0h4M4.93 19.07l2.83-2.83m8.48-8.48 2.83-2.83"/>
                            </svg>
                            <span>Searching Google Business…</span>
                        </div>

                        <!-- Results -->
                        <div v-else-if="searchPhase === 'results'" class="mk-results">
                            <p class="mk-results__label">Is one of these your business?</p>
                            <div v-for="place in places" :key="place.id" class="mk-result-row">
                                <div class="mk-result-row__icon">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>
                                    </svg>
                                </div>
                                <div class="mk-result-row__info">
                                    <div class="mk-result-row__name">{{ place.displayName.text }}</div>
                                    <div class="mk-result-row__addr">{{ place.formattedAddress }}</div>
                                </div>
                                <Link :href="show.url(place.id)" class="mk-btn mk-btn--primary mk-btn--sm">
                                    This is me →
                                </Link>
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
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--mk-accent)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                            Pulls from your Google Business Profile
                        </span>
                        <span class="mk-trust__item">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--mk-accent)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                            Keep your free address forever
                        </span>
                    </div>
                </div>

                <!-- ── From Google listing to live website ── -->
                <div class="mk-hero__below">
                    <div class="mk-before-after">
                        <!-- Left: Google listing -->
                        <div class="mk-ba-item">
                            <p class="mk-ba-label">Your Google listing</p>
                            <div class="mk-google-card">
                                <div class="mk-google-card__photos">
                                    <div class="mk-google-card__photo-main"></div>
                                    <div class="mk-google-card__photo-grid">
                                        <div></div>
                                        <div></div>
                                    </div>
                                </div>
                                <div class="mk-google-card__body">
                                    <p class="mk-google-card__name">Dave's Painting & Decorating</p>
                                    <p class="mk-google-card__type">Painter · decorator</p>
                                    <div class="mk-google-card__rating">
                                        <span class="mk-stars">
                                            <svg v-for="n in 5" :key="n" width="12" height="12" viewBox="0 0 24 24" fill="#f59e0b" aria-hidden="true"><path d="M13.849 4.22c-.684-1.626-3.014-1.626-3.698 0L8.397 8.387l-4.552.361c-1.775.14-2.495 2.331-1.142 3.477l3.468 2.937-1.06 4.392c-.413 1.713 1.472 3.067 2.992 2.149L12 19.35l3.897 2.354c1.52.918 3.405-.436 2.992-2.15l-1.06-4.39 3.468-2.938c1.353-1.146.633-3.336-1.142-3.477l-4.552-.36-1.754-4.17Z"/></svg>
                                        </span>
                                        <span class="mk-google-card__rating-num">4.9</span>
                                        <span class="mk-google-card__rating-count">(47)</span>
                                        <span class="mk-google-card__open">· Open</span>
                                    </div>
                                    <p class="mk-google-card__addr">📍 Manchester, England</p>
                                    <div class="mk-google-card__no-web">
                                        <span>🌐</span>
                                        <span class="mk-strikethrough">No website</span>
                                    </div>
                                    <div class="mk-google-card__actions">
                                        <button v-for="a in ['Directions','Call','Save']" :key="a" class="mk-google-card__action-btn">{{ a }}</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Middle: Arrow + logo -->
                        <div class="mk-ba-arrow">
                            <div class="mk-ba-arrow__logo">
                                <AppLogo />
                            </div>
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="var(--mk-accent)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M5 12h14m-7-7 7 7-7 7"/>
                            </svg>
                            <span class="mk-ba-arrow__label">INSTANT</span>
                        </div>

                        <!-- Right: New website -->
                        <div class="mk-ba-item">
                            <p class="mk-ba-label">Your new website</p>
                            <div class="mk-site-card">
                                <div class="mk-site-card__header">
                                    <div class="mk-site-card__accent-bar"></div>
                                    <div class="mk-site-card__logo-mark">D</div>
                                    <p class="mk-site-card__name">Dave's Painting & Decorating</p>
                                    <p class="mk-site-card__loc">📍 Manchester, England</p>
                                </div>
                                <div class="mk-site-card__actions">
                                    <button class="mk-site-card__btn mk-site-card__btn--primary">📞 Call</button>
                                    <button class="mk-site-card__btn mk-site-card__btn--outline">✉ Message</button>
                                    <button class="mk-site-card__btn mk-site-card__btn--book">📅 Book</button>
                                </div>
                                <div class="mk-site-card__about">
                                    <p class="mk-site-card__section-label">About us</p>
                                    <p class="mk-site-card__about-text">Family-run painting & decorating in Manchester with 20+ years' experience…</p>
                                </div>
                                <div class="mk-site-card__reviews">
                                    <p class="mk-site-card__section-label">Reviews</p>
                                    <div class="mk-site-card__review-row">
                                        <span class="mk-stars">
                                            <svg v-for="n in 5" :key="n" width="12" height="12" viewBox="0 0 24 24" fill="#f59e0b" aria-hidden="true"><path d="M13.849 4.22c-.684-1.626-3.014-1.626-3.698 0L8.397 8.387l-4.552.361c-1.775.14-2.495 2.331-1.142 3.477l3.468 2.937-1.06 4.392c-.413 1.713 1.472 3.067 2.992 2.149L12 19.35l3.897 2.354c1.52.918 3.405-.436 2.992-2.15l-1.06-4.39 3.468-2.938c1.353-1.146.633-3.336-1.142-3.477l-4.552-.36-1.754-4.17Z"/></svg>
                                        </span>
                                        <span class="mk-site-card__rating-num">4.9</span>
                                        <span class="mk-site-card__review-count">· 47 Google reviews</span>
                                    </div>
                                </div>
                            </div>
                        </div>
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
                    <h2 class="mk-section-head__h2">From Google listing to a proper website in three steps.</h2>
                    <p class="mk-section-head__sub">No drag-and-drop. No templates to pick. We build the whole thing from what Google already knows about your business.</p>
                </div>
                <div class="mk-steps-grid">
                    <div v-for="(step, i) in [
                        { n: '01', t: 'Search for your business', d: 'Type your business name. We\'ll find you on Google in seconds.', icon: 'search' },
                        { n: '02', t: 'Add your finishing touches', d: 'Upload a logo, tweak the text, pick a colour. Or skip — it works out of the box.', icon: 'palette' },
                        { n: '03', t: 'Share your new website', d: 'You get a free web address like yourname.321sites.com. Hand it to customers, stick it on your van.', icon: 'globe' },
                    ]" :key="i" class="mk-step-card">
                        <div class="mk-step-card__icon">
                            <svg v-if="step.icon === 'search'" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="var(--mk-accent)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
                            </svg>
                            <svg v-else-if="step.icon === 'palette'" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="var(--mk-accent)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="13.5" cy="6.5" r=".5" fill="var(--mk-accent)"/><circle cx="17.5" cy="10.5" r=".5" fill="var(--mk-accent)"/><circle cx="8.5" cy="7.5" r=".5" fill="var(--mk-accent)"/><circle cx="6.5" cy="12.5" r=".5" fill="var(--mk-accent)"/>
                                <path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 0 1 1.668-1.668h1.996c3.051 0 5.555-2.503 5.555-5.554C21.965 6.012 17.461 2 12 2z"/>
                            </svg>
                            <svg v-else width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="var(--mk-accent)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                            </svg>
                        </div>
                        <div class="mk-step-card__num">{{ step.n }}</div>
                        <h3 class="mk-step-card__title">{{ step.t }}</h3>
                        <p class="mk-step-card__desc">{{ step.d }}</p>
                    </div>
                </div>
                <div class="mk-steps-cta">
                    <button class="mk-btn mk-btn--primary mk-btn--lg" @click="scrollToSearch">Find my business on Google</button>
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
                            <svg v-if="i === 0" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="var(--mk-accent)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                            <svg v-else-if="i === 1" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="var(--mk-accent)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
                            <svg v-else-if="i === 2" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="var(--mk-accent)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.6 1.32h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 8.91a16 16 0 0 0 6 6l.8-.8a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                            <svg v-else-if="i === 3" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="var(--mk-accent)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>
                            <svg v-else-if="i === 4" width="22" height="22" viewBox="0 0 24 24" fill="#f59e0b" aria-hidden="true"><path d="M13.849 4.22c-.684-1.626-3.014-1.626-3.698 0L8.397 8.387l-4.552.361c-1.775.14-2.495 2.331-1.142 3.477l3.468 2.937-1.06 4.392c-.413 1.713 1.472 3.067 2.992 2.149L12 19.35l3.897 2.354c1.52.918 3.405-.436 2.992-2.15l-1.06-4.39 3.468-2.938c1.353-1.146.633-3.336-1.142-3.477l-4.552-.36-1.754-4.17Z"/></svg>
                            <svg v-else-if="i === 5" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="var(--mk-accent)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                            <svg v-else-if="i === 6" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="var(--mk-accent)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            <svg v-else width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="var(--mk-accent)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
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
                    <h2 class="mk-section-head__h2">One simple price. Cancel whenever.</h2>
                    <p class="mk-section-head__sub">Start on Free. Upgrade when you outgrow it. No setup fees, no surprises.</p>
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
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--mk-accent)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                                </span>
                                {{ f }}
                            </li>
                        </ul>
                        <button class="mk-btn mk-btn--secondary mk-btn--full" @click="scrollToSearch">Find my business</button>
                    </div>
                    <!-- Premium plan -->
                    <div class="mk-plan mk-plan--premium">
                        <div class="mk-plan__popular">Most popular</div>
                        <div class="mk-plan__tier mk-plan__tier--light">Premium</div>
                        <div class="mk-plan__price-row">
                            <span class="mk-plan__price">£9</span>
                            <span class="mk-plan__cadence mk-plan__cadence--light">a month</span>
                        </div>
                        <p class="mk-plan__sub mk-plan__sub--light">For when you want your own web address and a bit more polish.</p>
                        <ul class="mk-plan__feats">
                            <li v-for="f in proFeats" :key="f" class="mk-plan__feat">
                                <span class="mk-plan__feat-check mk-plan__feat-check--light">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                                </span>
                                {{ f }}
                            </li>
                        </ul>
                        <button class="mk-btn mk-btn--white mk-btn--full">Get Premium</button>
                    </div>
                </div>
                <p class="mk-pricing-note">Prices in GBP. VAT included where applicable. Cancel any time.</p>
            </div>
        </section>

        <!-- ══════════════════════════════════════════════
             FAQ
        ══════════════════════════════════════════════ -->
        <section id="faq" class="mk-section">
            <div class="mk-container mk-container--narrow">
                <div class="mk-section-head mk-section-head--center">
                    <div class="mk-eyebrow">FAQ</div>
                    <h2 class="mk-section-head__h2">The questions we get asked most.</h2>
                </div>
                <div class="mk-faq">
                    <div v-for="(item, i) in faqs" :key="i" class="mk-faq__item">
                        <button class="mk-faq__btn" @click="toggleFaq(i)">
                            <span class="mk-faq__q">{{ item.q }}</span>
                            <span class="mk-faq__chevron" :class="{ 'mk-faq__chevron--open': faqOpen === i }">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="m6 9 6 6 6-6"/>
                                </svg>
                            </span>
                        </button>
                        <div v-if="faqOpen === i" class="mk-faq__a">{{ item.a }}</div>
                    </div>
                </div>
                <p class="mk-faq__note">
                    Still got questions? <a href="mailto:support@321sites.com" class="mk-faq__note-link">Drop us a line</a> — a real person will reply within a day.
                </p>
            </div>
        </section>

        <!-- ══════════════════════════════════════════════
             CTA BAND
        ══════════════════════════════════════════════ -->
        <section class="mk-section">
            <div class="mk-container">
                <div class="mk-cta-band">
                    <div class="mk-cta-band__copy">
                        <h2 class="mk-cta-band__h2">A proper website for your business. In the time it takes to make a cuppa.</h2>
                        <p class="mk-cta-band__sub">Free to start, no credit card, no pushy sales calls. Give it a go — you can bin it if it's not for you.</p>
                    </div>
                    <div class="mk-cta-band__action">
                        <button class="mk-btn mk-btn--accent-white mk-btn--lg" @click="scrollToSearch">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                            Find my business on Google
                        </button>
                        <div class="mk-cta-band__trust">
                            <span>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                                Free forever
                            </span>
                            <span>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
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
                        <div class="mk-footer__logo" style="color: #ffffff;">
                            <AppLogo />
                        </div>
                        <p class="mk-footer__tagline">A website for your business in the time it takes to make a cuppa.</p>
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
    --mk-bg:          #F6F5F1;
    --mk-surface:     #FFFFFF;
    --mk-ink:         #111418;
    --mk-ink-mid:     #434B55;
    --mk-ink-soft:    #6B727D;
    --mk-line:        #D9D6CE;
    --mk-line-soft:   #E6E3DB;
    --mk-panel:       #ECEAE2;
    --mk-accent:      #1E66F5;
    --mk-accent-soft: #E6EEFE;
    --mk-accent-fg:   #FFFFFF;
}
@keyframes mk-spin { to { transform: rotate(360deg); } }
</style>

<style scoped>
/* ── Base ───────────────────────────────────────────────────────────────────── */
.mk-page {
    font-family: 'Inter', ui-sans-serif, system-ui, sans-serif;
    background: var(--mk-bg);
    color: var(--mk-ink);
    -webkit-font-smoothing: antialiased;
    min-height: 100vh;
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
    gap: 8px;
    height: 48px;
    padding: 0 20px;
    border-radius: 10px;
    font-family: inherit;
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
    border: 1.5px solid transparent;
    transition: opacity 0.1s ease, background 0.1s ease;
    white-space: nowrap;
    flex-shrink: 0;
}
.mk-btn--primary {
    background: var(--mk-accent);
    color: var(--mk-accent-fg);
    border-color: var(--mk-accent);
}
.mk-btn--primary:hover { opacity: 0.9; }
.mk-btn--secondary {
    background: var(--mk-surface);
    color: var(--mk-ink);
    border-color: var(--mk-line);
}
.mk-btn--secondary:hover { background: var(--mk-panel); }
.mk-btn--white {
    background: #fff;
    color: var(--mk-ink);
    border-color: transparent;
}
.mk-btn--white:hover { opacity: 0.9; }
.mk-btn--accent-white {
    background: var(--mk-accent);
    color: #fff;
    border-color: var(--mk-accent);
    font-size: 17px;
    height: 56px;
    padding: 0 28px;
}
.mk-btn--accent-white:hover { opacity: 0.9; }
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
.mk-btn--full { width: 100%; justify-content: center; }

/* ── Nav ────────────────────────────────────────────────────────────────────── */
.mk-nav {
    position: sticky;
    top: 0;
    z-index: 40;
    background: rgba(246,245,241,0.92);
    backdrop-filter: saturate(140%) blur(8px);
    -webkit-backdrop-filter: saturate(140%) blur(8px);
    border-bottom: 1px solid transparent;
    transition: border-color 0.2s ease;
}
.mk-nav--scrolled {
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
    transition: background 0.1s ease, color 0.1s ease;
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
.mk-nav__signin:hover { background: var(--mk-panel); }

/* ── Hero ───────────────────────────────────────────────────────────────────── */
.mk-hero {
    padding: 80px 0 0;
}
.mk-hero__inner {
    text-align: center;
    max-width: 920px;
    margin: 0 auto;
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
    border: 1.5px solid rgba(30,102,245,0.15);
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
    font-size: clamp(40px, 7vw, 78px);
    font-weight: 900;
    letter-spacing: -2px;
    line-height: 1.02;
    margin: 0;
    color: var(--mk-ink);
}

.mk-hero__sub {
    font-size: clamp(18px, 2vw, 22px);
    color: var(--mk-ink-mid);
    line-height: 1.5;
    margin: 24px auto 0;
    max-width: 640px;
}

/* Search */
.mk-search-wrap {
    margin-top: 44px;
    position: relative;
    max-width: 720px;
    margin-left: auto;
    margin-right: auto;
}
.mk-search {
    display: flex;
    align-items: center;
    gap: 6px;
    background: #fff;
    border: 2.5px solid var(--mk-ink);
    border-radius: 16px;
    padding: 8px;
    box-shadow: 0 6px 18px rgba(0,0,0,.06);
    transition: box-shadow 0.15s ease, border-color 0.15s ease;
}
.mk-search--focused {
    border-color: var(--mk-accent);
    box-shadow: 0 0 0 6px rgba(30,102,245,0.13), 0 8px 24px rgba(0,0,0,.08);
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
.mk-search__input::placeholder { color: var(--mk-ink-soft); }

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
.mk-spin { animation: mk-spin 0.8s linear infinite; }

.mk-results {
    background: #fff;
    border: 1.5px solid var(--mk-line);
    border-radius: 16px;
    overflow: hidden;
    text-align: left;
    box-shadow: 0 20px 40px rgba(0,0,0,.08);
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
.mk-result-row:hover { background: var(--mk-bg); }
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
.mk-result-row__info { flex: 1; min-width: 0; }
.mk-result-row__name { font-size: 15px; font-weight: 700; color: var(--mk-ink); }
.mk-result-row__addr { font-size: 13px; color: var(--mk-ink-soft); margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
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
.mk-reset-btn:hover { background: var(--mk-line-soft); }

/* Trust */
.mk-trust {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 20px;
    flex-wrap: wrap;
    margin-top: 20px;
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
    background: var(--mk-surface);
    border: 1.5px solid var(--mk-line);
    border-bottom: none;
    border-radius: 20px 20px 0 0;
    padding: 40px 40px 40px;
    box-shadow: 0 -4px 24px rgba(0,0,0,.04);
}
.mk-ba-item { min-width: 0; }
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
    background: linear-gradient(135deg, #C8D8F0 0%, #A8C4E0 100%);
}
.mk-google-card__photo-grid {
    display: grid;
    grid-template-rows: 1fr 1fr;
    gap: 2px;
}
.mk-google-card__photo-grid div:first-child { background: #B8D4B0; }
.mk-google-card__photo-grid div:last-child { background: #D4C4A0; }
.mk-google-card__body { padding: 14px; }
.mk-google-card__name { font-size: 15px; font-weight: 800; color: var(--mk-ink); margin: 0 0 2px; }
.mk-google-card__type { font-size: 13px; color: var(--mk-ink-soft); margin: 0 0 8px; }
.mk-google-card__rating { display: flex; align-items: center; gap: 4px; margin-bottom: 8px; }
.mk-stars { display: inline-flex; align-items: center; gap: 1px; }
.mk-google-card__rating-num { font-weight: 700; font-size: 13px; color: var(--mk-ink); }
.mk-google-card__rating-count { color: var(--mk-ink-soft); font-size: 13px; }
.mk-google-card__open { color: #1F7A3A; font-size: 13px; font-weight: 600; }
.mk-google-card__addr { font-size: 13px; color: var(--mk-ink-mid); margin: 0 0 4px; }
.mk-google-card__no-web { display: flex; align-items: center; gap: 4px; font-size: 13px; color: var(--mk-ink-soft); margin-bottom: 10px; }
.mk-strikethrough { text-decoration: line-through; color: var(--mk-ink-soft); }
.mk-google-card__actions { display: flex; gap: 6px; flex-wrap: wrap; }
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
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: var(--mk-ink);
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
    top: 0; left: 0; right: 0;
    height: 4px;
    background: var(--mk-accent);
}
.mk-site-card__logo-mark {
    width: 36px; height: 36px;
    border-radius: 8px;
    background: var(--mk-accent);
    display: flex; align-items: center; justify-content: center;
    font-size: 18px; font-weight: 800; color: #fff;
    margin-bottom: 8px;
}
.mk-site-card__name { font-size: 15px; font-weight: 800; color: #fff; margin: 0 0 2px; }
.mk-site-card__loc { font-size: 12px; color: rgba(255,255,255,0.65); margin: 0; }
.mk-site-card__actions {
    display: flex; gap: 6px; padding: 12px;
    border-bottom: 1px solid var(--mk-line-soft);
}
.mk-site-card__btn {
    flex: 1; padding: 8px 4px;
    border-radius: 8px; font-family: inherit;
    font-size: 12px; font-weight: 700;
    cursor: default; border: 1.5px solid;
}
.mk-site-card__btn--primary { background: var(--mk-accent); color: #fff; border-color: var(--mk-accent); }
.mk-site-card__btn--outline { background: transparent; color: var(--mk-ink); border-color: var(--mk-line); }
.mk-site-card__btn--book { background: transparent; color: var(--mk-ink); border-color: var(--mk-line); }
.mk-site-card__about { padding: 12px; border-bottom: 1px solid var(--mk-line-soft); }
.mk-site-card__reviews { padding: 12px; }
.mk-site-card__section-label { font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.8px; color: var(--mk-ink-soft); margin: 0 0 6px; }
.mk-site-card__about-text { font-size: 12px; color: var(--mk-ink-mid); line-height: 1.5; margin: 0; }
.mk-site-card__review-row { display: flex; align-items: center; gap: 6px; }
.mk-site-card__rating-num { font-size: 14px; font-weight: 700; color: var(--mk-ink); }
.mk-site-card__review-count { font-size: 12px; color: var(--mk-ink-soft); }

/* ── Section shared ──────────────────────────────────────────────────────────── */
.mk-section {
    padding: 100px 0;
}
.mk-section--panel {
    background: var(--mk-panel);
}
.mk-section-head { margin-bottom: 56px; }
.mk-section-head--center { text-align: center; }
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
.mk-section-head--center .mk-section-head__h2 { max-width: 720px; margin: 0 auto; }
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
    top: 24px; right: 24px;
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
    width: 44px; height: 44px;
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
    top: -14px; right: 28px;
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
.mk-plan__tier--light { color: rgba(255,255,255,0.65); }
.mk-plan__price-row { display: flex; align-items: baseline; gap: 8px; }
.mk-plan__price { font-size: 54px; font-weight: 900; letter-spacing: -2px; line-height: 1; }
.mk-plan__cadence { font-size: 16px; color: var(--mk-ink-soft); }
.mk-plan__cadence--light { color: rgba(255,255,255,0.55); }
.mk-plan__sub { font-size: 15px; color: var(--mk-ink-mid); line-height: 1.5; margin: 0; }
.mk-plan__sub--light { color: rgba(255,255,255,0.7); }
.mk-plan__feats { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 12px; flex: 1; }
.mk-plan__feat { display: flex; align-items: flex-start; gap: 12px; font-size: 15px; line-height: 1.45; }
.mk-plan__feat-check {
    width: 22px; height: 22px;
    border-radius: 50%;
    background: var(--mk-accent-soft);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    margin-top: 1px;
}
.mk-plan__feat-check--light {
    background: rgba(255,255,255,0.15);
}
.mk-pricing-note {
    text-align: center;
    font-size: 14px;
    color: var(--mk-ink-soft);
    margin-top: 24px;
}

/* ── FAQ ─────────────────────────────────────────────────────────────────────── */
.mk-faq { display: flex; flex-direction: column; gap: 10px; margin-top: 48px; }
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
    width: 36px; height: 36px;
    border-radius: 50%;
    background: var(--mk-panel);
    color: var(--mk-ink);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: transform 0.2s ease;
}
.mk-faq__chevron--open { transform: rotate(180deg); }
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
    color: rgba(255,255,255,0.72);
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
    color: rgba(255,255,255,0.55);
}
.mk-cta-band__trust span {
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

/* ── Footer ──────────────────────────────────────────────────────────────────── */
.mk-footer {
    background: var(--mk-ink);
    color: rgba(255,255,255,0.7);
    margin-top: 0;
}
.mk-footer__top {
    display: grid;
    grid-template-columns: 1.4fr 1fr;
    gap: 48px;
    padding: 72px 24px 48px;
    max-width: 1200px;
    margin: 0 auto;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}
.mk-footer__brand { max-width: 320px; }
.mk-footer__logo {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 16px;
}
.mk-footer__logo-mark {
    width: 34px; height: 34px;
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
    color: rgba(255,255,255,0.65);
    text-decoration: none;
    cursor: pointer;
    transition: color 0.1s ease;
}
.mk-footer__link:hover { color: #fff; }
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
    width: 10px; height: 10px;
    border-radius: 50%;
    background: #5BD46A;
}

/* ── Responsive ──────────────────────────────────────────────────────────────── */
@media (max-width: 900px) {
    .mk-nav__links { display: none; }
    .mk-steps-grid { grid-template-columns: 1fr; }
    .mk-features-grid { grid-template-columns: repeat(2, 1fr); }
    .mk-pricing-grid { grid-template-columns: 1fr; }
    .mk-cta-band { grid-template-columns: 1fr; }
    .mk-footer__top { grid-template-columns: 1fr; }
    .mk-footer__cols { grid-template-columns: repeat(2, 1fr); }
    .mk-before-after { grid-template-columns: 1fr; }
    .mk-ba-arrow {
        flex-direction: row;
        justify-content: center;
    }
    .mk-ba-arrow__label { display: none; }
}

@media (max-width: 640px) {
    .mk-hero { padding: 60px 0 0; }
    .mk-hero__h1 { letter-spacing: -1px; }
    .mk-search { flex-wrap: wrap; }
    .mk-search .mk-btn { width: 100%; justify-content: center; border-radius: 8px; }
    .mk-features-grid { grid-template-columns: 1fr; }
    .mk-before-after { padding: 24px; }
}
</style>
