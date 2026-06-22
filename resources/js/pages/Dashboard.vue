<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue';
import { Head } from '@inertiajs/vue3';
import { ref, provide, computed, reactive, watch } from 'vue';
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
    ExternalLink, Menu, X, ChevronRight, Inbox, User, Sparkles,
} from 'lucide-vue-next';
import billing from '@/routes/billing';

const props = defineProps({
    site: Object,
    appDomain: String,
    isPremium: Boolean,
    serverIp: String,
    submissions: Array,
    unreadCount: Number,
});

// Provide a reactive `site` that stays in sync with the Inertia prop.
// `provide` captures a value once, so handing it `props.site` directly would
// give children a stale snapshot — after saving domain/settings the props
// update but injected consumers wouldn't, forcing a manual page refresh.
// Mirroring the latest prop into a reactive object keeps every child live.
const site = reactive<Record<string, any>>({ ...(props.site ?? {}) });
watch(
    () => props.site,
    (val) => {
        Object.keys(site).forEach((key) => delete site[key]);
        if (val) Object.assign(site, val);
    },
    { deep: true },
);

provide('site', site);
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
    if (site.domain_type === 'custom' && site.custom_domain) return `https://${site.custom_domain}`;
    if (site.domain_type === 'subdomain' && site.subdomain) return `https://${site.subdomain}.${props.appDomain}`;
    return null;
});

const isLive = computed(() => !!siteUrl.value && site.domain_type !== 'draft');

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

const editInitialSection = ref<string | undefined>(undefined);

function navigate(id: NavId, section?: string) {
    activeNav.value = id;
    mobileMenuOpen.value = false;
    window.scrollTo(0, 0);
    if (id === 'edit') {
        editInitialSection.value = section;
    }
}

const unreadBadge = computed(() => (props.unreadCount ?? 0) > 0 ? props.unreadCount : null);
const checkoutUrl = billing.checkout.url();
</script>

<template>
    <Head title="Dashboard" />
    <Toaster />

    <!-- Full-screen shell -->
    <div class="flex min-h-screen bg-brand-bg font-[Inter,ui-sans-serif] text-brand-ink">

        <!-- ── Sidebar (desktop) ────────────────────────────────────── -->
        <aside
            class="w-[280px] min-w-[280px] bg-brand-surface border-r border-brand-line flex flex-col h-screen sticky top-0 overflow-y-auto z-30 shrink-0 md:translate-x-0 md:static md:h-screen"
            :class="{ 'translate-x-0': mobileMenuOpen, '-translate-x-[280px]': !mobileMenuOpen }"
            style="transition: transform 0.2s ease"
        >
            <!-- Logo -->
            <div class="flex items-center gap-3 px-5 py-5 border-b border-brand-line-soft">
                <AppLogo />
            </div>

            <!-- Nav items -->
            <nav class="px-3.5 py-3.5 flex flex-col gap-1 flex-1">
                <template v-for="item in navItems" :key="item.id">
                    <!-- Help item links directly to the help page -->
                    <a
                        v-if="item.id === 'help'"
                        href="/help"
                        class="flex items-center gap-3.5 px-4 py-3.5 rounded-[10px] cursor-pointer text-left w-full font-[inherit] border-[1.5px] border-transparent transition-colors bg-transparent hover:bg-brand-panel"
                    >
                        <span class="shrink-0 flex items-center">
                            <component :is="item.icon" :size="22" />
                        </span>
                        <span class="flex flex-col flex-1 min-w-0">
                            <span class="flex items-center gap-2">
                                <span class="text-[15px] font-bold leading-tight">{{ item.label }}</span>
                            </span>
                            <span class="text-xs text-brand-ink-soft font-medium mt-0.5 leading-tight">{{ item.hint }}</span>
                        </span>
                    </a>
                    <button
                        v-else
                        class="flex items-center gap-3.5 px-4 py-3.5 rounded-[10px] cursor-pointer text-left w-full font-[inherit] border-[1.5px] border-transparent transition-colors"
                        :class="activeNav === item.id ? 'bg-brand-blue-soft border-brand-blue text-brand-blue' : 'bg-transparent hover:bg-brand-panel'"
                        @click="navigate(item.id)"
                    >
                        <span class="shrink-0 flex items-center">
                            <component :is="item.icon" :size="22" />
                        </span>
                        <span class="flex flex-col flex-1 min-w-0">
                            <span class="flex items-center gap-2">
                                <span class="text-[15px] font-bold leading-tight">{{ item.label }}</span>
                                <span
                                    v-if="item.badge === 'unread' && unreadBadge"
                                    class="inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 rounded-full bg-brand-blue text-white text-[11px] font-bold leading-none"
                                >{{ unreadBadge }}</span>
                            </span>
                            <span
                                class="text-xs font-medium mt-0.5 leading-tight"
                                :class="activeNav === item.id ? 'text-brand-blue opacity-80' : 'text-brand-ink-soft'"
                            >{{ item.hint }}</span>
                        </span>
                    </button>
                </template>
            </nav>

            <!-- Upgrade to Pro (free users only) -->
            <div v-if="!isPremium" class="px-3.5 pb-3">
                <div class="border-[1.5px] border-[#F0E6C8] rounded-[12px] bg-gradient-to-br from-[#FFFDF5] to-[#FFF8E6] p-4 flex flex-col gap-3">
                    <div class="flex flex-col gap-1">
                        <div class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-slate-950 text-[#F6D860] text-[11px] font-black tracking-wide uppercase w-fit">
                            <Sparkles :size="11" />
                            Upgrade to Pro
                        </div>
                        <p class="text-sm font-bold text-slate-950 m-0 mt-1 tracking-[-0.1px] leading-snug">Take your site to the next level</p>
                        <p class="text-xs text-slate-600 m-0 leading-[1.5]">Custom domain, contact forms, Google Analytics, and more.</p>
                    </div>
                    <a :href="checkoutUrl" class="inline-flex items-center justify-center gap-1.5 w-full h-10 rounded-lg bg-slate-950 text-[#F6D860] font-bold text-sm cursor-pointer border-none transition-opacity duration-150 hover:opacity-88 no-underline">
                        Upgrade <ChevronRight :size="14" />
                    </a>
                </div>
            </div>

            <!-- Bottom: sign-in info -->
            <div class="px-3.5 py-3.5 border-t border-brand-line-soft flex flex-col gap-2">
                <div class="px-3.5 py-3.5 rounded-[10px] bg-brand-panel">
                    <div class="text-xs text-brand-ink-soft font-semibold">Signed in as</div>
                    <div class="text-[15px] font-bold text-brand-ink mt-0.5">{{ firstName || 'You' }}</div>
                    <div class="text-xs text-brand-ink-soft mt-0.5 break-all">{{ userEmail }}</div>
                </div>
            </div>
        </aside>

        <!-- Mobile overlay -->
        <div
            v-if="mobileMenuOpen"
            class="fixed inset-0 bg-[rgba(15,17,20,0.5)] z-[35]"
            @click="mobileMenuOpen = false"
        />

        <!-- ── Main content ──────────────────────────────────────────── -->
        <div class="flex-1 min-w-0 flex flex-col min-h-screen">

            <!-- Top bar -->
            <header class="px-8 py-5 bg-brand-surface border-b-[1.5px] border-brand-line flex items-center gap-5 sticky top-0 z-20 md:px-8 md:py-5">
                <!-- Mobile hamburger -->
                <button
                    class="flex md:hidden flex-col justify-center items-center gap-[5px] w-10 h-10 border-none bg-transparent cursor-pointer p-1 rounded-lg shrink-0 text-brand-ink"
                    @click="mobileMenuOpen = !mobileMenuOpen"
                    aria-label="Open menu"
                >
                    <X v-if="mobileMenuOpen" :size="22" />
                    <Menu v-else :size="22" />
                </button>

                <div class="flex-1 min-w-0 md:flex-1 md:min-w-0">
                    <h1 class="text-2xl font-extrabold text-brand-ink tracking-tight leading-tight m-0 md:text-2xl md:whitespace-normal md:overflow-visible md:text-ellipsis">
                        {{ screenTitles[activeNav].title }}
                    </h1>
                    <p class="text-[15px] text-brand-ink-soft mt-0.5 hidden md:block">{{ screenTitles[activeNav].subtitle }}</p>
                </div>

                <!-- Preview link (show when site is live) -->
                <a
                    v-if="isLive && siteUrl"
                    :href="siteUrl"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex items-center justify-center md:justify-start gap-2 h-11 px-4 md:px-4 rounded-[10px] border-[1.5px] border-brand-line bg-brand-surface text-brand-ink font-[inherit] text-[15px] font-semibold no-underline shrink-0 transition-colors hover:bg-brand-panel md:w-auto md:h-11 md:p-0 md:justify-start w-10 h-10 p-0 md:px-4"
                >
                    <ExternalLink :size="16" />
                    <span class="hidden md:inline">Preview my site</span>
                </a>
            </header>

            <!-- Screen content -->
            <div class="flex-1 p-[28px_32px_48px] max-w-[1100px] w-full md:p-[28px_32px_48px] sm:p-[16px_16px_40px]">
                <Overview       v-if="activeNav === 'home'"     @navigate="navigate" />
                <Site           v-else-if="activeNav === 'address'" />
                <Components     v-else-if="activeNav === 'edit'" :initial-section="editInitialSection" />
                <Messages       v-else-if="activeNav === 'messages'" :submissions="(submissions as any)" />
                <Settings       v-else-if="activeNav === 'seo'" />
                <AccountSettings v-else-if="activeNav === 'account'" :user-name="userName" :user-email="userEmail" />

                <!-- Help screen — simple placeholder linking to Help pages -->
                <div v-else-if="activeNav === 'help'" class="max-w-[600px]">
                    <div class="bg-brand-surface border-[1.5px] border-brand-line rounded-[14px] p-6">
                        <h2 class="text-xl font-bold text-brand-ink tracking-tight m-0 mb-1">Help & guides</h2>
                        <p class="text-[15px] text-brand-ink-soft m-0 mb-5">Need a hand? Browse our guides below or get in touch.</p>
                        <div class="flex flex-col gap-2">
                            <a href="/help" class="flex items-center justify-between px-4 py-4 border-[1.5px] border-brand-line rounded-lg bg-brand-surface text-brand-ink no-underline font-semibold text-base transition-colors hover:bg-brand-panel">
                                <span>Browse all guides</span>
                                <ChevronRight :size="18" />
                            </a>
                            <a href="mailto:support@321sites.com" class="flex items-center justify-between px-4 py-4 border-[1.5px] border-brand-line rounded-lg bg-brand-surface text-brand-ink no-underline font-semibold text-base transition-colors hover:bg-brand-panel">
                                <span>Email support</span>
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
</style>
