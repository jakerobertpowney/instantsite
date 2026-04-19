<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Terms of Service" />

    <div class="min-h-screen bg-background text-foreground">
        <div class="mx-auto flex max-w-3xl flex-col gap-8 px-6 py-12">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-muted-foreground">321Sites</p>
                    <h1 class="text-3xl font-semibold tracking-tight">Terms of Service</h1>
                </div>
                <Link href="/" class="text-sm font-medium underline underline-offset-4">Back to home</Link>
            </div>

            <div class="space-y-6 text-sm leading-7 text-muted-foreground">
                <section class="space-y-2">
                    <h2 class="text-lg font-semibold text-foreground">Using 321Sites</h2>
                    <p>321Sites helps you create and manage a simple website for your business using information from your Google Business Profile and the details you provide.</p>
                </section>

                <section class="space-y-2">
                    <h2 class="text-lg font-semibold text-foreground">Your content</h2>
                    <p>You are responsible for the accuracy of the business information, logos, links, and any other content you publish through your site.</p>
                </section>

                <section class="space-y-2">
                    <h2 class="text-lg font-semibold text-foreground">Paid features</h2>
                    <p>Premium features such as custom domains, analytics, and advanced contact tools may require a paid subscription. If you cancel, premium-only features may stop working at the end of your billing period.</p>
                </section>

                <section class="space-y-2">
                    <h2 class="text-lg font-semibold text-foreground">Availability</h2>
                    <p>We aim to keep 321Sites available and secure, but we cannot promise uninterrupted access at all times.</p>
                </section>

                <section class="space-y-2">
                    <h2 class="text-lg font-semibold text-foreground">Contact</h2>
                    <p>If you have questions about these terms, email <a href="mailto:help@321sites.com" class="font-medium text-foreground underline underline-offset-4">help@321sites.com</a>.</p>
                </section>
            </div>
        </div>
    </div>
</template>
