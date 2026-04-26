<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import { site as dashboardSite } from '@/routes/dashboard';
import { inject, ref, computed, watch } from 'vue';
import {
    Loader2, CheckCircle2, XCircle, RefreshCw,
    Sparkles, Link2, Unlink, Lock, Unlock,
    Image, Upload, X,
} from 'lucide-vue-next';
import { toast } from 'vue-sonner';
import { Input } from '@/components/ui/input';
import { RadioGroup } from '@/components/ui/radio-group';

const site      = inject('site') as any;
const isPremium = inject('isPremium') as boolean;
const serverIp  = inject('serverIp') as string;

const page = usePage();

const saving    = ref(false);
const verifying = ref(false);

// DNS verification state — seeded from what the server already knows
const domainVerified     = ref<boolean>(site?.domain_verified ?? false);
const dnsAutoConfigured  = ref<boolean>(site?.dns_auto_configured ?? false);
const connectedProvider  = ref<string | null>(site?.connected_provider ?? null);
const verifyChecks       = ref<null | { apex: any; www: any }>(null);
const verifyError        = ref('');

// Suggest a subdomain slug derived from the business name when none has been set
const suggestedSubdomain = computed(() => {
    const name: string = site?.data?.displayName?.text ?? '';
    return name
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-|-$/g, '');
});

// ── Favicon ───────────────────────────────────────────────────────────────────
const faviconInput      = ref<HTMLInputElement | null>(null);
const faviconPreview    = ref<string | null>(site?.data?.overrides?.favicon_path ?? null);
const faviconPendingType = ref<'upload' | 'logo' | 'initials' | ''>('');

// Render a 64×64 initials favicon on a canvas and return a data URL for
// immediate preview — mirrors the server-side GD logic so the user sees
// exactly what will be saved.
function buildInitialsDataUrl(): string {
    const name    = (site?.data?.displayName?.text as string | undefined) ?? 'Business';
    const words   = name.trim().split(/\s+/);
    let initials  = words
        .slice(0, 2)
        .map((w: string) => w[0]?.toUpperCase() ?? '')
        .filter((c: string) => /[A-Z]/.test(c))
        .join('');
    if (!initials) initials = 'B';

    const primary  = (site?.data?.overrides?.palette?.primary as string | undefined) ?? '#1e293b';
    const canvas   = document.createElement('canvas');
    canvas.width   = 64;
    canvas.height  = 64;
    const ctx      = canvas.getContext('2d')!;
    ctx.fillStyle  = primary;
    ctx.fillRect(0, 0, 64, 64);
    ctx.fillStyle  = '#ffffff';
    ctx.font       = `bold ${initials.length === 1 ? 28 : 22}px -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif`;
    ctx.textAlign  = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillText(initials, 32, 33);
    return canvas.toDataURL('image/png');
}

function selectFaviconType(type: 'upload' | 'logo' | 'initials') {
    faviconPendingType.value = type;
    form.favicon_type = type;
    form.favicon = null;
    form.favicon_data = '';
    if (type === 'upload') {
        faviconInput.value?.click();
    } else if (type === 'initials') {
        // Generate the favicon in the browser — send this data URL to the backend
        // so no server-side GD image processing is required.
        const dataUrl = buildInitialsDataUrl();
        form.favicon_data = dataUrl;
        faviconPreview.value = dataUrl;
    } else if (type === 'logo') {
        // Pass the logo path so the backend can copy/alias it as the favicon.
        const logoPath = site?.data?.overrides?.logo_path as string | undefined;
        form.favicon_data = logoPath ?? '';
        faviconPreview.value = logoPath ?? faviconPreview.value;
    }
}

function onFaviconChange(event: Event) {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    if (file) {
        form.favicon = file;
        faviconPreview.value = URL.createObjectURL(file);
    }
}

function clearFavicon() {
    faviconPendingType.value = '';
    form.favicon_type = 'clear';
    form.favicon = null;
    form.favicon_data = '';
    faviconPreview.value = null;
}

const form = useForm<{
    domain_type:   string;
    subdomain:     string | null;
    custom_domain: string | null;
    is_private:    boolean;
    favicon_type:  string;
    favicon:       File | null;
    favicon_data:  string;   // canvas data URL (initials) or logo path (logo)
}>({
    domain_type:   site.domain_type,
    subdomain:     site.subdomain,
    custom_domain: site.custom_domain,
    is_private:    site.is_private ?? false,
    favicon_type:  '',
    favicon:       null,
    favicon_data:  '',
});

// Reset verification UI when the domain input changes
watch(() => form.custom_domain, () => {
    domainVerified.value    = false;
    dnsAutoConfigured.value = false;
    connectedProvider.value = null;
    verifyChecks.value      = null;
    verifyError.value       = '';
});

const saveForm = () => {
    saving.value = true;

    form.post(dashboardSite.url(), {
        forceFormData: true,
        onSuccess: () => {
            const updatedSite = (page.props.site as any);
            domainVerified.value    = updatedSite?.domain_verified ?? false;
            dnsAutoConfigured.value = updatedSite?.dns_auto_configured ?? false;
            connectedProvider.value = updatedSite?.connected_provider ?? null;
            // Reset favicon fields so re-saving doesn't re-process
            form.favicon_type = '';
            form.favicon = null;
            form.favicon_data = '';
            faviconPendingType.value = '';
            faviconPreview.value = (page.props.site as any)?.data?.overrides?.favicon_path ?? null;
            toast('Saved!');
            saving.value = false;
        },
        onError: () => {
            saving.value = false;
        },
    });
};

const verifyDomain = async () => {
    verifying.value  = true;
    verifyChecks.value = null;
    verifyError.value  = '';

    try {
        const response = await fetch('/dashboard/site/verify-domain', {
            method:  'POST',
            headers: {
                'Content-Type':     'application/json',
                'X-CSRF-TOKEN':     (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        const data = await response.json();

        if (data.verified) {
            domainVerified.value = true;
            verifyChecks.value   = data.checks;
            toast('Domain verified! Your site is now live at ' + form.custom_domain);
        } else if (data.checks) {
            domainVerified.value = false;
            verifyChecks.value   = data.checks;
        } else {
            verifyError.value = data.error ?? 'Verification failed. Please try again.';
        }
    } catch {
        verifyError.value = 'Could not reach the verification server. Please try again.';
    } finally {
        verifying.value = false;
    }
};

// True when using a custom domain that hasn't been verified yet
const showDnsInstructions = computed(
    () => form.domain_type === 'custom' && form.custom_domain && !domainVerified.value,
);

// Whether to show the auto-connect section
const showAutoConnect = computed(
    () => form.domain_type === 'custom' && form.custom_domain && !connectedProvider.value && !domainVerified.value,
);

// ── Domain Connect (primary — works for GoDaddy, Cloudflare, IONOS, …) ────────
// This is an open standard supported by the most popular registrars.
// The user sees a single "Allow 321Sites to configure your DNS?" consent
// screen at their own registrar — no API keys, no technical knowledge needed.

type DcState = 'idle' | 'probing' | 'unsupported' | 'redirecting';
const dcState   = ref<DcState>('idle');
const dcMessage = ref('');

const csrf = () =>
    (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '';

const autoConfigureDns = async () => {
    dcState.value   = 'probing';
    dcMessage.value = '';

    try {
        const response = await fetch('/dashboard/domain/connect/probe', {
            method: 'POST',
            headers: {
                'Content-Type':     'application/json',
                'X-CSRF-TOKEN':     csrf(),
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        const data = await response.json();

        if (data.supported && data.redirectTo) {
            // Provider supports Domain Connect — send user to their registrar's
            // consent screen. They'll be redirected back to /dashboard after
            // approving (or cancelling).
            dcState.value = 'redirecting';
            window.location.href = data.redirectTo;
        } else {
            // Provider not yet on Domain Connect — show fallback options
            dcState.value   = 'unsupported';
            dcMessage.value = data.message ?? '';
        }
    } catch {
        dcState.value   = 'unsupported';
        dcMessage.value = 'Could not check your domain provider. Please use the manual steps below.';
    }
};

// ── Manual DNS toggle (kept for the Tier 1 fallback below the custom domain card) ─
const showManualDns = ref(false);

// ── Disconnect provider ───────────────────────────────────────────────────────
const disconnecting = ref(false);

const disconnectProvider = async () => {
    disconnecting.value = true;
    try {
        const response = await fetch('/dashboard/domain/disconnect', {
            method: 'POST',
            headers: {
                'Content-Type':     'application/json',
                'X-CSRF-TOKEN':     csrf(),
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (response.ok) {
            connectedProvider.value  = null;
            dnsAutoConfigured.value  = false;
            dcState.value            = 'idle';
            toast('Provider disconnected.');
        }
    } catch {
        toast('Could not disconnect. Please try again.');
    } finally {
        disconnecting.value = false;
    }
};

// Provider label helpers
const providerLabel = computed(() => {
    if (connectedProvider.value === 'domain_connect') return 'your domain provider';
    if (connectedProvider.value === 'cloudflare')     return 'Cloudflare';
    if (connectedProvider.value === 'godaddy')        return 'GoDaddy';
    return connectedProvider.value ?? 'your domain provider';
});

</script>

<template>
    <div class="flex flex-col gap-6 max-w-[820px]">
        <div class="bg-brand-surface border-[1.5px] border-brand-line rounded-[14px] overflow-hidden">
            <div class="px-7 pt-6 pb-5 border-b border-brand-line-soft">
                <div class="text-[18px] font-bold text-brand-ink tracking-[-0.2px]">Where your site lives on the internet</div>
                <div class="text-[14px] text-brand-ink-soft mt-1 leading-[1.5]">This is the link you'll give to customers, put on your van, or print on your cards.</div>
            </div>
            <div class="p-7">
                <RadioGroup v-model="form.domain_type" class="flex flex-col gap-3">

                    <!-- ── Free subdomain ─────────────────────────────────── -->
                    <label
                        class="flex flex-col gap-0 p-5 border-2 border-brand-line rounded-[12px] cursor-pointer bg-brand-surface transition-all duration-100"
                        :class="{ 'border-brand-blue bg-brand-blue-soft': form.domain_type === 'subdomain' }"
                        @click="form.domain_type = 'subdomain'"
                    >
                        <div class="flex items-start gap-3.5">
                            <span class="w-[22px] h-[22px] rounded-full border-2 border-brand-line bg-brand-surface flex items-center justify-center flex-shrink-0 mt-0.5 transition-all duration-100" :class="{ 'border-brand-blue bg-brand-blue': form.domain_type === 'subdomain' }">
                                <span v-if="form.domain_type === 'subdomain'" class="w-2 h-2 rounded-full bg-white" />
                            </span>
                            <div>
                                <div class="text-base font-semibold text-brand-ink leading-[1.3]">A free 321Sites address <span class="text-brand-ink-soft font-medium">(recommended)</span></div>
                                <div class="text-sm text-brand-ink-soft mt-1 leading-[1.4]">Looks like: yourname.321sites.com — free, works straight away, no setup.</div>
                            </div>
                        </div>
                        <div v-if="form.domain_type === 'subdomain'" class="flex items-center mt-3.5 pl-9">
                            <Input
                                type="text"
                                name="subdomain"
                                id="subdomain"
                                :placeholder="suggestedSubdomain || 'yourbusiness'"
                                v-model="form.subdomain"
                                class="w-[220px] h-12 px-3 border border-brand-line rounded-[10px_0_0_10px] text-sm text-brand-ink bg-brand-surface focus:outline-none focus:border-brand-blue"
                                required
                                @click.stop
                            />
                            <span class="h-12 flex items-center px-3.5 border-2 border-l-0 border-brand-line rounded-[0_10px_10px_0] bg-brand-panel text-brand-ink-soft text-[15px] font-medium whitespace-nowrap">.{{ $page.props.appDomain || '321sites.com' }}</span>
                        </div>
                        <p v-if="form.errors.subdomain" class="text-sm text-brand-danger font-medium mt-2">{{ form.errors.subdomain }}</p>
                    </label>

                    <!-- ── Custom domain ──────────────────────────────────── -->
                    <label
                        class="flex flex-col gap-0 p-5 border-2 border-brand-line rounded-[12px] cursor-pointer bg-brand-surface transition-all duration-100"
                        :class="[
                            { 'border-brand-blue bg-brand-blue-soft': form.domain_type === 'custom' },
                            { 'opacity-55 cursor-not-allowed': !isPremium },
                        ]"
                        @click="isPremium && (form.domain_type = 'custom')"
                    >
                        <div class="flex items-start gap-3.5">
                            <span class="w-[22px] h-[22px] rounded-full border-2 border-brand-line bg-brand-surface flex items-center justify-center flex-shrink-0 mt-0.5 transition-all duration-100" :class="{ 'border-brand-blue bg-brand-blue': form.domain_type === 'custom' }">
                                <span v-if="form.domain_type === 'custom'" class="w-2 h-2 rounded-full bg-white" />
                            </span>
                            <div>
                                <div class="text-base font-semibold text-brand-ink leading-[1.3]">
                                    Your own web address (e.g. yourbusiness.co.uk)
                                    <span v-if="!isPremium" class="inline-flex items-center gap-1 ml-2 px-2 py-0.5 rounded-full bg-yellow-100 text-amber-900 text-xs font-bold">
                                        <Sparkles :size="11" /> Premium
                                    </span>
                                    <span v-else-if="domainVerified && form.domain_type === 'custom'" class="inline-flex items-center gap-1 ml-2 px-2 py-0.5 rounded-full bg-green-100 text-green-800 text-xs font-bold">
                                        <CheckCircle2 :size="13" /> Verified
                                    </span>
                                </div>
                                <div class="text-sm text-brand-ink-soft mt-1 leading-[1.4]">Use a domain you've already bought. We'll walk you through the setup.</div>
                            </div>
                        </div>

                        <div v-if="form.domain_type === 'custom'" class="flex flex-col gap-3.5 mt-3.5 pl-9" @click.stop>
                            <Input
                                type="text"
                                name="custom_domain"
                                id="custom_domain"
                                placeholder="yourdomain.com"
                                v-model="form.custom_domain"
                                class="w-full h-12 px-3 border border-brand-line rounded-lg text-sm text-brand-ink bg-brand-surface focus:outline-none focus:border-brand-blue"
                                required
                            />
                            <p v-if="form.errors.custom_domain" class="text-sm text-brand-danger font-medium">{{ form.errors.custom_domain }}</p>

                            <!-- Connected state -->
                            <template v-if="connectedProvider && dnsAutoConfigured">
                                <div class="flex items-start gap-3 p-3.5 rounded-[10px] bg-green-50 border-[1.5px] border-green-300">
                                    <CheckCircle2 :size="18" class="text-green-700 flex-shrink-0 mt-0.5" />
                                    <div>
                                        <p class="text-[15px] font-bold text-green-800 m-0 mb-0.5">Connected to {{ providerLabel }} ✓</p>
                                        <p class="text-sm text-green-700 m-0 leading-[1.5]">
                                            We've automatically set up your DNS. Your site should be live at
                                            <strong>{{ form.custom_domain }}</strong> within a few minutes.
                                        </p>
                                        <button type="button" class="inline-flex items-center gap-1 mt-2 bg-transparent border-none p-0 font-family-inherit text-xs font-semibold text-brand-blue cursor-pointer underline-transparent hover:underline disabled:opacity-50 disabled:cursor-not-allowed" :disabled="disconnecting" @click="disconnectProvider">
                                            <Unlink :size="13" />
                                            {{ disconnecting ? 'Disconnecting…' : 'Disconnect provider' }}
                                        </button>
                                    </div>
                                </div>
                            </template>

                            <!-- Auto-connect -->
                            <template v-else-if="showAutoConnect">
                                <div class="flex flex-col gap-3.5 p-4 rounded-[12px] border-2 border-dashed border-brand-line bg-brand-panel">
                                    <template v-if="dcState !== 'unsupported'">
                                        <div class="flex items-start gap-3">
                                            <span class="text-2xl flex-shrink-0 leading-none">⚡</span>
                                            <div>
                                                <p class="text-base font-bold text-brand-ink m-0 mb-1">Skip the technical stuff</p>
                                                <p class="text-sm text-brand-ink-soft m-0 leading-[1.5]">Click below and we'll automatically configure your DNS — no technical knowledge needed. Works with GoDaddy, IONOS, and most popular registrars.</p>
                                            </div>
                                        </div>
                                        <button
                                            type="button"
                                            class="inline-flex items-center gap-2 h-11 px-4.5 border-[1.5px] border-brand-blue rounded-[10px] bg-brand-blue text-white font-family-inherit text-[15px] font-semibold cursor-pointer transition-opacity duration-100 disabled:opacity-50 disabled:cursor-not-allowed"
                                            :disabled="dcState === 'probing' || dcState === 'redirecting'"
                                            @click="autoConfigureDns"
                                        >
                                            <Loader2 v-if="dcState === 'probing' || dcState === 'redirecting'" :size="18" class="animate-spin" />
                                            <Link2 v-else :size="18" />
                                            {{
                                                dcState === 'probing'     ? 'Checking your domain provider…' :
                                                dcState === 'redirecting' ? 'Taking you to your provider…' :
                                                                            'Auto-configure DNS'
                                            }}
                                        </button>
                                        <p class="text-xs text-brand-ink-soft m-0 leading-[1.5]">You'll be taken to your domain provider's website to approve the setup. Takes a few seconds.</p>
                                    </template>

                                    <!-- Unsupported — show DNS records -->
                                    <template v-else>
                                        <div class="p-3.5 rounded-[10px] bg-amber-50 border-[1.5px] border-amber-200">
                                            <div>
                                                <p class="text-[15px] font-bold text-amber-900 m-0 mb-0.5">We couldn't auto-configure your domain</p>
                                                <p class="text-sm text-amber-800 m-0 leading-[1.5]">{{ dcMessage || "Your domain provider doesn't support automatic configuration yet." }} You'll need to add two DNS records manually — it only takes a few minutes.</p>
                                            </div>
                                        </div>
                                        <div class="border-[1.5px] border-brand-line rounded-[10px] overflow-hidden text-sm">
                                            <div class="grid grid-cols-[60px_1fr_1fr] px-3.5 py-2 bg-brand-panel text-[11px] font-bold text-brand-ink-soft uppercase tracking-[0.5px] gap-2.5">
                                                <span>Type</span><span>Host / Name</span><span>Value / Points to</span>
                                            </div>
                                            <div class="grid grid-cols-[60px_1fr_1fr] px-3.5 py-2.5 gap-2.5 items-center border-t border-brand-line-soft">
                                                <span class="font-mono">A</span>
                                                <span class="font-mono">@</span>
                                                <span class="font-mono break-all">{{ serverIp || 'YOUR_SERVER_IP' }}</span>
                                            </div>
                                            <div class="grid grid-cols-[60px_1fr_1fr] px-3.5 py-2.5 gap-2.5 items-center border-t border-brand-line-soft bg-slate-50 dark:bg-slate-900">
                                                <span class="font-mono">A</span>
                                                <span class="font-mono">www</span>
                                                <span class="font-mono break-all">{{ serverIp || 'YOUR_SERVER_IP' }}</span>
                                            </div>
                                        </div>
                                        <template v-if="verifyChecks">
                                            <div class="flex flex-col gap-2">
                                                <div v-for="(check, key) in verifyChecks" :key="key" class="flex items-center gap-2 text-sm">
                                                    <CheckCircle2 v-if="check.ok" :size="16" class="text-brand-success flex-shrink-0" />
                                                    <XCircle      v-else          :size="16" class="text-brand-danger flex-shrink-0" />
                                                    <span class="font-mono text-xs">{{ check.record }}</span>
                                                    <span class="text-brand-ink-soft">{{ check.ok ? 'pointing correctly' : `not yet pointing to ${check.expected}` }}</span>
                                                </div>
                                            </div>
                                        </template>
                                        <p v-if="verifyError" class="text-sm text-brand-danger font-medium m-0">{{ verifyError }}</p>
                                        <div class="flex items-center gap-4 flex-wrap">
                                            <button type="button" class="inline-flex items-center gap-2 h-11 px-4 border-[1.5px] border-brand-line rounded-[10px] bg-brand-surface text-brand-ink font-family-inherit text-[15px] font-semibold cursor-pointer transition-all duration-100 disabled:opacity-50 disabled:cursor-not-allowed hover:bg-brand-panel" :disabled="verifying" @click="verifyDomain">
                                                <Loader2 v-if="verifying" :size="16" class="animate-spin" />
                                                <RefreshCw v-else :size="16" />
                                                {{ verifying ? 'Checking DNS…' : 'Check DNS' }}
                                            </button>
                                            <button type="button" class="inline-flex items-center gap-1 bg-transparent border-none p-0 font-family-inherit text-xs font-semibold text-brand-blue cursor-pointer underline-transparent hover:underline" @click="dcState = 'idle'">Try auto-configure again</button>
                                        </div>
                                    </template>
                                </div>
                            </template>

                            <!-- Already verified (no provider) -->
                            <div v-else-if="domainVerified && !connectedProvider" class="flex items-start gap-3 p-3.5 rounded-[10px] bg-green-50 border-[1.5px] border-green-300">
                                <CheckCircle2 :size="18" class="text-green-700 flex-shrink-0 mt-0.5" />
                                <div>
                                    <p class="text-[15px] font-bold text-green-800 m-0 mb-0.5">Domain verified</p>
                                    <p class="text-sm text-green-700 m-0 leading-[1.5]">Your site is live at <strong>{{ form.custom_domain }}</strong>.</p>
                                </div>
                            </div>

                            <!-- Manual DNS fallback -->
                            <template v-if="showDnsInstructions && (showManualDns || (!showAutoConnect && !connectedProvider))">
                                <div class="p-3.5 rounded-[10px] bg-amber-50 border-[1.5px] border-amber-200">
                                    <div>
                                        <p class="text-[15px] font-bold text-amber-900 m-0 mb-0.5">Add these two DNS records to your domain</p>
                                        <p class="text-sm text-amber-800 m-0 leading-[1.5]">Log in to wherever you bought your domain and add these records. DNS changes can take up to 48 hours.</p>
                                    </div>
                                </div>
                                <div class="border-[1.5px] border-brand-line rounded-[10px] overflow-hidden text-sm">
                                    <div class="grid grid-cols-[60px_1fr_1fr] px-3.5 py-2 bg-brand-panel text-[11px] font-bold text-brand-ink-soft uppercase tracking-[0.5px] gap-2.5">
                                        <span>Type</span><span>Host / Name</span><span>Value / Points to</span>
                                    </div>
                                    <div class="grid grid-cols-[60px_1fr_1fr] px-3.5 py-2.5 gap-2.5 items-center border-t border-brand-line-soft">
                                        <span class="font-mono">A</span>
                                        <span class="font-mono">@</span>
                                        <span class="font-mono break-all">{{ serverIp || 'YOUR_SERVER_IP' }}</span>
                                    </div>
                                    <div class="grid grid-cols-[60px_1fr_1fr] px-3.5 py-2.5 gap-2.5 items-center border-t border-brand-line-soft bg-slate-50 dark:bg-slate-900">
                                        <span class="font-mono">A</span>
                                        <span class="font-mono">www</span>
                                        <span class="font-mono break-all">{{ serverIp || 'YOUR_SERVER_IP' }}</span>
                                    </div>
                                </div>
                                <template v-if="verifyChecks">
                                    <div class="flex flex-col gap-2">
                                        <div v-for="(check, key) in verifyChecks" :key="key" class="flex items-center gap-2 text-sm">
                                            <CheckCircle2 v-if="check.ok" :size="16" class="text-brand-success flex-shrink-0" />
                                            <XCircle      v-else          :size="16" class="text-brand-danger flex-shrink-0" />
                                            <span class="font-mono text-xs">{{ check.record }}</span>
                                            <span class="text-brand-ink-soft">{{ check.ok ? 'pointing correctly' : `not yet pointing to ${check.expected}` }}</span>
                                        </div>
                                    </div>
                                </template>
                                <p v-if="verifyError" class="text-sm text-brand-danger font-medium m-0">{{ verifyError }}</p>
                                <button type="button" class="inline-flex items-center gap-2 h-11 px-4 border-[1.5px] border-brand-line rounded-[10px] bg-brand-surface text-brand-ink font-family-inherit text-[15px] font-semibold cursor-pointer transition-all duration-100 disabled:opacity-50 disabled:cursor-not-allowed hover:bg-brand-panel" :disabled="verifying" @click="verifyDomain">
                                    <Loader2 v-if="verifying" :size="16" class="animate-spin" />
                                    <RefreshCw v-else :size="16" />
                                    {{ verifying ? 'Checking DNS…' : 'Check DNS' }}
                                </button>
                            </template>
                        </div>
                    </label>

                </RadioGroup>
            </div>
        </div>

        <!-- Privacy toggle -->
        <div class="bg-brand-surface border-[1.5px] border-brand-line rounded-[14px] overflow-hidden">
            <div class="px-7 pt-6 pb-5 border-b border-brand-line-soft">
                <div class="text-[18px] font-bold text-brand-ink tracking-[-0.2px]">Visibility</div>
                <div class="text-[14px] text-brand-ink-soft mt-1 leading-[1.5]">Control who can see your site.</div>
            </div>
            <div class="p-7">
                <button
                    type="button"
                    class="flex items-center gap-4 w-full p-5 border-2 border-brand-line rounded-[12px] bg-brand-surface cursor-pointer text-left transition-all duration-150 hover:border-brand-blue"
                    :class="{ 'border-amber-400 bg-amber-50': form.is_private }"
                    @click="form.is_private = !form.is_private"
                    :aria-pressed="form.is_private"
                >
                    <span class="flex-shrink-0 text-brand-ink-soft" :class="{ 'text-amber-700': form.is_private }">
                        <Lock v-if="form.is_private" :size="20" />
                        <Unlock v-else :size="20" />
                    </span>
                    <span class="flex-1 flex flex-col gap-0.5">
                        <span class="text-[15px] font-semibold text-brand-ink leading-[1.3]" :class="{ 'text-amber-800': form.is_private }">
                            {{ form.is_private ? 'Private — only visible to you when logged in' : 'Public — anyone with the link can visit' }}
                        </span>
                        <span class="text-xs text-brand-ink-soft leading-[1.4]" :class="{ 'text-amber-700 opacity-80': form.is_private }">
                            {{ form.is_private
                                ? 'Visitors will be redirected to the login page. You can still view your site by logging in.'
                                : 'Your site is accessible to everyone at its web address.' }}
                        </span>
                    </span>
                    <span class="flex-shrink-0 px-2.5 py-0.5 rounded-full text-xs font-bold bg-brand-panel text-brand-ink-soft border border-brand-line" :class="{ 'bg-amber-100 text-amber-800 border-amber-300': form.is_private }">
                        {{ form.is_private ? 'Private' : 'Public' }}
                    </span>
                </button>
            </div>
        </div>

        <!-- Favicon -->
        <div class="bg-brand-surface border-[1.5px] border-brand-line rounded-[14px] overflow-hidden">
            <div class="px-7 pt-6 pb-5 border-b border-brand-line-soft">
                <div class="text-[18px] font-bold text-brand-ink tracking-[-0.2px]">Browser tab icon</div>
                <div class="text-[14px] text-brand-ink-soft mt-1 leading-[1.5]">The small icon that appears in browser tabs, bookmarks, and search results.</div>
            </div>
            <div class="p-7">

                <!-- Current favicon preview -->
                <div v-if="faviconPreview" class="flex items-center gap-2.5 p-3.5 rounded-[10px] bg-brand-panel border-[1.5px] border-brand-line mb-3.5">
                    <img :src="faviconPreview" alt="Current favicon" class="w-8 h-8 rounded-[6px] object-contain bg-white border border-brand-line" />
                    <span class="text-sm text-brand-ink flex-1">Current icon</span>
                    <button type="button" class="flex items-center justify-center w-7 h-7 rounded-[6px] border-none bg-transparent text-brand-ink-soft cursor-pointer transition-all duration-100 hover:text-brand-danger hover:bg-red-100" @click="clearFavicon" aria-label="Remove favicon">
                        <X :size="14" />
                    </button>
                </div>

                <!-- Option buttons -->
                <div class="flex gap-2 flex-wrap mb-3.5">
                    <button
                        type="button"
                        class="flex items-center gap-2 px-3.5 py-2.5 rounded-[10px] border-[1.5px] border-brand-line bg-brand-surface text-brand-ink font-family-inherit text-sm font-semibold cursor-pointer transition-all duration-100 hover:border-brand-blue hover:bg-brand-blue-soft hover:text-brand-blue"
                        :class="{ 'border-brand-blue bg-brand-blue-soft text-brand-blue': faviconPendingType === 'initials' }"
                        @click="selectFaviconType('initials')"
                    >
                        <span class="w-4.5 h-4.5 flex items-center justify-center text-xs font-black tracking-[-0.5px]">Aa</span>
                        <span>Business initials</span>
                    </button>

                    <button
                        type="button"
                        class="flex items-center gap-2 px-3.5 py-2.5 rounded-[10px] border-[1.5px] border-brand-line bg-brand-surface text-brand-ink font-family-inherit text-sm font-semibold cursor-pointer transition-all duration-100 hover:border-brand-blue hover:bg-brand-blue-soft hover:text-brand-blue disabled:opacity-40 disabled:cursor-not-allowed"
                        :class="{ 'border-brand-blue bg-brand-blue-soft text-brand-blue': faviconPendingType === 'logo' }"
                        :disabled="!site?.data?.overrides?.logo_path"
                        @click="selectFaviconType('logo')"
                        :title="!site?.data?.overrides?.logo_path ? 'Upload a logo first' : 'Generate from your logo'"
                    >
                        <Image class="flex-shrink-0" :size="18" />
                        <span>Use logo</span>
                    </button>

                    <button
                        type="button"
                        class="flex items-center gap-2 px-3.5 py-2.5 rounded-[10px] border-[1.5px] border-brand-line bg-brand-surface text-brand-ink font-family-inherit text-sm font-semibold cursor-pointer transition-all duration-100 hover:border-brand-blue hover:bg-brand-blue-soft hover:text-brand-blue"
                        :class="{ 'border-brand-blue bg-brand-blue-soft text-brand-blue': faviconPendingType === 'upload' }"
                        @click="selectFaviconType('upload')"
                    >
                        <Upload class="flex-shrink-0" :size="18" />
                        <span>Upload image</span>
                    </button>
                </div>

                <p v-if="faviconPendingType === 'initials'" class="text-xs text-brand-success m-0 leading-[1.5] flex items-center gap-1.5">
                    <CheckCircle2 :size="14" style="flex-shrink:0" /> Looking good — hit Save to apply this icon to your site.
                </p>
                <p v-else-if="faviconPendingType === 'logo'" class="text-xs text-brand-ink-soft m-0 leading-[1.5]">
                    Your logo will be cropped to a square. Save to apply.
                </p>
                <p v-else-if="faviconPendingType === 'upload' && !form.favicon" class="text-xs text-brand-ink-soft m-0 leading-[1.5]">
                    Choose an image file (PNG or JPG, max 512 KB). Square images work best.
                </p>
                <p v-else-if="faviconPendingType === 'upload' && form.favicon" class="text-xs text-brand-success m-0 leading-[1.5] flex items-center gap-1.5">
                    <CheckCircle2 :size="14" style="flex-shrink:0" /> Image selected — save to apply.
                </p>

                <!-- Hidden file input -->
                <input
                    ref="faviconInput"
                    type="file"
                    accept="image/*"
                    style="display: none"
                    @change="onFaviconChange"
                />
            </div>
        </div>

        <!-- Save -->
        <div class="flex gap-3 justify-end">
            <button type="button" class="inline-flex items-center gap-2 h-[52px] px-6 border-[1.5px] border-brand-blue rounded-[10px] bg-brand-blue text-white font-family-inherit text-base font-semibold cursor-pointer transition-opacity duration-100 disabled:opacity-50 disabled:cursor-not-allowed" :disabled="saving" @click="saveForm">
                <Loader2 v-if="saving" :size="18" class="animate-spin" />
                {{ saving ? 'Saving…' : 'Save web address' }}
            </button>
        </div>
    </div>
</template>
