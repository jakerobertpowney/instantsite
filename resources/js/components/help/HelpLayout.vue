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

    <div class="min-h-screen bg-white text-[#0f172a] antialiased flex flex-col" style="font-family: 'Inter', ui-sans-serif, system-ui, sans-serif;">

        <!-- ══════════════════════════════════════════════
             NAV
        ══════════════════════════════════════════════ -->
        <header class="sticky top-0 z-40 bg-[rgba(255,255,255,0.92)] backdrop-blur-[8px] -webkit-backdrop-filter backdrop-blur-[8px] border-b border-transparent transition-colors" :class="{ 'border-b-[#e8ecf1]': scrolled }">
            <div class="max-w-screen-xl mx-auto px-6 flex items-center gap-5 py-3.5">
                <!-- Logo -->
                <a href="/" class="flex items-center gap-2.5 no-underline text-[#0f172a] flex-shrink-0">
                    <AppLogo />
                </a>

                <!-- Nav links -->
                <nav class="hidden lg:flex items-center gap-1 flex-1 ml-6">
                    <Link href="/" class="px-3 py-2 text-sm font-semibold text-[#3d4a5c] rounded-lg no-underline transition-all hover:bg-[#edf1f8] hover:text-[#0f172a]">Home</Link>
                    <Link href="/help" class="px-3 py-2 text-sm font-semibold bg-[#edf1f8] text-[#0f172a] rounded-lg no-underline transition-all">Help</Link>
                    <Link href="/#pricing" class="px-3 py-2 text-sm font-semibold text-[#3d4a5c] rounded-lg no-underline transition-all hover:bg-[#edf1f8] hover:text-[#0f172a]">Pricing</Link>
                </nav>

                <!-- CTA — auth-aware -->
                <div class="flex items-center gap-2 flex-shrink-0">
                    <template v-if="isLoggedIn">
                        <Link href="/dashboard" class="inline-flex items-center gap-2 h-9.5 px-4 rounded-[10px] bg-[#1e66f5] text-white font-inherit text-sm font-bold no-underline transition-opacity hover:opacity-90">Dashboard</Link>
                    </template>
                    <template v-else>
                        <Link href="/login" class="h-9.5 px-4 text-sm font-semibold text-[#0f172a] no-underline inline-flex items-center rounded-lg transition-colors hover:bg-[#edf1f8]">Sign in</Link>
                        <Link href="/register" class="inline-flex items-center gap-2 h-9.5 px-4 rounded-[10px] bg-[#1e66f5] text-white font-inherit text-sm font-bold no-underline transition-opacity hover:opacity-90">Get started free</Link>
                    </template>
                </div>
            </div>
        </header>

        <!-- ══════════════════════════════════════════════
             PAGE HERO
        ══════════════════════════════════════════════ -->
        <div class="py-16 lg:py-14 border-b border-[#e8ecf1]">
            <div class="max-w-screen-xl mx-auto px-6">
                <p v-if="eyebrow" class="text-xs font-bold tracking-widest uppercase text-[#1e66f5] mb-3.5">{{ eyebrow }}</p>
                <h1 class="text-4xl lg:text-6xl font-black tracking-tight leading-tight text-[#0f172a] max-w-3xl">{{ title }}</h1>
                <p v-if="description" class="mt-5 text-lg leading-[1.7] text-[#3d4a5c] max-w-2xl">{{ description }}</p>
            </div>
        </div>

        <!-- ══════════════════════════════════════════════
             MAIN CONTENT
        ══════════════════════════════════════════════ -->
        <main class="flex-1 py-16 lg:py-20">
            <div class="max-w-screen-xl mx-auto px-6 flex flex-col gap-16">
                <slot />
            </div>
        </main>

        <!-- ══════════════════════════════════════════════
             FOOTER
        ══════════════════════════════════════════════ -->
        <footer class="bg-[#0f172a] text-white/55 text-sm">
            <div class="max-w-screen-xl mx-auto px-6 grid grid-cols-1 lg:grid-cols-[1fr_2fr] gap-16 py-16 md:py-12">
                <div class="flex flex-col gap-4">
                    <div class="flex items-center gap-2.5 text-white">
                        <AppLogo />
                    </div>
                    <p class="text-sm leading-relaxed">Your business, online in minutes.</p>
                    <p class="text-sm">
                        Need help?
                        <a href="mailto:help@321sites.com" class="text-white/80 underline underline-offset-[3px] transition-colors hover:text-white">help@321sites.com</a>
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-10">
                    <div class="flex flex-col gap-2.5">
                        <p class="text-xs font-bold uppercase tracking-widest text-white/40 mb-1">Product</p>
                        <Link href="/#how" class="text-sm text-white/60 no-underline transition-colors hover:text-white">How it works</Link>
                        <Link href="/#features" class="text-sm text-white/60 no-underline transition-colors hover:text-white">Features</Link>
                        <Link href="/#pricing" class="text-sm text-white/60 no-underline transition-colors hover:text-white">Pricing</Link>
                    </div>
                    <div class="flex flex-col gap-2.5">
                        <p class="text-xs font-bold uppercase tracking-widest text-white/40 mb-1">Help</p>
                        <Link href="/help" class="text-sm text-white/60 no-underline transition-colors hover:text-white">Help centre</Link>
                        <a href="mailto:help@321sites.com" class="text-sm text-white/60 no-underline transition-colors hover:text-white">Email support</a>
                    </div>
                    <div class="flex flex-col gap-2.5">
                        <p class="text-xs font-bold uppercase tracking-widest text-white/40 mb-1">Legal</p>
                        <Link href="/terms" class="text-sm text-white/60 no-underline transition-colors hover:text-white">Terms</Link>
                        <Link href="/privacy" class="text-sm text-white/60 no-underline transition-colors hover:text-white">Privacy</Link>
                    </div>
                </div>
            </div>

            <div class="max-w-screen-xl mx-auto px-6 flex flex-col md:flex-row items-start md:items-center justify-between gap-2 py-5 md:py-7 border-t border-white/8 text-xs text-white/35">
                <span class="flex items-center gap-1.5">
                    <span class="w-1.75 h-1.75 rounded-full bg-[#22C55E] flex-shrink-0" aria-hidden="true"></span>
                    All systems operational
                </span>
                <span>© {{ new Date().getFullYear() }} 321Sites. All rights reserved.</span>
            </div>
        </footer>
    </div>
</template>
