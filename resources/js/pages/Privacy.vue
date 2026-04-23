<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const isLoggedIn = computed(() => !!(page.props.auth as any)?.user);
</script>

<template>
    <Head title="Privacy Policy — 321Sites">
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="" />
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet" />
    </Head>

    <div class="lp-page">
        <!-- Nav -->
        <header class="lp-nav">
            <div class="lp-container lp-nav__inner">
                <a href="/" class="lp-nav__logo"><AppLogo /></a>
                <div class="lp-nav__cta">
                    <template v-if="isLoggedIn">
                        <Link href="/dashboard" class="lp-btn lp-btn--primary">Dashboard</Link>
                    </template>
                    <template v-else>
                        <Link href="/login" class="lp-nav__signin">Sign in</Link>
                        <Link href="/" class="lp-btn lp-btn--primary">Find my business</Link>
                    </template>
                </div>
            </div>
        </header>

        <!-- Content -->
        <main class="lp-container lp-legal">
            <div class="lp-legal__head">
                <h1 class="lp-legal__h1">Privacy Policy</h1>
                <p class="lp-legal__updated">Last updated April 2026</p>
            </div>

            <div class="lp-legal__body">
                <section>
                    <h2>What we collect</h2>
                    <p>We store the account details you provide, the website content you choose to publish, and customer enquiries sent through your site.</p>
                </section>

                <section>
                    <h2>How we use it</h2>
                    <p>We use this information to create your site, help you manage it, deliver account emails, and improve the service.</p>
                </section>

                <section>
                    <h2>Third-party services</h2>
                    <p>321Sites relies on trusted providers such as Google, Stripe, and email infrastructure partners to power search, billing, and communication features.</p>
                </section>

                <section>
                    <h2>Your choices</h2>
                    <p>You can update your account details, control whether your site is indexed by search engines, and contact us if you need help with your data.</p>
                </section>

                <section>
                    <h2>Contact</h2>
                    <p>If you have privacy questions, email <a href="mailto:help@321sites.com">help@321sites.com</a>.</p>
                </section>
            </div>
        </main>
    </div>
</template>

<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');
</style>

<style scoped>
.lp-page {
    font-family: 'Inter', ui-sans-serif, system-ui, sans-serif;
    background: #ffffff;
    color: #0f172a;
    -webkit-font-smoothing: antialiased;
    min-height: 100vh;
}

.lp-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 24px;
}

/* Nav */
.lp-nav {
    position: sticky;
    top: 0;
    z-index: 40;
    background: rgba(255, 255, 255, 0.92);
    backdrop-filter: saturate(140%) blur(8px);
    -webkit-backdrop-filter: saturate(140%) blur(8px);
    border-bottom: 1px solid #e8ecf1;
}
.lp-nav__inner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-top: 14px;
    padding-bottom: 14px;
}
.lp-nav__logo {
    display: flex;
    align-items: center;
    text-decoration: none;
    flex-shrink: 0;
}
.lp-nav__cta {
    display: flex;
    align-items: center;
    gap: 8px;
}
.lp-nav__signin {
    height: 42px;
    padding: 0 16px;
    display: inline-flex;
    align-items: center;
    font-size: 15px;
    font-weight: 600;
    color: #0f172a;
    text-decoration: none;
    border-radius: 10px;
    transition: background 0.1s ease;
}
.lp-nav__signin:hover {
    background: #edf1f8;
}
.lp-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    height: 42px;
    padding: 0 18px;
    border-radius: 10px;
    font-family: inherit;
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
    border: none;
    text-decoration: none;
    white-space: nowrap;
    transition: opacity 0.1s ease;
}
.lp-btn--primary {
    background: #1e66f5;
    color: #ffffff;
}
.lp-btn--primary:hover {
    opacity: 0.9;
}

/* Legal content */
.lp-legal {
    max-width: 720px;
    padding-top: 64px;
    padding-bottom: 100px;
}
.lp-legal__head {
    margin-bottom: 48px;
    padding-bottom: 32px;
    border-bottom: 1px solid #e8ecf1;
}
.lp-legal__h1 {
    font-size: clamp(32px, 4vw, 48px);
    font-weight: 800;
    letter-spacing: -1.5px;
    line-height: 1.05;
    color: #0f172a;
    margin: 0 0 12px;
}
.lp-legal__updated {
    font-size: 14px;
    color: #64748b;
    margin: 0;
}
.lp-legal__body {
    display: flex;
    flex-direction: column;
    gap: 40px;
}
.lp-legal__body section h2 {
    font-size: 18px;
    font-weight: 700;
    color: #0f172a;
    margin: 0 0 10px;
    letter-spacing: -0.2px;
}
.lp-legal__body section p {
    font-size: 16px;
    color: #3d4a5c;
    line-height: 1.7;
    margin: 0;
}
.lp-legal__body section a {
    color: #1e66f5;
    text-decoration: underline;
    text-underline-offset: 3px;
}
</style>
