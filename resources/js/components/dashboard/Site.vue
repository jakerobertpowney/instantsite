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
    <div class="sa-wrap">
        <div class="sa-card">
            <div class="sa-card__head">
                <div class="sa-card__title">Where your site lives on the internet</div>
                <div class="sa-card__sub">This is the link you'll give to customers, put on your van, or print on your cards.</div>
            </div>
            <div class="sa-card__body">
                <RadioGroup v-model="form.domain_type" class="sa-radio-group">

                    <!-- ── Free subdomain ─────────────────────────────────── -->
                    <label
                        class="sa-radio-card"
                        :class="{ 'sa-radio-card--active': form.domain_type === 'subdomain' }"
                        @click="form.domain_type = 'subdomain'"
                    >
                        <div class="sa-radio-card__header">
                            <span class="sa-radio-dot" :class="{ 'sa-radio-dot--on': form.domain_type === 'subdomain' }">
                                <span v-if="form.domain_type === 'subdomain'" class="sa-radio-dot__fill" />
                            </span>
                            <div>
                                <div class="sa-radio-card__label">A free 321Sites address <span class="sa-recommended">(recommended)</span></div>
                                <div class="sa-radio-card__hint">Looks like: yourname.321sites.com — free, works straight away, no setup.</div>
                            </div>
                        </div>
                        <div v-if="form.domain_type === 'subdomain'" class="sa-subdomain-row">
                            <Input
                                type="text"
                                name="subdomain"
                                id="subdomain"
                                :placeholder="suggestedSubdomain || 'yourbusiness'"
                                v-model="form.subdomain"
                                class="sa-input sa-input--inline"
                                required
                                @click.stop
                            />
                            <span class="sa-subdomain-suffix">.{{ $page.props.appDomain || '321sites.com' }}</span>
                        </div>
                        <p v-if="form.errors.subdomain" class="sa-error">{{ form.errors.subdomain }}</p>
                    </label>

                    <!-- ── Custom domain ──────────────────────────────────── -->
                    <label
                        class="sa-radio-card"
                        :class="[
                            { 'sa-radio-card--active': form.domain_type === 'custom' },
                            { 'sa-radio-card--disabled': !isPremium },
                        ]"
                        @click="isPremium && (form.domain_type = 'custom')"
                    >
                        <div class="sa-radio-card__header">
                            <span class="sa-radio-dot" :class="{ 'sa-radio-dot--on': form.domain_type === 'custom' }">
                                <span v-if="form.domain_type === 'custom'" class="sa-radio-dot__fill" />
                            </span>
                            <div>
                                <div class="sa-radio-card__label">
                                    Your own web address (e.g. yourbusiness.co.uk)
                                    <span v-if="!isPremium" class="sa-premium-badge">
                                        <Sparkles class="sa-premium-badge__icon" :size="11" /> Premium
                                    </span>
                                    <span v-else-if="domainVerified && form.domain_type === 'custom'" class="sa-verified-badge">
                                        <CheckCircle2 :size="13" /> Verified
                                    </span>
                                </div>
                                <div class="sa-radio-card__hint">Use a domain you've already bought. We'll walk you through the setup.</div>
                            </div>
                        </div>

                        <div v-if="form.domain_type === 'custom'" class="sa-custom-body" @click.stop>
                            <Input
                                type="text"
                                name="custom_domain"
                                id="custom_domain"
                                placeholder="yourdomain.com"
                                v-model="form.custom_domain"
                                class="sa-input"
                                required
                            />
                            <p v-if="form.errors.custom_domain" class="sa-error">{{ form.errors.custom_domain }}</p>

                            <!-- Connected state -->
                            <template v-if="connectedProvider && dnsAutoConfigured">
                                <div class="sa-status-banner sa-status-banner--success">
                                    <CheckCircle2 :size="18" class="sa-status-banner__icon" />
                                    <div>
                                        <p class="sa-status-banner__title">Connected to {{ providerLabel }} ✓</p>
                                        <p class="sa-status-banner__body">
                                            We've automatically set up your DNS. Your site should be live at
                                            <strong>{{ form.custom_domain }}</strong> within a few minutes.
                                        </p>
                                        <button type="button" class="sa-link-btn" :disabled="disconnecting" @click="disconnectProvider">
                                            <Unlink :size="13" />
                                            {{ disconnecting ? 'Disconnecting…' : 'Disconnect provider' }}
                                        </button>
                                    </div>
                                </div>
                            </template>

                            <!-- Auto-connect -->
                            <template v-else-if="showAutoConnect">
                                <div class="sa-autoconnect">
                                    <template v-if="dcState !== 'unsupported'">
                                        <div class="sa-autoconnect__intro">
                                            <span class="sa-autoconnect__bolt">⚡</span>
                                            <div>
                                                <p class="sa-autoconnect__title">Skip the technical stuff</p>
                                                <p class="sa-autoconnect__hint">Click below and we'll automatically configure your DNS — no technical knowledge needed. Works with GoDaddy, IONOS, and most popular registrars.</p>
                                            </div>
                                        </div>
                                        <button
                                            type="button"
                                            class="db-btn db-btn--primary"
                                            :disabled="dcState === 'probing' || dcState === 'redirecting'"
                                            @click="autoConfigureDns"
                                        >
                                            <Loader2 v-if="dcState === 'probing' || dcState === 'redirecting'" :size="18" class="sa-spin" />
                                            <Link2 v-else :size="18" />
                                            {{
                                                dcState === 'probing'     ? 'Checking your domain provider…' :
                                                dcState === 'redirecting' ? 'Taking you to your provider…' :
                                                                            'Auto-configure DNS'
                                            }}
                                        </button>
                                        <p class="sa-note">You'll be taken to your domain provider's website to approve the setup. Takes a few seconds.</p>
                                    </template>

                                    <!-- Unsupported — show DNS records -->
                                    <template v-else>
                                        <div class="sa-status-banner sa-status-banner--warning">
                                            <div>
                                                <p class="sa-status-banner__title">We couldn't auto-configure your domain</p>
                                                <p class="sa-status-banner__body">{{ dcMessage || "Your domain provider doesn't support automatic configuration yet." }} You'll need to add two DNS records manually — it only takes a few minutes.</p>
                                            </div>
                                        </div>
                                        <div class="sa-dns-table">
                                            <div class="sa-dns-table__head">
                                                <span>Type</span><span>Host / Name</span><span>Value / Points to</span>
                                            </div>
                                            <div class="sa-dns-table__row">
                                                <span class="sa-mono">A</span>
                                                <span class="sa-mono">@</span>
                                                <span class="sa-mono sa-break">{{ serverIp || 'YOUR_SERVER_IP' }}</span>
                                            </div>
                                            <div class="sa-dns-table__row sa-dns-table__row--alt">
                                                <span class="sa-mono">A</span>
                                                <span class="sa-mono">www</span>
                                                <span class="sa-mono sa-break">{{ serverIp || 'YOUR_SERVER_IP' }}</span>
                                            </div>
                                        </div>
                                        <template v-if="verifyChecks">
                                            <div class="sa-check-list">
                                                <div v-for="(check, key) in verifyChecks" :key="key" class="sa-check-row">
                                                    <CheckCircle2 v-if="check.ok" :size="16" class="sa-check-row__ok" />
                                                    <XCircle      v-else          :size="16" class="sa-check-row__fail" />
                                                    <span class="sa-mono sa-small">{{ check.record }}</span>
                                                    <span class="sa-muted">{{ check.ok ? 'pointing correctly' : `not yet pointing to ${check.expected}` }}</span>
                                                </div>
                                            </div>
                                        </template>
                                        <p v-if="verifyError" class="sa-error">{{ verifyError }}</p>
                                        <div class="sa-verify-row">
                                            <button type="button" class="db-btn db-btn--secondary" :disabled="verifying" @click="verifyDomain">
                                                <Loader2 v-if="verifying" :size="16" class="sa-spin" />
                                                <RefreshCw v-else :size="16" />
                                                {{ verifying ? 'Checking DNS…' : 'Check DNS' }}
                                            </button>
                                            <button type="button" class="sa-link-btn" @click="dcState = 'idle'">Try auto-configure again</button>
                                        </div>
                                    </template>
                                </div>
                            </template>

                            <!-- Already verified (no provider) -->
                            <div v-else-if="domainVerified && !connectedProvider" class="sa-status-banner sa-status-banner--success">
                                <CheckCircle2 :size="18" class="sa-status-banner__icon" />
                                <div>
                                    <p class="sa-status-banner__title">Domain verified</p>
                                    <p class="sa-status-banner__body">Your site is live at <strong>{{ form.custom_domain }}</strong>.</p>
                                </div>
                            </div>

                            <!-- Manual DNS fallback -->
                            <template v-if="showDnsInstructions && (showManualDns || (!showAutoConnect && !connectedProvider))">
                                <div class="sa-status-banner sa-status-banner--warning">
                                    <div>
                                        <p class="sa-status-banner__title">Add these two DNS records to your domain</p>
                                        <p class="sa-status-banner__body">Log in to wherever you bought your domain and add these records. DNS changes can take up to 48 hours.</p>
                                    </div>
                                </div>
                                <div class="sa-dns-table">
                                    <div class="sa-dns-table__head">
                                        <span>Type</span><span>Host / Name</span><span>Value / Points to</span>
                                    </div>
                                    <div class="sa-dns-table__row">
                                        <span class="sa-mono">A</span>
                                        <span class="sa-mono">@</span>
                                        <span class="sa-mono sa-break">{{ serverIp || 'YOUR_SERVER_IP' }}</span>
                                    </div>
                                    <div class="sa-dns-table__row sa-dns-table__row--alt">
                                        <span class="sa-mono">A</span>
                                        <span class="sa-mono">www</span>
                                        <span class="sa-mono sa-break">{{ serverIp || 'YOUR_SERVER_IP' }}</span>
                                    </div>
                                </div>
                                <template v-if="verifyChecks">
                                    <div class="sa-check-list">
                                        <div v-for="(check, key) in verifyChecks" :key="key" class="sa-check-row">
                                            <CheckCircle2 v-if="check.ok" :size="16" class="sa-check-row__ok" />
                                            <XCircle      v-else          :size="16" class="sa-check-row__fail" />
                                            <span class="sa-mono sa-small">{{ check.record }}</span>
                                            <span class="sa-muted">{{ check.ok ? 'pointing correctly' : `not yet pointing to ${check.expected}` }}</span>
                                        </div>
                                    </div>
                                </template>
                                <p v-if="verifyError" class="sa-error">{{ verifyError }}</p>
                                <button type="button" class="db-btn db-btn--secondary" :disabled="verifying" @click="verifyDomain">
                                    <Loader2 v-if="verifying" :size="16" class="sa-spin" />
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
        <div class="sa-card">
            <div class="sa-card__head">
                <div class="sa-card__title">Visibility</div>
                <div class="sa-card__sub">Control who can see your site.</div>
            </div>
            <div class="sa-card__body">
                <button
                    type="button"
                    class="sa-privacy-toggle"
                    :class="{ 'sa-privacy-toggle--on': form.is_private }"
                    @click="form.is_private = !form.is_private"
                    :aria-pressed="form.is_private"
                >
                    <span class="sa-privacy-toggle__icon">
                        <Lock v-if="form.is_private" :size="20" />
                        <Unlock v-else :size="20" />
                    </span>
                    <span class="sa-privacy-toggle__text">
                        <span class="sa-privacy-toggle__label">
                            {{ form.is_private ? 'Private — only visible to you when logged in' : 'Public — anyone with the link can visit' }}
                        </span>
                        <span class="sa-privacy-toggle__hint">
                            {{ form.is_private
                                ? 'Visitors will be redirected to the login page. You can still view your site by logging in.'
                                : 'Your site is accessible to everyone at its web address.' }}
                        </span>
                    </span>
                    <span class="sa-privacy-pill" :class="{ 'sa-privacy-pill--on': form.is_private }">
                        {{ form.is_private ? 'Private' : 'Public' }}
                    </span>
                </button>
            </div>
        </div>

        <!-- Favicon -->
        <div class="sa-card">
            <div class="sa-card__head">
                <div class="sa-card__title">Browser tab icon</div>
                <div class="sa-card__sub">The small icon that appears in browser tabs, bookmarks, and search results.</div>
            </div>
            <div class="sa-card__body">

                <!-- Current favicon preview -->
                <div v-if="faviconPreview" class="sa-favicon-current">
                    <img :src="faviconPreview" alt="Current favicon" class="sa-favicon-thumb" />
                    <span class="sa-favicon-label">Current icon</span>
                    <button type="button" class="sa-favicon-remove" @click="clearFavicon" aria-label="Remove favicon">
                        <X :size="14" />
                    </button>
                </div>

                <!-- Option buttons -->
                <div class="sa-favicon-options">
                    <button
                        type="button"
                        class="sa-favicon-opt"
                        :class="{ 'sa-favicon-opt--active': faviconPendingType === 'initials' }"
                        @click="selectFaviconType('initials')"
                    >
                        <span class="sa-favicon-opt__icon sa-favicon-opt__icon--initials">Aa</span>
                        <span>Business initials</span>
                    </button>

                    <button
                        type="button"
                        class="sa-favicon-opt"
                        :class="{ 'sa-favicon-opt--active': faviconPendingType === 'logo' }"
                        :disabled="!site?.data?.overrides?.logo_path"
                        @click="selectFaviconType('logo')"
                        :title="!site?.data?.overrides?.logo_path ? 'Upload a logo first' : 'Generate from your logo'"
                    >
                        <Image class="sa-favicon-opt__icon" :size="18" />
                        <span>Use logo</span>
                    </button>

                    <button
                        type="button"
                        class="sa-favicon-opt"
                        :class="{ 'sa-favicon-opt--active': faviconPendingType === 'upload' }"
                        @click="selectFaviconType('upload')"
                    >
                        <Upload class="sa-favicon-opt__icon" :size="18" />
                        <span>Upload image</span>
                    </button>
                </div>

                <p v-if="faviconPendingType === 'initials'" class="sa-note sa-note--mt sa-note--success">
                    <CheckCircle2 :size="14" style="flex-shrink:0" /> Looking good — hit Save to apply this icon to your site.
                </p>
                <p v-else-if="faviconPendingType === 'logo'" class="sa-note sa-note--mt">
                    Your logo will be cropped to a square. Save to apply.
                </p>
                <p v-else-if="faviconPendingType === 'upload' && !form.favicon" class="sa-note sa-note--mt">
                    Choose an image file (PNG or JPG, max 512 KB). Square images work best.
                </p>
                <p v-else-if="faviconPendingType === 'upload' && form.favicon" class="sa-note sa-note--mt sa-note--success">
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
        <div class="sa-actions">
            <button type="button" class="db-btn db-btn--primary db-btn--lg" :disabled="saving" @click="saveForm">
                <Loader2 v-if="saving" :size="18" class="sa-spin" />
                {{ saving ? 'Saving…' : 'Save web address' }}
            </button>
        </div>
    </div>
</template>

<style scoped>
/* ── Shared btn primitives ────────────────────────────────────────────── */
.db-btn {
    display: inline-flex; align-items: center; gap: 8px;
    height: 44px; padding: 0 18px; border-radius: 10px;
    font-family: inherit; font-size: 15px; font-weight: 600;
    cursor: pointer; transition: opacity 0.1s, background 0.1s;
    text-decoration: none; white-space: nowrap; border: none;
}
.db-btn:disabled { opacity: 0.5; cursor: not-allowed; }
.db-btn--primary  { background: var(--db-accent); color: var(--db-accent-fg); border: 1.5px solid var(--db-accent); }
.db-btn--primary:hover:not(:disabled) { opacity: 0.9; }
.db-btn--secondary { background: var(--db-surface); color: var(--db-ink); border: 1.5px solid var(--db-line); }
.db-btn--secondary:hover:not(:disabled) { background: var(--db-panel); }
.db-btn--lg { height: 52px; padding: 0 24px; font-size: 16px; }

/* ── Wrap ──────────────────────────────────────────────────────────────── */
.sa-wrap { display: flex; flex-direction: column; gap: 24px; max-width: 820px; }

/* ── Card ──────────────────────────────────────────────────────────────── */
.sa-card {
    background: var(--db-surface);
    border: 1.5px solid var(--db-line);
    border-radius: 14px;
    overflow: hidden;
}
.sa-card__head {
    padding: 24px 28px 20px;
    border-bottom: 1px solid var(--db-line-soft);
}
.sa-card__title {
    font-size: 18px; font-weight: 700; color: var(--db-ink); letter-spacing: -0.2px;
}
.sa-card__sub {
    font-size: 14px; color: var(--db-ink-soft); margin-top: 4px; line-height: 1.5;
}
.sa-card__body { padding: 20px 28px 24px; }

/* ── Radio group ───────────────────────────────────────────────────────── */
.sa-radio-group { display: flex; flex-direction: column; gap: 12px; }

.sa-radio-card {
    display: flex; flex-direction: column; gap: 0;
    padding: 18px 20px;
    border: 2px solid var(--db-line);
    border-radius: 12px;
    cursor: pointer;
    background: var(--db-surface);
    transition: border-color 0.1s, background 0.1s;
}
.sa-radio-card--active {
    border-color: var(--db-accent);
    background: var(--db-accent-soft);
}
.sa-radio-card--disabled { opacity: 0.55; cursor: not-allowed; }

.sa-radio-card__header {
    display: flex; align-items: flex-start; gap: 14px;
}

.sa-radio-dot {
    width: 22px; height: 22px; border-radius: 999px;
    border: 2px solid var(--db-line);
    background: var(--db-surface);
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; margin-top: 2px;
    transition: border-color 0.1s, background 0.1s;
}
.sa-radio-dot--on {
    border-color: var(--db-accent);
    background: var(--db-accent);
}
.sa-radio-dot__fill {
    width: 8px; height: 8px; border-radius: 999px; background: #fff;
}

.sa-radio-card__label {
    font-size: 16px; font-weight: 600; color: var(--db-ink); line-height: 1.3;
}
.sa-radio-card__hint {
    font-size: 14px; color: var(--db-ink-soft); margin-top: 4px; line-height: 1.4;
}

.sa-recommended { color: var(--db-ink-soft); font-weight: 500; }

/* ── Badges ──────────────────────────────────────────────────────────── */
.sa-premium-badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 2px 8px; border-radius: 999px;
    background: #fef9c3; color: #854d0e;
    font-size: 12px; font-weight: 700;
    margin-left: 8px; vertical-align: middle;
}
.sa-premium-badge__icon { flex-shrink: 0; }

.sa-verified-badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 2px 8px; border-radius: 999px;
    background: #dcfce7; color: #166534;
    font-size: 12px; font-weight: 700;
    margin-left: 8px; vertical-align: middle;
}

/* ── Subdomain row ───────────────────────────────────────────────────── */
.sa-subdomain-row {
    display: flex; align-items: center; margin-top: 14px; padding-left: 36px;
}
.sa-input {
    height: 48px; font-size: 16px; border-radius: 10px;
    border: 1.5px solid var(--db-line); background: #fff;
    padding: 0 14px; font-family: inherit; color: var(--db-ink);
    outline: none; flex: 1;
}
.sa-input--inline {
    border-radius: 10px 0 0 10px;
    flex: 0 1 220px;
}
.sa-input:focus { border-color: var(--db-accent); box-shadow: 0 0 0 3px var(--db-accent-soft); }

.sa-subdomain-suffix {
    height: 48px; display: flex; align-items: center;
    padding: 0 14px;
    border: 1.5px solid var(--db-line); border-left: none;
    border-radius: 0 10px 10px 0;
    background: var(--db-panel);
    color: var(--db-ink-soft); font-size: 15px; font-weight: 500;
    white-space: nowrap;
}

/* ── Custom domain body ──────────────────────────────────────────────── */
.sa-custom-body {
    display: flex; flex-direction: column; gap: 14px;
    margin-top: 14px; padding-left: 36px;
}

/* ── Status banners ──────────────────────────────────────────────────── */
.sa-status-banner {
    display: flex; align-items: flex-start; gap: 12px;
    padding: 14px 16px; border-radius: 10px;
}
.sa-status-banner--success {
    background: #f0fdf4; border: 1.5px solid #bbf7d0; color: #166534;
}
.sa-status-banner--warning {
    background: #fffbeb; border: 1.5px solid #fde68a; color: #92400e;
}
.sa-status-banner__icon { flex-shrink: 0; margin-top: 1px; }
.sa-status-banner__title { font-size: 15px; font-weight: 700; margin: 0 0 2px; }
.sa-status-banner__body  { font-size: 14px; line-height: 1.5; margin: 0; }

/* ── Auto-connect ────────────────────────────────────────────────────── */
.sa-autoconnect {
    display: flex; flex-direction: column; gap: 14px;
    padding: 16px; border-radius: 12px;
    border: 2px dashed var(--db-line); background: var(--db-panel);
}
.sa-autoconnect__intro { display: flex; align-items: flex-start; gap: 12px; }
.sa-autoconnect__bolt  { font-size: 24px; flex-shrink: 0; line-height: 1; }
.sa-autoconnect__title { font-size: 16px; font-weight: 700; color: var(--db-ink); margin: 0 0 4px; }
.sa-autoconnect__hint  { font-size: 14px; color: var(--db-ink-soft); line-height: 1.5; margin: 0; }

/* ── DNS table ───────────────────────────────────────────────────────── */
.sa-dns-table {
    border: 1.5px solid var(--db-line); border-radius: 10px; overflow: hidden; font-size: 13px;
}
.sa-dns-table__head {
    display: grid; grid-template-columns: 60px 1fr 1fr;
    padding: 8px 14px; background: var(--db-panel);
    font-size: 11px; font-weight: 700; color: var(--db-ink-soft);
    text-transform: uppercase; letter-spacing: 0.5px; gap: 10px;
}
.sa-dns-table__row {
    display: grid; grid-template-columns: 60px 1fr 1fr;
    padding: 10px 14px; gap: 10px; align-items: center;
    border-top: 1px solid var(--db-line-soft);
}
.sa-dns-table__row--alt { background: var(--db-bg); }

/* ── Check list ──────────────────────────────────────────────────────── */
.sa-check-list { display: flex; flex-direction: column; gap: 8px; }
.sa-check-row  { display: flex; align-items: center; gap: 8px; font-size: 13px; }
.sa-check-row__ok   { color: var(--db-success); flex-shrink: 0; }
.sa-check-row__fail { color: var(--db-danger);  flex-shrink: 0; }

/* ── Misc ────────────────────────────────────────────────────────────── */
.sa-mono  { font-family: 'JetBrains Mono', ui-monospace, monospace; }
.sa-break { word-break: break-all; }
.sa-small { font-size: 12px; }
.sa-muted { color: var(--db-ink-soft); }
.sa-note  { font-size: 13px; color: var(--db-ink-soft); line-height: 1.5; margin: 0; }
.sa-error { font-size: 13px; color: var(--db-danger); font-weight: 500; margin: 0; }

.sa-link-btn {
    background: transparent; border: none; padding: 0;
    font-family: inherit; font-size: 13px; font-weight: 600;
    color: var(--db-accent); cursor: pointer; text-decoration: underline;
    text-decoration-color: transparent; display: inline-flex; align-items: center; gap: 4px;
}
.sa-link-btn:hover { text-decoration-color: currentColor; }
.sa-link-btn:disabled { opacity: 0.5; cursor: not-allowed; }

.sa-verify-row { display: flex; align-items: center; gap: 16px; flex-wrap: wrap; }
.sa-actions    { display: flex; gap: 12px; justify-content: flex-end; }

/* ── Favicon card ────────────────────────────────────────────────────── */
.sa-favicon-current {
    display: flex; align-items: center; gap: 10px;
    padding: 10px 14px; border-radius: 10px;
    background: var(--db-panel); border: 1.5px solid var(--db-line);
    margin-bottom: 14px;
}
.sa-favicon-thumb {
    width: 32px; height: 32px; border-radius: 6px; object-fit: contain;
    background: #fff; border: 1px solid var(--db-line);
}
.sa-favicon-label { font-size: 14px; color: var(--db-ink); flex: 1; }
.sa-favicon-remove {
    display: flex; align-items: center; justify-content: center;
    width: 28px; height: 28px; border-radius: 6px; border: none;
    background: transparent; color: var(--db-ink-soft); cursor: pointer;
    transition: color 0.1s, background 0.1s;
}
.sa-favicon-remove:hover { color: var(--db-danger, #dc2626); background: #fee2e2; }

.sa-favicon-options {
    display: flex; gap: 8px; flex-wrap: wrap;
}
.sa-favicon-opt {
    display: flex; align-items: center; gap: 8px;
    padding: 10px 14px; border-radius: 10px;
    border: 1.5px solid var(--db-line); background: var(--db-surface);
    color: var(--db-ink); font-family: inherit; font-size: 14px; font-weight: 600;
    cursor: pointer; transition: border-color 0.1s, background 0.1s, color 0.1s;
}
.sa-favicon-opt:hover:not(:disabled) {
    border-color: var(--db-accent); background: var(--db-accent-soft); color: var(--db-accent);
}
.sa-favicon-opt--active {
    border-color: var(--db-accent); background: var(--db-accent-soft); color: var(--db-accent);
}
.sa-favicon-opt:disabled { opacity: 0.4; cursor: not-allowed; }
.sa-favicon-opt__icon { flex-shrink: 0; }
.sa-favicon-opt__icon--initials {
    font-size: 12px; font-weight: 800; letter-spacing: -0.5px;
    width: 18px; height: 18px; display: flex; align-items: center; justify-content: center;
}

.sa-note { font-size: 13px; color: var(--db-ink-soft); line-height: 1.5; margin: 0; }
.sa-note--mt { margin-top: 12px; }
.sa-note--success { color: #166534; display: flex; align-items: center; gap: 6px; }

.sa-spin { animation: sa-spin-anim 1s linear infinite; }
@keyframes sa-spin-anim { to { transform: rotate(360deg); } }

/* ── Privacy toggle ─────────────────────────────────────────────────── */
.sa-privacy-toggle {
    display: flex; align-items: center; gap: 16px;
    width: 100%; padding: 16px 20px;
    border: 2px solid var(--db-line);
    border-radius: 12px;
    background: var(--db-surface);
    cursor: pointer; text-align: left;
    transition: border-color 0.15s, background 0.15s;
    font-family: inherit;
}
.sa-privacy-toggle:hover { border-color: var(--db-accent); }
.sa-privacy-toggle--on {
    border-color: #f59e0b;
    background: #fffbeb;
}
.sa-privacy-toggle__icon {
    flex-shrink: 0;
    color: var(--db-ink-soft);
    display: flex; align-items: center;
}
.sa-privacy-toggle--on .sa-privacy-toggle__icon { color: #b45309; }
.sa-privacy-toggle__text {
    flex: 1; display: flex; flex-direction: column; gap: 2px;
}
.sa-privacy-toggle__label {
    font-size: 15px; font-weight: 600; color: var(--db-ink); line-height: 1.3;
}
.sa-privacy-toggle--on .sa-privacy-toggle__label { color: #92400e; }
.sa-privacy-toggle__hint {
    font-size: 13px; color: var(--db-ink-soft); line-height: 1.4;
}
.sa-privacy-toggle--on .sa-privacy-toggle__hint { color: #b45309; opacity: 0.8; }

.sa-privacy-pill {
    flex-shrink: 0;
    padding: 3px 10px; border-radius: 999px;
    font-size: 12px; font-weight: 700;
    background: var(--db-panel); color: var(--db-ink-soft);
    border: 1.5px solid var(--db-line);
}
.sa-privacy-pill--on {
    background: #fef3c7; color: #92400e; border-color: #fde68a;
}
</style>
