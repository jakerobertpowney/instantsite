<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

defineProps({
    businessName: String,
    loginUrl: String,
});
</script>

<template>
    <Head>
        <title>{{ businessName ? `${businessName} — Private` : 'Private Site' }}</title>
        <meta name="robots" content="noindex,nofollow" />
    </Head>

    <div style="min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;background:#f8fafc;font-family:system-ui,sans-serif;padding:24px;">
        <div style="width:100%;max-width:400px;text-align:center;">

            <!-- Lock icon -->
            <div style="width:64px;height:64px;border-radius:50%;background:#e2e8f0;display:flex;align-items:center;justify-content:center;margin:0 auto 24px;">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
            </div>

            <!-- Heading -->
            <h1 style="font-size:22px;font-weight:700;color:#0f172a;margin:0 0 8px;">
                This site is private
            </h1>

            <!-- Business name if available -->
            <p v-if="businessName" style="font-size:15px;color:#475569;margin:0 0 6px;">
                {{ businessName }}
            </p>

            <p style="font-size:14px;color:#94a3b8;line-height:1.6;margin:0 0 32px;">
                You need to be logged in as the site owner to view this page.
            </p>

            <!-- Login button -->
            <a
                :href="loginUrl"
                style="display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:0 28px;height:48px;border-radius:10px;background:#0f172a;color:#fff;text-decoration:none;font-size:15px;font-weight:600;transition:opacity 0.15s;"
                onmouseover="this.style.opacity='0.85'"
                onmouseout="this.style.opacity='1'"
            >
                Log in to view
            </a>

        </div>

        <!-- Footer -->
        <p style="position:fixed;bottom:20px;font-size:12px;color:#cbd5e1;">
            Powered by <a href="https://321sites.com" target="_blank" rel="noopener noreferrer" style="color:#94a3b8;text-decoration:underline;">321Sites</a>
        </p>
    </div>
</template>
