<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Privacy Policy" />

    <div class="min-h-screen bg-background text-foreground">
        <div class="mx-auto flex max-w-3xl flex-col gap-8 px-6 py-12">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-muted-foreground">321Sites</p>
                    <h1 class="text-3xl font-semibold tracking-tight">Privacy Policy</h1>
                </div>
                <Link href="/" class="text-sm font-medium underline underline-offset-4">Back to home</Link>
            </div>

            <div class="space-y-6 text-sm leading-7 text-muted-foreground">
                <section class="space-y-2">
                    <h2 class="text-lg font-semibold text-foreground">What we collect</h2>
                    <p>We store the account details you provide, the website content you choose to publish, and customer enquiries sent through your site.</p>
                </section>

                <section class="space-y-2">
                    <h2 class="text-lg font-semibold text-foreground">How we use it</h2>
                    <p>We use this information to create your site, help you manage it, deliver account emails, and improve the service.</p>
                </section>

                <section class="space-y-2">
                    <h2 class="text-lg font-semibold text-foreground">Third-party services</h2>
                    <p>321Sites relies on trusted providers such as Google, Stripe, and email infrastructure partners to power search, billing, and communication features.</p>
                </section>

                <section class="space-y-2">
                    <h2 class="text-lg font-semibold text-foreground">Your choices</h2>
                    <p>You can update your account details, control whether your site is indexed by search engines, and contact us if you need help with your data.</p>
                </section>

                <section class="space-y-2">
                    <h2 class="text-lg font-semibold text-foreground">Contact</h2>
                    <p>If you have privacy questions, email <a href="mailto:help@321sites.com" class="font-medium text-foreground underline underline-offset-4">help@321sites.com</a>.</p>
                </section>
            </div>
        </div>
    </div>
</template>
