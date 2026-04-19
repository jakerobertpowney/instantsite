<script setup lang="ts">
import { Copy, ExternalLink, Globe, RefreshCw, CheckCircle, ChevronRight, Image, Pencil, Phone, Star, BarChart2, Link as LinkIcon, MessageSquare, AlertCircle, Lock } from 'lucide-vue-next';
import { inject, computed, ref, onUnmounted } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import DashboardController from '@/actions/App/Http/Controllers/Admin/DashboardController';

const emit = defineEmits<{
    navigate: [id: 'home' | 'address' | 'edit' | 'seo' | 'help' | 'messages' | 'account']
}>();

const site = inject('site') as any;
const appDomain = inject('appDomain') as string;
const page = usePage();

const firstName = computed(() => {
    const name = (page.props.auth?.user?.name as string | undefined)?.trim();
    return name ? name.split(' ')[0] : null;
});

const greeting = computed(() => {
    const hour = new Date().getHours();
    if (hour < 12) return 'Good morning';
    if (hour < 18) return 'Good afternoon';
    return 'Good evening';
});

const siteUrl = computed(() => {
    if (!site) return null;
    if (site.domain_type === 'custom' && site.custom_domain) return `https://${site.custom_domain}`;
    if (site.domain_type === 'subdomain' && site.subdomain) return `https://${site.subdomain}.${appDomain}`;
    return null;
});

const isLive = computed(() => !!siteUrl.value && site?.domain_type !== 'draft');

const statusTone = computed(() => {
    if (site?.domain_type === 'draft') return 'draft';
    if (isLive.value && site?.is_private) return 'private';
    if (isLive.value) return 'live';
    return 'neutral';
});

const statusLabel = computed(() => {
    if (site?.domain_type === 'draft') return 'Draft — not visible yet';
    if (isLive.value && site?.is_private) return 'Private';
    if (site?.domain_type === 'custom' && site?.custom_domain) return 'Live — Custom Domain';
    if (site?.domain_type === 'subdomain' && site?.subdomain) return 'Live';
    return 'Not published';
});

const googleSyncedAt = computed(() => {
    const raw = site?.data?.google_synced_at;
    if (!raw) return null;
    return new Date(raw).toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' });
});

const lastUpdatedAt = computed(() => {
    const candidates = [
        site?.updated_at,
        site?.data?.google_synced_at,
    ].filter(Boolean).map(r => new Date(r as string).getTime());
    if (!candidates.length) return null;
    return new Date(Math.max(...candidates)).toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' });
});

// ── Refresh from Google ───────────────────────────────────────────────
const isRefreshing = ref(false);
const refreshSuccess = ref(false);
const refreshFailed = ref(false);
const progressStep = ref(0);

const progressSteps = [
    'Connecting to Google…',
    'Fetching your business info…',
    'Downloading photos…',
    'Saving changes…',
];

const progressBarWidth = computed(() => {
    const widths = [15, 40, 65, 85];
    return `${widths[progressStep.value] ?? 85}%`;
});

let pollInterval: ReturnType<typeof setInterval> | null = null;
let stepInterval: ReturnType<typeof setInterval> | null = null;
let timeoutHandle: ReturnType<typeof setTimeout> | null = null;

const stopAll = () => {
    if (pollInterval) { clearInterval(pollInterval); pollInterval = null; }
    if (stepInterval) { clearInterval(stepInterval); stepInterval = null; }
    if (timeoutHandle) { clearTimeout(timeoutHandle); timeoutHandle = null; }
};

onUnmounted(stopAll);

const refreshFromGoogle = () => {
    if (isRefreshing.value) return;

    const previousSyncedAt = site?.data?.google_synced_at ?? null;
    isRefreshing.value = true;
    refreshSuccess.value = false;
    refreshFailed.value = false;
    progressStep.value = 0;

    // Advance the step label every 4 seconds while the job runs
    stepInterval = setInterval(() => {
        if (progressStep.value < progressSteps.length - 1) {
            progressStep.value++;
        }
    }, 4000);

    router.post(DashboardController.refresh.url(), {}, {
        onSuccess: () => {
            // Job has been dispatched — poll until google_synced_at changes
            pollInterval = setInterval(() => {
                router.reload({
                    only: ['site'],
                    onSuccess: () => {
                        const newSyncedAt = site?.data?.google_synced_at ?? null;
                        if (newSyncedAt && newSyncedAt !== previousSyncedAt) {
                            stopAll();
                            progressStep.value = progressSteps.length - 1;
                            // Brief pause so the bar reaches 85% visually, then do a
                            // final reload to ensure the "Last checked" timestamp is fresh
                            setTimeout(() => {
                                router.reload({
                                    only: ['site'],
                                    onFinish: () => {
                                        isRefreshing.value = false;
                                        refreshSuccess.value = true;
                                    },
                                });
                            }, 400);
                        }
                    },
                });
            }, 2500);

            // Safety timeout: give up polling after 90 s — do a final reload and show success
            timeoutHandle = setTimeout(() => {
                if (isRefreshing.value) {
                    stopAll();
                    router.reload({
                        onFinish: () => {
                            isRefreshing.value = false;
                            refreshSuccess.value = true;
                        },
                    });
                }
            }, 90_000);
        },
        onError: () => {
            stopAll();
            isRefreshing.value = false;
            refreshFailed.value = true;
        },
    });
};

const showCopied = ref(false);
const copyLink = () => {
    if (!siteUrl.value) return;
    navigator.clipboard.writeText(siteUrl.value);
    showCopied.value = true;
    setTimeout(() => { showCopied.value = false; }, 2000);
};

// ── Task completion checks ────────────────────────────────────────────────
// Logo: user has uploaded their own image via the Components tab
const hasLogo = computed(() => !!site?.data?.overrides?.logo_path);

// Description: user has typed their own override (Google's description doesn't count)
const hasDescription = computed(() => !!(site?.data?.overrides?.description?.trim()));

// Buttons: at least one custom quick-action link has been added
const hasButtons = computed(() => (site?.data?.quickLinks?.length ?? 0) > 0);

// SEO: both meta fields filled in (top-level columns, not inside data blob)
const hasSeo = computed(() => !!(site?.meta_title?.trim() && site?.meta_description?.trim()));

// Contact form: the contact_form component has been explicitly enabled
const hasContactForm = computed(() => site?.data?.components?.contact_form?.enabled === true);

// Custom domain: domain type is 'custom' and a domain has been entered
const hasCustomDomain = computed(() => site?.domain_type === 'custom' && !!site?.custom_domain);

// Analytics: a Google Analytics / GA4 measurement ID has been saved
const hasAnalytics = computed(() => !!(site?.data?.google_analytics_id?.trim()));

// Hide the whole checklist once every task is done
const allDone = computed(() =>
    hasLogo.value &&
    hasDescription.value &&
    hasButtons.value &&
    hasSeo.value &&
    hasContactForm.value &&
    hasCustomDomain.value &&
    hasAnalytics.value
);
</script>

<template>
    <div class="ov-wrap">

        <!-- ── Hero card ──────────────────────────────────────────── -->
        <div class="ov-card ov-card--hero">
            <!-- Status pill row -->
            <div class="ov-pill-row">
                <span class="ov-pill" :class="`ov-pill--${statusTone}`">
                    <CheckCircle v-if="isLive && !site?.is_private" :size="14" />
                    <Lock v-else-if="isLive && site?.is_private" :size="14" />
                    {{ statusLabel }}
                </span>
                <span v-if="lastUpdatedAt" class="ov-synced-label">
                    Last updated: {{ lastUpdatedAt }}
                </span>
            </div>

            <!-- Greeting -->
            <h2 class="ov-greeting">
                {{ greeting }}<template v-if="firstName">, {{ firstName }}</template>.
            </h2>
            <p class="ov-greeting-sub">
                Press <strong>See my website</strong> to open it, or <strong>Copy link</strong> to send it to a customer.
            </p>

            <!-- URL block -->
            <div class="ov-url-block" v-if="siteUrl">
                <Globe :size="26" class="ov-url-block__icon" />
                <div class="ov-url-block__body">
                    <div class="ov-url-block__caption">Your web address</div>
                    <div class="ov-url-block__url">{{ siteUrl }}</div>
                </div>
                <div class="ov-url-block__actions">
                    <button class="db-btn db-btn--secondary" @click="copyLink">
                        <Copy :size="16" />
                        <span>{{ showCopied ? 'Copied!' : 'Copy link' }}</span>
                    </button>
                    <a :href="siteUrl" target="_blank" rel="noopener noreferrer" class="db-btn db-btn--primary">
                        <ExternalLink :size="16" />
                        <span>See my website</span>
                    </a>
                </div>
            </div>

            <!-- Not published yet -->
            <div v-else class="ov-unpublished">
                <Globe :size="32" class="ov-unpublished__icon" />
                <p class="ov-unpublished__title">Your website isn't live yet</p>
                <p class="ov-unpublished__hint">Go to <strong>Web address</strong> to choose a link and make your site live.</p>
                <button class="db-btn db-btn--primary" @click="emit('navigate', 'address')">
                    Set a web address
                </button>
            </div>
        </div>

        <!-- ── Two-column grid ──────────────────────────────────────── -->
        <div class="ov-grid">

            <!-- What to do next — hidden once everything is complete -->
            <div v-if="!allDone" class="ov-card ov-card--pad">
                <h3 class="ov-card__title">What you might do next</h3>
                <p class="ov-card__sub">Work through these to get the most out of your site.</p>
                <div class="ov-task-list">

                    <!-- 1. Logo -->
                    <button
                        v-if="!hasLogo"
                        class="ov-task"
                        @click="emit('navigate', 'edit')"
                    >
                        <span class="ov-task__icon">
                            <Image :size="22" />
                        </span>
                        <span class="ov-task__body">
                            <span class="ov-task__title">Add your logo</span>
                            <span class="ov-task__hint">Replace the placeholder with your own logo.</span>
                        </span>
                        <ChevronRight :size="18" class="ov-task__arrow" />
                    </button>

                    <!-- 2. About / description -->
                    <button
                        v-if="!hasDescription"
                        class="ov-task"
                        @click="emit('navigate', 'edit')"
                    >
                        <span class="ov-task__icon">
                            <Pencil :size="22" />
                        </span>
                        <span class="ov-task__body">
                            <span class="ov-task__title">Write an About section</span>
                            <span class="ov-task__hint">Tell customers a bit about your work.</span>
                        </span>
                        <ChevronRight :size="18" class="ov-task__arrow" />
                    </button>

                    <!-- 3. Quick-action buttons -->
                    <button
                        v-if="!hasButtons"
                        class="ov-task"
                        @click="emit('navigate', 'edit')"
                    >
                        <span class="ov-task__icon">
                            <Phone :size="22" />
                        </span>
                        <span class="ov-task__body">
                            <span class="ov-task__title">Add a button</span>
                            <span class="ov-task__hint">So customers can call, WhatsApp, or book you.</span>
                        </span>
                        <ChevronRight :size="18" class="ov-task__arrow" />
                    </button>

                    <!-- 4. SEO / meta tags -->
                    <button
                        v-if="!hasSeo"
                        class="ov-task"
                        @click="emit('navigate', 'seo')"
                    >
                        <span class="ov-task__icon">
                            <Star :size="22" />
                        </span>
                        <span class="ov-task__body">
                            <span class="ov-task__title">Set up Search & sharing</span>
                            <span class="ov-task__hint">Help people find you on Google.</span>
                        </span>
                        <ChevronRight :size="18" class="ov-task__arrow" />
                    </button>

                    <!-- 5. Contact form (premium) -->
                    <button
                        v-if="!hasContactForm"
                        class="ov-task"
                        @click="emit('navigate', 'edit')"
                    >
                        <span class="ov-task__icon">
                            <MessageSquare :size="22" />
                        </span>
                        <span class="ov-task__body">
                            <span class="ov-task__title">
                                Turn on your contact form
                                <span class="ov-pro-badge">PRO</span>
                            </span>
                            <span class="ov-task__hint">Let customers send you enquiries directly from your site.</span>
                        </span>
                        <ChevronRight :size="18" class="ov-task__arrow" />
                    </button>

                    <!-- 6. Custom domain (premium) -->
                    <button
                        v-if="!hasCustomDomain"
                        class="ov-task"
                        @click="emit('navigate', 'address')"
                    >
                        <span class="ov-task__icon">
                            <LinkIcon :size="22" />
                        </span>
                        <span class="ov-task__body">
                            <span class="ov-task__title">
                                Add a custom domain
                                <span class="ov-pro-badge">PRO</span>
                            </span>
                            <span class="ov-task__hint">Use your own address like www.yourbusiness.com.</span>
                        </span>
                        <ChevronRight :size="18" class="ov-task__arrow" />
                    </button>

                    <!-- 7. Google Analytics (premium) -->
                    <button
                        v-if="!hasAnalytics"
                        class="ov-task"
                        @click="emit('navigate', 'seo')"
                    >
                        <span class="ov-task__icon">
                            <BarChart2 :size="22" />
                        </span>
                        <span class="ov-task__body">
                            <span class="ov-task__title">
                                Add Google Analytics
                                <span class="ov-pro-badge">PRO</span>
                            </span>
                            <span class="ov-task__hint">See how many people visit your site and where they come from.</span>
                        </span>
                        <ChevronRight :size="18" class="ov-task__arrow" />
                    </button>

                </div>
            </div>

            <!-- All done state -->
            <div v-else class="ov-card ov-card--pad ov-card--done">
                <span class="ov-done-icon"><CheckCircle :size="28" /></span>
                <h3 class="ov-card__title" style="margin: 0;">Your site is looking great!</h3>
                <p class="ov-card__sub" style="margin: 0;">Share your link with a customer and start getting enquiries.</p>
            </div>

            <!-- Google Business card -->
            <div class="ov-card ov-card--pad">
                <h3 class="ov-card__title">Your Google Business listing</h3>
                <p class="ov-card__sub">Your site pulls your name, address, phone, photos and reviews from Google. Updated something recently? Refresh here to bring the changes across.</p>

                <div class="ov-google-block">
                    <div class="ov-google-block__row">
                        <span class="ov-google-label">Last checked</span>
                        <span class="ov-google-val">{{ googleSyncedAt || 'Never' }}</span>
                    </div>
                </div>

                <!-- Progress state -->
                <div v-if="isRefreshing" class="ov-refresh-progress">
                    <div class="ov-refresh-progress__header">
                        <RefreshCw :size="15" class="ov-spin" />
                        <span>Refreshing from Google</span>
                    </div>
                    <div class="ov-refresh-bar">
                        <div class="ov-refresh-bar__fill" :style="{ width: progressBarWidth }"></div>
                    </div>
                    <p class="ov-refresh-step">{{ progressSteps[progressStep] }}</p>
                </div>

                <!-- Success alert -->
                <div v-else-if="refreshSuccess" class="ov-refresh-success">
                    <CheckCircle :size="17" class="ov-refresh-success__icon" />
                    <div class="ov-refresh-success__body">
                        <span class="ov-refresh-success__title">All up to date!</span>
                        <span class="ov-refresh-success__hint">Your site now shows the latest info from your Google Business Profile.</span>
                    </div>
                </div>

                <!-- Error alert -->
                <div v-else-if="refreshFailed" class="ov-refresh-error">
                    <AlertCircle :size="17" class="ov-refresh-error__icon" />
                    <div>
                        <span class="ov-refresh-error__title">Something went wrong</span>
                        <span class="ov-refresh-error__hint">We couldn't reach Google. Please try again in a moment.</span>
                    </div>
                </div>

                <!-- Default button -->
                <button
                    v-if="!isRefreshing"
                    class="db-btn db-btn--secondary db-btn--full"
                    :disabled="isRefreshing"
                    @click="refreshFromGoogle"
                >
                    <RefreshCw :size="16" />
                    <span>Refresh from Google</span>
                </button>

                <p class="ov-google-hint">
                    If something looks wrong on your site, check your Google Business Profile first — that's the easiest place to fix it.
                </p>
            </div>
        </div>
    </div>
</template>

<style scoped>
.ov-wrap {
    display: flex;
    flex-direction: column;
    gap: 24px;
}

/* ── Cards ──────────────────────────────────────────────────────────── */
.ov-card {
    background: var(--db-surface);
    border: 1.5px solid var(--db-line);
    border-radius: 14px;
}

.ov-card--pad {
    padding: 24px;
}

.ov-card--hero {
    padding: 28px;
}

.ov-card__title {
    font-size: 18px;
    font-weight: 700;
    color: var(--db-ink);
    letter-spacing: -0.2px;
    margin: 0 0 6px;
}

.ov-card__sub {
    font-size: 14px;
    color: var(--db-ink-soft);
    line-height: 1.55;
    margin: 0 0 16px;
}

/* ── Pill ──────────────────────────────────────────────────────────── */
.ov-pill-row {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 12px;
}

.ov-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px;
    border-radius: 999px;
    font-size: 13px;
    font-weight: 600;
}

.ov-pill--live    { background: #DEEFE3; color: #14532A; border: 1px solid #B7DAC1; }
.ov-pill--private { background: #EDE9FE; color: #4C1D95; border: 1px solid #C4B5FD; }
.ov-pill--draft   { background: #F6E8D4; color: #6B3E00; border: 1px solid #E6C999; }
.ov-pill--neutral { background: #EEECE5; color: var(--db-ink); border: 1px solid var(--db-line); }

.ov-synced-label {
    font-size: 13px;
    color: var(--db-ink-soft);
}

/* ── Greeting ──────────────────────────────────────────────────────── */
.ov-greeting {
    font-size: 28px;
    font-weight: 800;
    color: var(--db-ink);
    letter-spacing: -0.6px;
    line-height: 1.15;
    margin: 0 0 6px;
}

.ov-greeting-sub {
    font-size: 16px;
    color: var(--db-ink-mid);
    line-height: 1.55;
    margin: 0 0 20px;
}

/* ── URL block ─────────────────────────────────────────────────────── */
.ov-url-block {
    margin-top: 4px;
    padding: 20px;
    border-radius: 12px;
    background: var(--db-panel);
    display: flex;
    align-items: center;
    gap: 16px;
    flex-wrap: wrap;
}

.ov-url-block__icon {
    color: var(--db-ink);
    flex-shrink: 0;
}

.ov-url-block__body {
    flex: 1;
    min-width: 200px;
}

.ov-url-block__caption {
    font-size: 12px;
    color: var(--db-ink-soft);
    font-weight: 600;
    letter-spacing: 0.3px;
    text-transform: uppercase;
}

.ov-url-block__url {
    font-size: 18px;
    font-weight: 700;
    color: var(--db-ink);
    margin-top: 2px;
    word-break: break-all;
}

.ov-url-block__actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

/* ── Unpublished state ─────────────────────────────────────────────── */
.ov-unpublished {
    margin-top: 4px;
    padding: 28px;
    border-radius: 12px;
    background: var(--db-panel);
    border: 1.5px dashed var(--db-line);
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    gap: 10px;
}

.ov-unpublished__icon { color: var(--db-ink-soft); }

.ov-unpublished__title {
    font-size: 17px;
    font-weight: 700;
    color: var(--db-ink);
    margin: 0;
}

.ov-unpublished__hint {
    font-size: 14px;
    color: var(--db-ink-soft);
    line-height: 1.5;
    margin: 0;
}

/* ── Two-col grid ──────────────────────────────────────────────────── */
.ov-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

@media (max-width: 900px) {
    .ov-grid { grid-template-columns: 1fr; }
}

/* ── Task list ─────────────────────────────────────────────────────── */
.ov-task-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.ov-task {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 16px;
    border: 1.5px solid var(--db-line);
    border-radius: 12px;
    background: var(--db-surface);
    cursor: pointer;
    font-family: inherit;
    text-align: left;
    transition: background 0.1s ease;
}

.ov-task:hover { background: var(--db-panel); }

.ov-task__icon {
    width: 42px;
    height: 42px;
    border-radius: 10px;
    background: var(--db-panel);
    color: var(--db-ink);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.ov-task__icon--done {
    background: #DEEFE3;
    color: var(--db-success);
}

.ov-task__body {
    display: flex;
    flex-direction: column;
    flex: 1;
    min-width: 0;
}

.ov-task__title {
    font-size: 15px;
    font-weight: 700;
    color: var(--db-ink);
    line-height: 1.2;
}

.ov-task__title--done {
    text-decoration: line-through;
    color: var(--db-ink-soft);
}

.ov-task__hint {
    font-size: 13px;
    color: var(--db-ink-soft);
    margin-top: 2px;
    line-height: 1.4;
}

.ov-task__arrow { color: var(--db-ink-soft); flex-shrink: 0; }

/* ── PRO badge ─────────────────────────────────────────────────────────── */
.ov-pro-badge {
    display: inline-flex;
    align-items: center;
    padding: 1px 7px;
    border-radius: 999px;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 0.6px;
    text-transform: uppercase;
    background: #111418;
    color: #F6D860;
    margin-left: 7px;
    vertical-align: middle;
    line-height: 1.8;
}

/* ── All-done card ──────────────────────────────────────────────────────── */
.ov-card--done {
    display: flex;
    align-items: center;
    gap: 16px;
}

.ov-done-icon {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: #DEEFE3;
    color: var(--db-success);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

/* ── Google block ──────────────────────────────────────────────────── */
.ov-google-block {
    padding: 14px;
    background: var(--db-panel);
    border-radius: 10px;
    margin-bottom: 14px;
}

.ov-google-block__row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    font-size: 14px;
}

.ov-google-label {
    color: var(--db-ink-soft);
    font-weight: 600;
}

.ov-google-val {
    color: var(--db-ink);
    font-weight: 700;
}

.ov-google-hint {
    font-size: 13px;
    color: var(--db-ink-soft);
    line-height: 1.55;
    margin: 12px 0 0;
}

/* ── Shared button primitives (for use in child components) ─────────── */
.db-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    height: 44px;
    padding: 0 18px;
    border-radius: 10px;
    font-family: inherit;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: opacity 0.1s ease, background 0.1s ease;
    text-decoration: none;
    white-space: nowrap;
    border: none;
}

.db-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.db-btn--primary {
    background: var(--db-accent);
    color: var(--db-accent-fg);
    border: 1.5px solid var(--db-accent);
}

.db-btn--primary:hover:not(:disabled) {
    opacity: 0.9;
}

.db-btn--secondary {
    background: var(--db-surface);
    color: var(--db-ink);
    border: 1.5px solid var(--db-line);
}

.db-btn--secondary:hover:not(:disabled) {
    background: var(--db-panel);
}

.db-btn--full { width: 100%; justify-content: center; }

/* ── Spin animation ─────────────────────────────────────────────────── */
.ov-spin {
    animation: ov-spin 1s linear infinite;
}

@keyframes ov-spin {
    to { transform: rotate(360deg); }
}

/* ── Refresh progress block ─────────────────────────────────────────── */
.ov-refresh-progress {
    padding: 14px 16px;
    background: var(--db-panel);
    border: 1.5px solid var(--db-line);
    border-radius: 10px;
    margin-bottom: 14px;
}

.ov-refresh-progress__header {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    font-weight: 600;
    color: var(--db-ink);
    margin-bottom: 10px;
}

.ov-refresh-bar {
    height: 5px;
    border-radius: 999px;
    background: var(--db-line);
    overflow: hidden;
    margin-bottom: 10px;
}

.ov-refresh-bar__fill {
    height: 100%;
    border-radius: 999px;
    background: var(--db-accent);
    transition: width 0.7s ease;
}

.ov-refresh-step {
    font-size: 13px;
    color: var(--db-ink-soft);
    margin: 0;
    min-height: 18px;
}

/* ── Refresh success alert ──────────────────────────────────────────── */
.ov-refresh-success {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 12px 14px;
    background: #DEEFE3;
    border: 1.5px solid #B7DAC1;
    border-radius: 10px;
    margin-bottom: 14px;
}

.ov-refresh-success__icon {
    color: #14532A;
    flex-shrink: 0;
    margin-top: 1px;
}

.ov-refresh-success__body {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.ov-refresh-success__title {
    font-size: 14px;
    font-weight: 700;
    color: #14532A;
    line-height: 1.2;
}

.ov-refresh-success__hint {
    font-size: 13px;
    color: #166534;
    line-height: 1.45;
}

/* ── Refresh error alert ────────────────────────────────────────────── */
.ov-refresh-error {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 12px 14px;
    background: #FEF2F2;
    border: 1.5px solid #FECACA;
    border-radius: 10px;
    margin-bottom: 14px;
}

.ov-refresh-error__icon {
    color: #991B1B;
    flex-shrink: 0;
    margin-top: 1px;
}

.ov-refresh-error__title {
    display: block;
    font-size: 14px;
    font-weight: 700;
    color: #991B1B;
    line-height: 1.2;
}

.ov-refresh-error__hint {
    display: block;
    font-size: 13px;
    color: #B91C1C;
    line-height: 1.45;
    margin-top: 2px;
}
</style>
