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
    <div class="flex flex-col gap-6">

        <!-- ── Hero card ──────────────────────────────────────────── -->
        <div class="bg-brand-surface border-[1.5px] border-brand-line rounded-[14px] p-7">
            <!-- Status pill row -->
            <div class="flex items-center gap-3 flex-wrap mb-3">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-sm font-semibold"
                    :class="statusTone === 'live' && !site?.is_private ? 'bg-[#DEEFE3] text-[#14532A] border border-[#B7DAC1]'
                        : statusTone === 'private' ? 'bg-[#EDE9FE] text-[#4C1D95] border border-[#C4B5FD]'
                        : statusTone === 'draft' ? 'bg-[#F6E8D4] text-[#6B3E00] border border-[#E6C999]'
                        : 'bg-[#EEECE5] text-brand-ink border border-brand-line'">
                    <CheckCircle v-if="isLive && !site?.is_private" :size="14" />
                    <Lock v-else-if="isLive && site?.is_private" :size="14" />
                    {{ statusLabel }}
                </span>
                <span v-if="lastUpdatedAt" class="text-xs text-brand-ink-soft">
                    Last updated: {{ lastUpdatedAt }}
                </span>
            </div>

            <!-- Greeting -->
            <h2 class="text-3xl font-black text-brand-ink tracking-tight leading-[1.15] mb-1.5">
                {{ greeting }}<template v-if="firstName">, {{ firstName }}</template>.
            </h2>
            <p class="text-base text-brand-ink-mid leading-[1.55] mb-5">
                Press <strong>See my website</strong> to open it, or <strong>Copy link</strong> to send it to a customer.
            </p>

            <!-- URL block -->
            <div class="mt-1 p-5 rounded-[12px] bg-brand-panel flex items-center gap-4 flex-wrap" v-if="siteUrl">
                <Globe :size="26" class="text-brand-ink flex-shrink-0" />
                <div class="flex-1 min-w-[200px]">
                    <div class="text-xs text-brand-ink-soft font-semibold tracking-wide uppercase">Your web address</div>
                    <div class="text-[18px] font-bold text-brand-ink mt-0.5 break-all">{{ siteUrl }}</div>
                </div>
                <div class="flex gap-2.5 flex-wrap">
                    <button class="inline-flex items-center gap-2 h-11 px-4.5 rounded-[10px] bg-brand-surface text-brand-ink border-[1.5px] border-brand-line font-semibold text-sm cursor-pointer hover:bg-brand-panel transition-colors" @click="copyLink">
                        <Copy :size="16" />
                        <span>{{ showCopied ? 'Copied!' : 'Copy link' }}</span>
                    </button>
                    <a :href="siteUrl" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 h-11 px-4.5 rounded-[10px] bg-brand-blue text-white font-semibold text-sm cursor-pointer hover:opacity-90 transition-opacity">
                        <ExternalLink :size="16" />
                        <span>See my website</span>
                    </a>
                </div>
            </div>

            <!-- Not published yet -->
            <div v-else class="mt-1 p-7 rounded-[12px] bg-brand-panel border-[1.5px] border-dashed border-brand-line flex flex-col items-center text-center gap-2.5">
                <Globe :size="32" class="text-brand-ink-soft" />
                <p class="text-[17px] font-bold text-brand-ink m-0">Your website isn't live yet</p>
                <p class="text-sm text-brand-ink-soft leading-[1.5] m-0">Go to <strong>Web address</strong> to choose a link and make your site live.</p>
                <button class="inline-flex items-center gap-2 h-11 px-4.5 rounded-[10px] bg-brand-blue text-white font-semibold text-sm cursor-pointer hover:opacity-90 transition-opacity" @click="emit('navigate', 'address')">
                    Set a web address
                </button>
            </div>
        </div>

        <!-- ── Two-column grid ──────────────────────────────────────── -->
        <div class="grid grid-cols-2 gap-5 max-lg:grid-cols-1">

            <!-- What to do next — hidden once everything is complete -->
            <div v-if="!allDone" class="bg-brand-surface border-[1.5px] border-brand-line rounded-[14px] p-6">
                <h3 class="text-lg font-bold text-brand-ink tracking-tight mb-1.5">What you might do next</h3>
                <p class="text-sm text-brand-ink-soft leading-[1.55] mb-4">Work through these to get the most out of your site.</p>
                <div class="flex flex-col gap-2">

                    <!-- 1. Logo -->
                    <button
                        v-if="!hasLogo"
                        class="flex items-center gap-3.5 p-3.5 border-[1.5px] border-brand-line rounded-[12px] bg-brand-surface cursor-pointer font-inherit text-left transition-colors hover:bg-brand-panel"
                        @click="emit('navigate', 'edit')"
                    >
                        <span class="w-[42px] h-[42px] rounded-[10px] bg-brand-panel text-brand-ink flex items-center justify-center flex-shrink-0">
                            <Image :size="22" />
                        </span>
                        <span class="flex flex-col flex-1 min-w-0">
                            <span class="text-sm font-bold text-brand-ink leading-[1.2]">Add your logo</span>
                            <span class="text-xs text-brand-ink-soft mt-0.5 leading-[1.4]">Replace the placeholder with your own logo.</span>
                        </span>
                        <ChevronRight :size="18" class="text-brand-ink-soft flex-shrink-0" />
                    </button>

                    <!-- 2. About / description -->
                    <button
                        v-if="!hasDescription"
                        class="flex items-center gap-3.5 p-3.5 border-[1.5px] border-brand-line rounded-[12px] bg-brand-surface cursor-pointer font-inherit text-left transition-colors hover:bg-brand-panel"
                        @click="emit('navigate', 'edit')"
                    >
                        <span class="w-[42px] h-[42px] rounded-[10px] bg-brand-panel text-brand-ink flex items-center justify-center flex-shrink-0">
                            <Pencil :size="22" />
                        </span>
                        <span class="flex flex-col flex-1 min-w-0">
                            <span class="text-sm font-bold text-brand-ink leading-[1.2]">Write an About section</span>
                            <span class="text-xs text-brand-ink-soft mt-0.5 leading-[1.4]">Tell customers a bit about your work.</span>
                        </span>
                        <ChevronRight :size="18" class="text-brand-ink-soft flex-shrink-0" />
                    </button>

                    <!-- 3. Quick-action buttons -->
                    <button
                        v-if="!hasButtons"
                        class="flex items-center gap-3.5 p-3.5 border-[1.5px] border-brand-line rounded-[12px] bg-brand-surface cursor-pointer font-inherit text-left transition-colors hover:bg-brand-panel"
                        @click="emit('navigate', 'edit')"
                    >
                        <span class="w-[42px] h-[42px] rounded-[10px] bg-brand-panel text-brand-ink flex items-center justify-center flex-shrink-0">
                            <Phone :size="22" />
                        </span>
                        <span class="flex flex-col flex-1 min-w-0">
                            <span class="text-sm font-bold text-brand-ink leading-[1.2]">Add a button</span>
                            <span class="text-xs text-brand-ink-soft mt-0.5 leading-[1.4]">So customers can call, WhatsApp, or book you.</span>
                        </span>
                        <ChevronRight :size="18" class="text-brand-ink-soft flex-shrink-0" />
                    </button>

                    <!-- 4. SEO / meta tags -->
                    <button
                        v-if="!hasSeo"
                        class="flex items-center gap-3.5 p-3.5 border-[1.5px] border-brand-line rounded-[12px] bg-brand-surface cursor-pointer font-inherit text-left transition-colors hover:bg-brand-panel"
                        @click="emit('navigate', 'seo')"
                    >
                        <span class="w-[42px] h-[42px] rounded-[10px] bg-brand-panel text-brand-ink flex items-center justify-center flex-shrink-0">
                            <Star :size="22" />
                        </span>
                        <span class="flex flex-col flex-1 min-w-0">
                            <span class="text-sm font-bold text-brand-ink leading-[1.2]">Set up Search & sharing</span>
                            <span class="text-xs text-brand-ink-soft mt-0.5 leading-[1.4]">Help people find you on Google.</span>
                        </span>
                        <ChevronRight :size="18" class="text-brand-ink-soft flex-shrink-0" />
                    </button>

                    <!-- 5. Contact form (premium) -->
                    <button
                        v-if="!hasContactForm"
                        class="flex items-center gap-3.5 p-3.5 border-[1.5px] border-brand-line rounded-[12px] bg-brand-surface cursor-pointer font-inherit text-left transition-colors hover:bg-brand-panel"
                        @click="emit('navigate', 'edit')"
                    >
                        <span class="w-[42px] h-[42px] rounded-[10px] bg-brand-panel text-brand-ink flex items-center justify-center flex-shrink-0">
                            <MessageSquare :size="22" />
                        </span>
                        <span class="flex flex-col flex-1 min-w-0">
                            <span class="text-sm font-bold text-brand-ink leading-[1.2]">
                                Turn on your contact form
                                <span class="inline-flex items-center px-1.75 py-0.25 rounded-full text-[10px] font-black tracking-wider uppercase bg-brand-ink text-[#F6D860] ml-1.75 align-middle leading-[1.8]">PRO</span>
                            </span>
                            <span class="text-xs text-brand-ink-soft mt-0.5 leading-[1.4]">Let customers send you enquiries directly from your site.</span>
                        </span>
                        <ChevronRight :size="18" class="text-brand-ink-soft flex-shrink-0" />
                    </button>

                    <!-- 6. Custom domain (premium) -->
                    <button
                        v-if="!hasCustomDomain"
                        class="flex items-center gap-3.5 p-3.5 border-[1.5px] border-brand-line rounded-[12px] bg-brand-surface cursor-pointer font-inherit text-left transition-colors hover:bg-brand-panel"
                        @click="emit('navigate', 'address')"
                    >
                        <span class="w-[42px] h-[42px] rounded-[10px] bg-brand-panel text-brand-ink flex items-center justify-center flex-shrink-0">
                            <LinkIcon :size="22" />
                        </span>
                        <span class="flex flex-col flex-1 min-w-0">
                            <span class="text-sm font-bold text-brand-ink leading-[1.2]">
                                Add a custom domain
                                <span class="inline-flex items-center px-1.75 py-0.25 rounded-full text-[10px] font-black tracking-wider uppercase bg-brand-ink text-[#F6D860] ml-1.75 align-middle leading-[1.8]">PRO</span>
                            </span>
                            <span class="text-xs text-brand-ink-soft mt-0.5 leading-[1.4]">Use your own address like www.yourbusiness.com.</span>
                        </span>
                        <ChevronRight :size="18" class="text-brand-ink-soft flex-shrink-0" />
                    </button>

                    <!-- 7. Google Analytics (premium) -->
                    <button
                        v-if="!hasAnalytics"
                        class="flex items-center gap-3.5 p-3.5 border-[1.5px] border-brand-line rounded-[12px] bg-brand-surface cursor-pointer font-inherit text-left transition-colors hover:bg-brand-panel"
                        @click="emit('navigate', 'seo')"
                    >
                        <span class="w-[42px] h-[42px] rounded-[10px] bg-brand-panel text-brand-ink flex items-center justify-center flex-shrink-0">
                            <BarChart2 :size="22" />
                        </span>
                        <span class="flex flex-col flex-1 min-w-0">
                            <span class="text-sm font-bold text-brand-ink leading-[1.2]">
                                Add Google Analytics
                                <span class="inline-flex items-center px-1.75 py-0.25 rounded-full text-[10px] font-black tracking-wider uppercase bg-brand-ink text-[#F6D860] ml-1.75 align-middle leading-[1.8]">PRO</span>
                            </span>
                            <span class="text-xs text-brand-ink-soft mt-0.5 leading-[1.4]">See how many people visit your site and where they come from.</span>
                        </span>
                        <ChevronRight :size="18" class="text-brand-ink-soft flex-shrink-0" />
                    </button>

                </div>
            </div>

            <!-- All done state -->
            <div v-else class="bg-brand-surface border-[1.5px] border-brand-line rounded-[14px] p-6 flex items-center gap-4">
                <span class="w-12 h-12 rounded-full bg-[#DEEFE3] text-[#14532A] flex items-center justify-center flex-shrink-0"><CheckCircle :size="28" /></span>
                <div>
                    <h3 class="text-lg font-bold text-brand-ink tracking-tight">Your site is looking great!</h3>
                    <p class="text-sm text-brand-ink-soft leading-[1.55]">Share your link with a customer and start getting enquiries.</p>
                </div>
            </div>

            <!-- Google Business card -->
            <div class="bg-brand-surface border-[1.5px] border-brand-line rounded-[14px] p-6">
                <h3 class="text-lg font-bold text-brand-ink tracking-tight mb-1.5">Your Google Business listing</h3>
                <p class="text-sm text-brand-ink-soft leading-[1.55] mb-4">Your site pulls your name, address, phone, photos and reviews from Google. Updated something recently? Refresh here to bring the changes across.</p>

                <div class="p-3.5 bg-brand-panel rounded-[10px] mb-3.5">
                    <div class="flex items-center justify-between gap-2 text-sm">
                        <span class="text-brand-ink-soft font-semibold">Last checked</span>
                        <span class="text-brand-ink font-bold">{{ googleSyncedAt || 'Never' }}</span>
                    </div>
                </div>

                <!-- Progress state -->
                <div v-if="isRefreshing" class="p-4 bg-brand-panel border-[1.5px] border-brand-line rounded-[10px] mb-3.5">
                    <div class="flex items-center gap-2 text-sm font-semibold text-brand-ink mb-2.5">
                        <RefreshCw :size="15" class="animate-spin" />
                        <span>Refreshing from Google</span>
                    </div>
                    <div class="h-1.25 rounded-full bg-brand-line overflow-hidden mb-2.5">
                        <div class="h-full rounded-full bg-brand-blue transition-all" :style="{ width: progressBarWidth }"></div>
                    </div>
                    <p class="text-xs text-brand-ink-soft m-0 min-h-[18px]">{{ progressSteps[progressStep] }}</p>
                </div>

                <!-- Success alert -->
                <div v-else-if="refreshSuccess" class="flex items-start gap-2.5 p-3 bg-[#DEEFE3] border-[1.5px] border-[#B7DAC1] rounded-[10px] mb-3.5">
                    <CheckCircle :size="17" class="text-[#14532A] flex-shrink-0 mt-0.25" />
                    <div class="flex flex-col gap-0.5">
                        <span class="text-sm font-bold text-[#14532A] leading-[1.2]">All up to date!</span>
                        <span class="text-xs text-[#166534] leading-[1.45]">Your site now shows the latest info from your Google Business Profile.</span>
                    </div>
                </div>

                <!-- Error alert -->
                <div v-else-if="refreshFailed" class="flex items-start gap-2.5 p-3 bg-[#FEF2F2] border-[1.5px] border-[#FECACA] rounded-[10px] mb-3.5">
                    <AlertCircle :size="17" class="text-[#991B1B] flex-shrink-0 mt-0.25" />
                    <div class="flex flex-col">
                        <span class="text-sm font-bold text-[#991B1B] leading-[1.2]">Something went wrong</span>
                        <span class="text-xs text-[#B91C1C] leading-[1.45] mt-0.5">We couldn't reach Google. Please try again in a moment.</span>
                    </div>
                </div>

                <!-- Default button -->
                <button
                    v-if="!isRefreshing"
                    class="w-full inline-flex items-center justify-center gap-2 h-11 px-4.5 rounded-[10px] bg-brand-surface text-brand-ink border-[1.5px] border-brand-line font-semibold text-sm cursor-pointer hover:bg-brand-panel transition-colors disabled:opacity-50 disabled:cursor-not-allowed mb-3"
                    :disabled="isRefreshing"
                    @click="refreshFromGoogle"
                >
                    <RefreshCw :size="16" />
                    <span>Refresh from Google</span>
                </button>

                <p class="text-xs text-brand-ink-soft leading-[1.55] mt-3 m-0">
                    If something looks wrong on your site, check your Google Business Profile first — that's the easiest place to fix it.
                </p>
            </div>
        </div>
    </div>
</template>
