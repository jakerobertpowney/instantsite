<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue';
import { home } from '@/routes';
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <!-- Load Inter — same as the marketing homepage -->
    <Head>
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="" />
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet" />
    </Head>

    <!--
        CSS variable overrides scoped to the onboarding auth pages:
          --primary / --primary-foreground  → blue CTA buttons (matching homepage)
          --radius                          → 0.75rem so rounded-md = 10px (marketing border-radius)
    -->
    <div
        class="flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10"
        style="
            background: #F6F5F1;
            font-family: 'Inter', ui-sans-serif, system-ui, sans-serif;
            --primary: #1E66F5;
            --primary-foreground: #ffffff;
            --radius: 0.75rem;
        "
    >
        <div class="w-full max-w-sm">
            <div class="flex flex-col gap-8">
                <div class="flex flex-col items-center gap-4">
                    <Link :href="home()" class="flex flex-col items-center gap-3">
                        <AppLogo />
                        <p style="font-size: 13px; color: #6B727D; margin-top: 3px;">Simple websites for small businesses.</p>
                    </Link>
                    <div class="space-y-1.5 text-center">
                        <h1 style="font-size: 21px; font-weight: 800; letter-spacing: -0.3px; color: #111418; line-height: 1.2;">{{ title }}</h1>
                        <p style="font-size: 15px; color: #6B727D; line-height: 1.5;">{{ description }}</p>
                    </div>
                </div>
                <slot />
                <div class="flex items-center justify-center gap-4" style="font-size: 13px; color: #6B727D;">
                    <Link href="/help" class="transition-opacity hover:opacity-60">Help</Link>
                    <Link href="/terms" class="transition-opacity hover:opacity-60">Terms</Link>
                    <Link href="/privacy" class="transition-opacity hover:opacity-60">Privacy</Link>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/*
 * Override shadcn Button sizing/weight within the auth layout to match the
 * marketing page button spec:  48px tall · 15px · 700 weight · 10px radius
 * The colour is already handled by the --primary CSS variable override above.
 */
:deep(button[type="submit"]),
:deep(button[type="button"].auth-cta) {
    height: 48px !important;
    font-size: 15px !important;
    font-weight: 700 !important;
    border-radius: 10px !important;
    letter-spacing: normal !important;
}

/* Labels — slightly bolder than shadcn default */
:deep(label) {
    font-size: 14px;
    font-weight: 600;
    color: #111418;
}

/* Inputs — taller, white background, more breathing room */
:deep(input[type="email"]),
:deep(input[type="password"]),
:deep(input[type="text"]) {
    height: 44px !important;
    font-size: 15px !important;
    border-radius: 8px !important;
    border-color: #D9D6CE !important;
    background: #ffffff !important;
}

:deep(input[type="email"]:focus),
:deep(input[type="password"]:focus),
:deep(input[type="text"]:focus) {
    border-color: #1E66F5 !important;
    box-shadow: 0 0 0 3px rgba(30, 102, 245, 0.12) !important;
    outline: none !important;
}
</style>
