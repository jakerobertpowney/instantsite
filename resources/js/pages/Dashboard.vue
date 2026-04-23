<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue';
import { Head } from '@inertiajs/vue3';
import { ref, provide, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { Toaster } from '@/components/ui/sonner';
import 'vue-sonner/style.css';
import Overview from '@/components/dashboard/Overview.vue';
import Site from '@/components/dashboard/Site.vue';
import Components from '@/components/dashboard/Components.vue';
import Settings from '@/components/dashboard/Settings.vue';
import Messages from '@/components/dashboard/Messages.vue';
import AccountSettings from '@/components/dashboard/AccountSettings.vue';
import {
    Home, Globe, Pencil, Search, HelpCircle,
    ExternalLink, Menu, X, ChevronRight, Inbox, User,
} from 'lucide-vue-next';

const props = defineProps({
    site: Object,
    appDomain: String,
    isPremium: Boolean,
    serverIp: String,
    submissions: Array,
    unreadCount: Number,
});

provide('site', props.site);
provide('appDomain', props.appDomain);
provide('isPremium', props.isPremium);
provide('serverIp', props.serverIp);

const page = usePage();

const activeNav = ref<'home' | 'address' | 'edit' | 'seo' | 'help' | 'messages' | 'account'>('home');
const mobileMenuOpen = ref(false);

const firstName = computed(() => {
    const name = (page.props.auth?.user?.name as string | undefined)?.trim();
    return name ? name.split(' ')[0] : null;
});

const userEmail = computed(() => (page.props.auth?.user as any)?.email ?? '');
const userName = computed(() => (page.props.auth?.user as any)?.name ?? '');

const siteUrl = computed(() => {
    const site = props.site as any;
    if (!site) return null;
    if (site.domain_type === 'custom' && site.custom_domain) return `https://${site.custom_domain}`;
    if (site.domain_type === 'subdomain' && site.subdomain) return `https://${site.subdomain}.${props.appDomain}`;
    return null;
});

const isLive = computed(() => !!siteUrl.value && (props.site as any)?.domain_type !== 'draft');

const navItems = [
    { id: 'home',     label: 'Home',             hint: 'Your site at a glance',              icon: Home,       badge: null },
    { id: 'address',  label: 'Web address',      hint: "What your site's link is",           icon: Globe,      badge: null },
    { id: 'edit',     label: 'Edit my site',     hint: 'Change words, photos, colours',      icon: Pencil,     badge: null },
    { id: 'messages', label: 'Messages',         hint: 'From people on your site',           icon: Inbox,      badge: 'unread' },
    { id: 'seo',      label: 'Search & sharing', hint: 'How Google sees your site',          icon: Search,     badge: null },
    { id: 'help',     label: 'Help & guides',    hint: 'How to do things',                   icon: HelpCircle, badge: null },
    { id: 'account',  label: 'Account',          hint: 'Your details and sign-in',           icon: User,       badge: null },
] as const;

type NavId = (typeof navItems)[number]['id'];

const screenTitles: Record<NavId, { title: string; subtitle: string }> = {
    home:     { title: 'Home',             subtitle: 'Your site at a glance'          },
    address:  { title: 'Web address',      subtitle: 'Choose where people find you'   },
    edit:     { title: 'Edit my site',     subtitle: 'Change words, photos, colours'  },
    messages: { title: 'Messages',         subtitle: 'Contact form submissions'       },
    seo:      { title: 'Search & sharing', subtitle: 'How Google sees your site'      },
    help:     { title: 'Help & guides',    subtitle: 'Answers to common questions'    },
    account:  { title: 'Account',          subtitle: 'Your details and sign-in'       },
};

function navigate(id: NavId) {
    activeNav.value = id;
    mobileMenuOpen.value = false;
    window.scrollTo(0, 0);
}

const unreadBadge = computed(() => (props.unreadCount ?? 0) > 0 ? props.unreadCount : null);
</script>

<template>
    <Head title="Dashboard" />
    <Toaster />

    <!-- Full-screen shell -->
    <div class="db-shell">

        <!-- ── Sidebar (desktop) ────────────────────────────────────── -->
        <aside class="db-sidebar" :class="{ 'db-sidebar--mobile-open': mobileMenuOpen }">
            <!-- Logo -->
            <div class="db-sidebar__brand">
                <AppLogo />
            </div>

            <!-- Nav items -->
            <nav class="db-sidebar__nav">
                <template v-for="item in navItems" :key="item.id">
                    <!-- Help item links directly to the help page -->
                    <a
                        v-if="item.id === 'help'"
                        href="/help"
                        class="db-nav-item"
                    >
                        <span class="db-nav-item__icon">
                            <component :is="item.icon" :size="22" />
                        </span>
                        <span class="db-nav-item__text">
                            <span class="db-nav-item__label-row">
                                <span class="db-nav-item__label">{{ item.label }}</span>
                            </span>
                            <span class="db-nav-item__hint">{{ item.hint }}</span>
                        </span>
                    </a>
                    <button
                        v-else
                        class="db-nav-item"
                        :class="{ 'db-nav-item--active': activeNav === item.id }"
                        @click="navigate(item.id)"
                    >
                        <span class="db-nav-item__icon">
                            <component :is="item.icon" :size="22" />
                        </span>
                        <span class="db-nav-item__text">
                            <span class="db-nav-item__label-row">
                                <span class="db-nav-item__label">{{ item.label }}</span>
                                <span
                                    v-if="item.badge === 'unread' && unreadBadge"
                                    class="db-nav-badge"
                                >{{ unreadBadge }}</span>
                            </span>
                            <span class="db-nav-item__hint">{{ item.hint }}</span>
                        </span>
                    </button>
                </template>
            </nav>

            <!-- Bottom: sign-in info -->
            <div class="db-sidebar__footer">
                <div class="db-account-block">
                    <div class="db-account-label">Signed in as</div>
                    <div class="db-account-name">{{ firstName || 'You' }}</div>
                    <div class="db-account-email">{{ userEmail }}</div>
                </div>
            </div>
        </aside>

        <!-- Mobile overlay -->
        <div
            v-if="mobileMenuOpen"
            class="db-mobile-overlay"
            @click="mobileMenuOpen = false"
        />

        <!-- ── Main content ──────────────────────────────────────────── -->
        <div class="db-main">

            <!-- Top bar -->
            <header class="db-topbar">
                <!-- Mobile hamburger -->
                <button class="db-hamburger" @click="mobileMenuOpen = !mobileMenuOpen" aria-label="Open menu">
                    <X v-if="mobileMenuOpen" :size="22" />
                    <Menu v-else :size="22" />
                </button>

                <div class="db-topbar__titles">
                    <h1 class="db-topbar__title">{{ screenTitles[activeNav].title }}</h1>
                    <p class="db-topbar__sub">{{ screenTitles[activeNav].subtitle }}</p>
                </div>

                <!-- Preview link (show when site is live) -->
                <a
                    v-if="isLive && siteUrl"
                    :href="siteUrl"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="db-preview-btn"
                >
                    <ExternalLink :size="16" />
                    <span>Preview my site</span>
                </a>
            </header>

            <!-- Screen content -->
            <div class="db-content">
                <Overview       v-if="activeNav === 'home'"     @navigate="navigate" />
                <Site           v-else-if="activeNav === 'address'" />
                <Components     v-else-if="activeNav === 'edit'" />
                <Messages       v-else-if="activeNav === 'messages'" :submissions="(submissions as any)" />
                <Settings       v-else-if="activeNav === 'seo'" />
                <AccountSettings v-else-if="activeNav === 'account'" :user-name="userName" :user-email="userEmail" />

                <!-- Help screen — simple placeholder linking to Help pages -->
                <div v-else-if="activeNav === 'help'" class="db-help-screen">
                    <div class="db-card db-card--pad">
                        <h2 class="db-section-title">Help & guides</h2>
                        <p class="db-section-sub">Need a hand? Browse our guides below or get in touch.</p>
                        <div class="db-help-links">
                            <a href="/help" class="db-help-link">
                                <span class="db-help-link__label">Browse all guides</span>
                                <ChevronRight :size="18" />
                            </a>
                            <a href="mailto:support@321sites.com" class="db-help-link">
                                <span class="db-help-link__label">Email support</span>
                                <ChevronRight :size="18" />
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style>
/* ── Dashboard shell tokens ───────────────────────────────────────────── */
:root {
    /* Brand tokens (--db-*) */
    --db-bg:          #f8fafc;
    --db-surface:     #ffffff;
    --db-ink:         #0f172a;
    --db-ink-mid:     #3d4a5c;
    --db-ink-soft:    #64748b;
    --db-line:        #dde1e8;
    --db-line-soft:   #e8ecf1;
    --db-panel:       #edf1f8;
    --db-accent:      #1e66f5;
    --db-accent-fg:   #ffffff;
    --db-accent-soft: #e6eefe;
    --db-success:     #15803d;
    --db-warning:     #b45309;
    --db-danger:      #b91c1c;

    /* shadcn-vue overrides — ensures all shadcn components use brand tokens */
    --primary:            #1e66f5;
    --primary-foreground: #ffffff;
    --radius:             0.75rem;
    --border:             #dde1e8;
    --ring:               #1e66f5;
    --background:         #ffffff;
    --foreground:         #0f172a;
    --muted:              #edf1f8;
    --muted-foreground:   #64748b;
    --destructive:        #b91c1c;
    --destructive-foreground: #ffffff;
}

/* ── Shell ────────────────────────────────────────────────────────────── */
.db-shell {
    display: flex;
    min-height: 100vh;
    background: var(--db-bg);
    font-family: 'Inter', 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
    color: var(--db-ink);
}

/* ── Sidebar ──────────────────────────────────────────────────────────── */
.db-sidebar {
    width: 280px;
    min-width: 280px;
    background: var(--db-surface);
    border-right: 1.5px solid var(--db-line);
    display: flex;
    flex-direction: column;
    height: 100vh;
    position: sticky;
    top: 0;
    overflow-y: auto;
    z-index: 30;
    flex-shrink: 0;
}

.db-sidebar__brand {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 22px 22px 18px;
    border-bottom: 1px solid var(--db-line-soft);
}

.db-brand-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: var(--db-ink);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.db-brand-name {
    font-size: 17px;
    font-weight: 800;
    color: var(--db-ink);
    letter-spacing: -0.3px;
    line-height: 1.2;
}

.db-brand-sub {
    font-size: 13px;
    color: var(--db-ink-soft);
    line-height: 1.2;
}

.db-sidebar__nav {
    padding: 14px;
    display: flex;
    flex-direction: column;
    gap: 4px;
    flex: 1;
}

.db-nav-item {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 16px;
    border-radius: 10px;
    cursor: pointer;
    text-align: left;
    background: transparent;
    border: 1.5px solid transparent;
    color: var(--db-ink);
    font-family: inherit;
    width: 100%;
    transition: background 0.1s ease, border-color 0.1s ease;
}

.db-nav-item:hover:not(.db-nav-item--active) {
    background: var(--db-panel);
}

.db-nav-item--active {
    background: var(--db-accent-soft);
    border-color: var(--db-accent);
    color: var(--db-accent);
}

.db-nav-item__icon {
    flex-shrink: 0;
    display: flex;
    align-items: center;
}

.db-nav-item__text {
    display: flex;
    flex-direction: column;
    flex: 1;
    min-width: 0;
}

.db-nav-item__label-row {
    display: flex;
    align-items: center;
    gap: 8px;
}

.db-nav-item__label {
    font-size: 15px;
    font-weight: 700;
    line-height: 1.2;
}

.db-nav-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 20px;
    height: 20px;
    padding: 0 6px;
    border-radius: 100px;
    background: var(--db-accent);
    color: var(--db-accent-fg);
    font-size: 11px;
    font-weight: 700;
    line-height: 1;
}

.db-nav-item--active .db-nav-badge {
    background: var(--db-accent);
    color: var(--db-accent-fg);
}

.db-nav-item__hint {
    font-size: 12px;
    color: var(--db-ink-soft);
    font-weight: 500;
    margin-top: 2px;
    line-height: 1.3;
}

.db-nav-item--active .db-nav-item__hint {
    color: var(--db-accent);
    opacity: 0.8;
}

.db-sidebar__footer {
    padding: 14px;
    border-top: 1px solid var(--db-line-soft);
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.db-account-block {
    padding: 14px;
    border-radius: 10px;
    background: var(--db-panel);
}

.db-account-label {
    font-size: 12px;
    color: var(--db-ink-soft);
    font-weight: 600;
}

.db-account-name {
    font-size: 15px;
    font-weight: 700;
    color: var(--db-ink);
    margin-top: 2px;
}

.db-account-email {
    font-size: 13px;
    color: var(--db-ink-soft);
    margin-top: 2px;
    word-break: break-all;
}

/* ── Mobile sidebar overlay ───────────────────────────────────────────── */
.db-mobile-overlay {
    display: none;
}

/* ── Top bar ──────────────────────────────────────────────────────────── */
.db-main {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    min-height: 100vh;
}

.db-topbar {
    padding: 22px 32px;
    background: var(--db-surface);
    border-bottom: 1.5px solid var(--db-line);
    display: flex;
    align-items: center;
    gap: 20px;
    position: sticky;
    top: 0;
    z-index: 20;
}

.db-hamburger {
    display: none;
    background: transparent;
    border: none;
    cursor: pointer;
    color: var(--db-ink);
    padding: 4px;
    border-radius: 8px;
    flex-shrink: 0;
}

.db-topbar__titles {
    flex: 1;
    min-width: 0;
}

.db-topbar__title {
    font-size: 24px;
    font-weight: 800;
    color: var(--db-ink);
    letter-spacing: -0.5px;
    line-height: 1.2;
    margin: 0;
}

.db-topbar__sub {
    font-size: 15px;
    color: var(--db-ink-soft);
    margin: 2px 0 0;
}

.db-preview-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    height: 44px;
    padding: 0 18px;
    border-radius: 10px;
    border: 1.5px solid var(--db-line);
    background: var(--db-surface);
    color: var(--db-ink);
    font-family: inherit;
    font-size: 15px;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    white-space: nowrap;
    flex-shrink: 0;
    transition: background 0.1s ease;
}

.db-preview-btn:hover {
    background: var(--db-panel);
}

/* ── Content area ─────────────────────────────────────────────────────── */
.db-content {
    flex: 1;
    padding: 28px 32px 48px;
    max-width: 1100px;
    width: 100%;
}

/* ── Shared card / section primitives ─────────────────────────────────── */
.db-card {
    background: var(--db-surface);
    border: 1.5px solid var(--db-line);
    border-radius: 14px;
}

.db-card--pad {
    padding: 24px;
}

.db-section-title {
    font-size: 20px;
    font-weight: 700;
    color: var(--db-ink);
    letter-spacing: -0.2px;
    margin: 0 0 4px;
}

.db-section-sub {
    font-size: 15px;
    color: var(--db-ink-soft);
    margin: 0 0 20px;
}

/* ── Help screen ──────────────────────────────────────────────────────── */
.db-help-screen {
    max-width: 600px;
}

.db-help-links {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.db-help-link {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px;
    border: 1.5px solid var(--db-line);
    border-radius: 12px;
    background: var(--db-surface);
    color: var(--db-ink);
    text-decoration: none;
    font-weight: 600;
    font-size: 16px;
    transition: background 0.1s ease;
}

.db-help-link:hover {
    background: var(--db-panel);
}

/* ── Responsive / Mobile ──────────────────────────────────────────────── */
@media (max-width: 768px) {
    .db-sidebar {
        position: fixed;
        left: -280px;
        top: 0;
        height: 100vh;
        transition: left 0.2s ease;
        z-index: 40;
    }

    .db-sidebar--mobile-open {
        left: 0;
    }

    .db-mobile-overlay {
        display: block;
        position: fixed;
        inset: 0;
        background: rgba(15, 17, 20, 0.5);
        z-index: 35;
    }

    .db-hamburger {
        display: flex;
    }

    .db-topbar {
        padding: 18px 20px;
    }

    .db-content {
        padding: 20px 20px 40px;
    }

    .db-topbar__title {
        font-size: 20px;
    }
}
</style>
