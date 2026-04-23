<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';

defineProps<{
    title: string;
    eyebrow?: string;
    description?: string;
    pageTitle?: string;
}>();

// ── Auth detection ─────────────────────────────────────────────────────────────
const page = usePage();
const isLoggedIn = computed(() => !!(page.props.auth as any)?.user);

// ── Nav scroll ─────────────────────────────────────────────────────────────────
const scrolled = ref(false);
const handleScroll = () => { scrolled.value = window.scrollY > 60; };

onMounted(() => window.addEventListener('scroll', handleScroll, { passive: true }));
onUnmounted(() => window.removeEventListener('scroll', handleScroll));
</script>

<template>
    <Head :title="(pageTitle || title) + ' — 321Sites Help'">
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="" />
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet" />
    </Head>

    <div class="hl-page">

        <!-- ══════════════════════════════════════════════
             NAV
        ══════════════════════════════════════════════ -->
        <header class="hl-nav" :class="{ 'hl-nav--scrolled': scrolled }">
            <div class="hl-container hl-nav__inner">
                <!-- Logo -->
                <a href="/" class="hl-nav__logo">
                    <AppLogo />
                </a>

                <!-- Nav links -->
                <nav class="hl-nav__links">
                    <Link href="/" class="hl-nav__link">Home</Link>
                    <Link href="/help" class="hl-nav__link hl-nav__link--active">Help</Link>
                    <Link href="/#pricing" class="hl-nav__link">Pricing</Link>
                </nav>

                <!-- CTA — auth-aware -->
                <div class="hl-nav__cta">
                    <template v-if="isLoggedIn">
                        <Link href="/dashboard" class="hl-btn hl-btn--primary hl-btn--sm">Dashboard</Link>
                    </template>
                    <template v-else>
                        <Link href="/login" class="hl-nav__signin">Sign in</Link>
                        <Link href="/register" class="hl-btn hl-btn--primary hl-btn--sm">Get started free</Link>
                    </template>
                </div>
            </div>
        </header>

        <!-- ══════════════════════════════════════════════
             PAGE HERO
        ══════════════════════════════════════════════ -->
        <div class="hl-hero">
            <div class="hl-container">
                <p v-if="eyebrow" class="hl-hero__eyebrow">{{ eyebrow }}</p>
                <h1 class="hl-hero__title">{{ title }}</h1>
                <p v-if="description" class="hl-hero__desc">{{ description }}</p>
            </div>
        </div>

        <!-- ══════════════════════════════════════════════
             MAIN CONTENT
        ══════════════════════════════════════════════ -->
        <main class="hl-main">
            <div class="hl-container hl-main__inner">
                <slot />
            </div>
        </main>

        <!-- ══════════════════════════════════════════════
             FOOTER
        ══════════════════════════════════════════════ -->
        <footer class="hl-footer">
            <div class="hl-container hl-footer__inner">
                <div class="hl-footer__brand">
                    <div class="hl-footer__logo" style="color: #ffffff;">
                        <AppLogo />
                    </div>
                    <p class="hl-footer__tagline">Your business, online in minutes.</p>
                    <p class="hl-footer__support">
                        Need help?
                        <a href="mailto:help@321sites.com" class="hl-footer__support-link">help@321sites.com</a>
                    </p>
                </div>

                <div class="hl-footer__cols">
                    <div class="hl-footer__col">
                        <p class="hl-footer__col-label">Product</p>
                        <Link href="/#how" class="hl-footer__col-link">How it works</Link>
                        <Link href="/#features" class="hl-footer__col-link">Features</Link>
                        <Link href="/#pricing" class="hl-footer__col-link">Pricing</Link>
                    </div>
                    <div class="hl-footer__col">
                        <p class="hl-footer__col-label">Help</p>
                        <Link href="/help" class="hl-footer__col-link">Help centre</Link>
                        <a href="mailto:help@321sites.com" class="hl-footer__col-link">Email support</a>
                    </div>
                    <div class="hl-footer__col">
                        <p class="hl-footer__col-label">Legal</p>
                        <Link href="/terms" class="hl-footer__col-link">Terms</Link>
                        <Link href="/privacy" class="hl-footer__col-link">Privacy</Link>
                    </div>
                </div>
            </div>

            <div class="hl-container hl-footer__bottom">
                <span class="hl-footer__status">
                    <span class="hl-footer__status-dot" aria-hidden="true"></span>
                    All systems operational
                </span>
                <span>© {{ new Date().getFullYear() }} 321Sites. All rights reserved.</span>
            </div>
        </footer>
    </div>
</template>

<style>
/* ── Design tokens scoped to the help layout ────────────────────────────────── */
/* Using .hl-page instead of :root so these don't bleed into other pages        */
.hl-page {
    --mk-bg:          #ffffff;
    --mk-surface:     #ffffff;
    --mk-ink:         #0f172a;
    --mk-ink-mid:     #3d4a5c;
    --mk-ink-soft:    #64748b;
    --mk-line:        #dde1e8;
    --mk-line-soft:   #e8ecf1;
    --mk-panel:       #edf1f8;
    --mk-accent:      #1e66f5;
    --mk-accent-soft: #e6eefe;
    --mk-accent-fg:   #ffffff;
}
</style>

<style scoped>
/* ── Base ───────────────────────────────────────────────────────────────────── */
.hl-page {
    font-family: 'Inter', ui-sans-serif, system-ui, sans-serif;
    background: var(--mk-bg);
    color: var(--mk-ink);
    -webkit-font-smoothing: antialiased;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}

/* ── Container ──────────────────────────────────────────────────────────────── */
.hl-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 24px;
}

/* ── Buttons ────────────────────────────────────────────────────────────────── */
.hl-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    height: 44px;
    padding: 0 20px;
    border-radius: 10px;
    font-family: inherit;
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
    border: 1.5px solid transparent;
    text-decoration: none;
    transition: opacity 0.1s ease;
    white-space: nowrap;
    flex-shrink: 0;
}
.hl-btn--primary {
    background: var(--mk-accent);
    color: var(--mk-accent-fg);
    border-color: var(--mk-accent);
}
.hl-btn--primary:hover { opacity: 0.9; }
.hl-btn--sm {
    height: 38px;
    font-size: 14px;
    padding: 0 16px;
}

/* ── Nav ────────────────────────────────────────────────────────────────────── */
.hl-nav {
    position: sticky;
    top: 0;
    z-index: 40;
    background: rgba(255,255,255,0.92);
    backdrop-filter: saturate(140%) blur(8px);
    -webkit-backdrop-filter: saturate(140%) blur(8px);
    border-bottom: 1px solid transparent;
    transition: border-color 0.2s ease;
}
.hl-nav--scrolled {
    border-bottom-color: var(--mk-line-soft);
}
.hl-nav__inner {
    display: flex;
    align-items: center;
    gap: 20px;
    padding-top: 14px;
    padding-bottom: 14px;
}
.hl-nav__logo {
    display: flex;
    align-items: center;
    gap: 10px;
    text-decoration: none;
    color: var(--mk-ink);
    flex-shrink: 0;
}
.hl-nav__logo-mark {
    width: 34px;
    height: 34px;
    border-radius: 8px;
    background: var(--mk-ink);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.hl-nav__logo-name {
    font-size: 20px;
    font-weight: 800;
    letter-spacing: -0.4px;
}
.hl-nav__links {
    display: flex;
    align-items: center;
    gap: 4px;
    flex: 1;
    margin-left: 24px;
}
.hl-nav__link {
    padding: 8px 12px;
    font-size: 15px;
    font-weight: 600;
    color: var(--mk-ink-mid);
    border-radius: 8px;
    text-decoration: none;
    transition: background 0.1s ease, color 0.1s ease;
}
.hl-nav__link:hover,
.hl-nav__link--active {
    background: var(--mk-panel);
    color: var(--mk-ink);
}
.hl-nav__cta {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-shrink: 0;
}
.hl-nav__signin {
    height: 38px;
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
.hl-nav__signin:hover { background: var(--mk-panel); }

/* ── Hero ───────────────────────────────────────────────────────────────────── */
.hl-hero {
    padding: 64px 0 56px;
    border-bottom: 1px solid var(--mk-line-soft);
}
.hl-hero__eyebrow {
    font-size: 13px;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: var(--mk-accent);
    margin-bottom: 14px;
}
.hl-hero__title {
    font-size: clamp(32px, 5vw, 52px);
    font-weight: 900;
    letter-spacing: -1.5px;
    line-height: 1.1;
    color: var(--mk-ink);
    max-width: 760px;
}
.hl-hero__desc {
    margin-top: 20px;
    font-size: 18px;
    line-height: 1.7;
    color: var(--mk-ink-mid);
    max-width: 680px;
}

/* ── Main ───────────────────────────────────────────────────────────────────── */
.hl-main {
    flex: 1;
    padding: 64px 0 80px;
}
.hl-main__inner {
    display: flex;
    flex-direction: column;
    gap: 64px;
}

/* ── Footer ─────────────────────────────────────────────────────────────────── */
.hl-footer {
    background: var(--mk-ink);
    color: rgba(255,255,255,0.55);
    font-size: 14px;
}
.hl-footer__inner {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 64px;
    padding-top: 64px;
    padding-bottom: 48px;
}
.hl-footer__brand {
    display: flex;
    flex-direction: column;
    gap: 16px;
}
.hl-footer__logo {
    display: flex;
    align-items: center;
    gap: 10px;
}
.hl-footer__logo-mark {
    width: 28px;
    height: 28px;
    border-radius: 6px;
    background: rgba(255,255,255,0.15);
    display: flex;
    align-items: center;
    justify-content: center;
}
.hl-footer__logo-name {
    font-size: 16px;
    font-weight: 800;
    color: #fff;
    letter-spacing: -0.3px;
}
.hl-footer__tagline {
    font-size: 14px;
    line-height: 1.6;
}
.hl-footer__support {
    font-size: 14px;
}
.hl-footer__support-link {
    color: rgba(255,255,255,0.8);
    text-decoration: underline;
    text-underline-offset: 3px;
}
.hl-footer__support-link:hover { color: #fff; }
.hl-footer__cols {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 40px;
}
.hl-footer__col {
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.hl-footer__col-label {
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: rgba(255,255,255,0.4);
    margin-bottom: 4px;
}
.hl-footer__col-link {
    font-size: 14px;
    color: rgba(255,255,255,0.6);
    text-decoration: none;
    transition: color 0.1s ease;
}
.hl-footer__col-link:hover { color: #fff; }
.hl-footer__bottom {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-top: 20px;
    padding-bottom: 28px;
    border-top: 1px solid rgba(255,255,255,0.08);
    color: rgba(255,255,255,0.35);
    font-size: 13px;
}
.hl-footer__status {
    display: flex;
    align-items: center;
    gap: 7px;
}
.hl-footer__status-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #22C55E;
    flex-shrink: 0;
}

/* ── Responsive ─────────────────────────────────────────────────────────────── */
@media (max-width: 900px) {
    .hl-nav__links { display: none; }
    .hl-footer__inner { grid-template-columns: 1fr; gap: 40px; }
    .hl-footer__cols { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 640px) {
    .hl-hero { padding: 48px 0 40px; }
    .hl-footer__cols { grid-template-columns: 1fr; }
    .hl-footer__bottom { flex-direction: column; align-items: flex-start; gap: 8px; }
    .hl-nav__signin { display: none; }
}
</style>
