<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const isLoggedIn = computed(() => !!(page.props.auth as any)?.user);
</script>

<template>
    <Head title="Terms of Service — 321Sites">
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="" />
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet" />
    </Head>

    <div class="min-h-screen bg-white font-sans text-brand-ink antialiased" style="font-family: 'Inter', ui-sans-serif, system-ui, sans-serif;">
        <!-- Nav -->
        <header class="sticky top-0 z-40 border-b border-brand-line bg-white/92 backdrop-blur-md">
            <div class="mx-auto max-w-5xl px-6 py-3.5">
                <div class="flex items-center justify-between">
                    <a href="/" class="flex shrink-0 items-center no-underline"><AppLogo /></a>
                    <div class="flex items-center gap-2">
                        <template v-if="isLoggedIn">
                            <Link href="/dashboard" class="inline-flex items-center justify-center gap-2 rounded-[10px] bg-brand-blue px-4.5 py-3 font-bold text-white transition-opacity hover:opacity-90 text-sm">Dashboard</Link>
                        </template>
                        <template v-else>
                            <Link href="/login" class="inline-flex h-[42px] items-center rounded-[10px] px-4 font-semibold text-brand-ink transition-colors hover:bg-brand-panel text-sm no-underline">Sign in</Link>
                            <Link href="/" class="inline-flex items-center justify-center gap-2 rounded-[10px] bg-brand-blue px-4.5 py-3 font-bold text-white transition-opacity hover:opacity-90 text-sm">Find my business</Link>
                        </template>
                    </div>
                </div>
            </div>
        </header>

        <!-- Content -->
        <main class="mx-auto max-w-2xl px-6 py-16 pb-24">
            <div class="mb-12 border-b border-brand-line pb-8">
                <h1 class="mb-3 text-4xl font-black leading-tight text-brand-ink" style="letter-spacing: -0.04em;">Terms of Service</h1>
                <p class="text-sm text-brand-ink-soft">Last updated April 2026</p>
            </div>

            <div class="flex flex-col gap-10">
                <section>
                    <h2 class="mb-2.5 text-lg font-bold text-brand-ink">Using 321Sites</h2>
                    <p class="text-base leading-relaxed text-gray-700">321Sites helps you create and manage a simple website for your business using information from your Google Business Profile and the details you provide.</p>
                </section>

                <section>
                    <h2 class="mb-2.5 text-lg font-bold text-brand-ink">Your content</h2>
                    <p class="text-base leading-relaxed text-gray-700">You are responsible for the accuracy of the business information, logos, links, and any other content you publish through your site.</p>
                </section>

                <section>
                    <h2 class="mb-2.5 text-lg font-bold text-brand-ink">Paid features</h2>
                    <p class="text-base leading-relaxed text-gray-700">Premium features such as custom domains, analytics, and advanced contact tools may require a paid subscription. If you cancel, premium-only features may stop working at the end of your billing period.</p>
                </section>

                <section>
                    <h2 class="mb-2.5 text-lg font-bold text-brand-ink">Availability</h2>
                    <p class="text-base leading-relaxed text-gray-700">We aim to keep 321Sites available and secure, but we cannot promise uninterrupted access at all times.</p>
                </section>

                <section>
                    <h2 class="mb-2.5 text-lg font-bold text-brand-ink">Contact</h2>
                    <p class="text-base leading-relaxed text-gray-700">If you have questions about these terms, email <a href="mailto:help@321sites.com" class="text-brand-blue underline" style="text-underline-offset: 3px;">help@321sites.com</a>.</p>
                </section>
            </div>
        </main>
    </div>
</template>

<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');
</style>
