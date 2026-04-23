<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import { settings as dashboardSettings } from '@/routes/dashboard';
import { Input } from '@/components/ui/input';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { Loader2, Sparkles, Wand2 } from 'lucide-vue-next';
import { toast } from 'vue-sonner';
import { computed, inject, ref } from 'vue';
import axios from 'axios';

const page = usePage();
const isPremium = inject('isPremium') as boolean;
const appDomain = inject('appDomain') as string;
const site = computed(() => (page.props.site as any) ?? {});
const siteData = computed(() => site.value.data ?? {});

const form = useForm({
    meta_title: site.value.meta_title ?? '',
    meta_description: site.value.meta_description ?? '',
    google_analytics_id: siteData.value.google_analytics_id ?? '',
    allow_indexing: siteData.value.allow_indexing !== false,
});

const saving = ref(false);

const isGeneratingTitle = ref(false);
const isGeneratingMetaDesc = ref(false);

const generateField = async (type: 'meta_title' | 'meta_description') => {
    if (type === 'meta_title') isGeneratingTitle.value = true;
    else isGeneratingMetaDesc.value = true;
    try {
        const response = await axios.post('/dashboard/generate-description', { type });
        if (type === 'meta_title') form.meta_title = response.data.description ?? '';
        else form.meta_description = response.data.description ?? '';
    } catch {
        toast('Could not generate — please write it yourself.');
    } finally {
        if (type === 'meta_title') isGeneratingTitle.value = false;
        else isGeneratingMetaDesc.value = false;
    }
};

const liveSiteUrl = computed(() => {
    if (site.value?.domain_type === 'custom' && site.value?.custom_domain && site.value?.domain_verified) {
        return `https://${site.value.custom_domain}`;
    }

    if (site.value?.subdomain) {
        return `https://${site.value.subdomain}.${appDomain}`;
    }

    return null;
});

const searchConsoleUrl = computed(() => {
    if (!liveSiteUrl.value) return null;

    return `https://search.google.com/search-console?resource_id=${encodeURIComponent(liveSiteUrl.value)}`;
});

const saveForm = () => {
    saving.value = true;
    form.post(dashboardSettings.url(), {
        onSuccess: () => {
            toast('Saved!');
            saving.value = false;
        },
        onError: () => {
            saving.value = false;
        },
    });
};
</script>

<template>
    <div class="seo-wrap">

        <!-- How your site looks on Google -->
        <div class="seo-card">
            <div class="seo-card__head">
                <div class="seo-card__title">How your site looks on Google</div>
                <div class="seo-card__sub">This is what people see when they find your site in Google search results. It updates as you type.</div>
                <a
                    v-if="searchConsoleUrl"
                    :href="searchConsoleUrl"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="seo-console-link"
                >
                    Open Search Console ↗
                </a>
            </div>
            <div class="seo-card__body">
                <!-- Preview -->
                <div class="seo-preview">
                    <div class="seo-preview__url">{{ liveSiteUrl || 'yourbusiness.321sites.com' }}</div>
                    <div class="seo-preview__title">{{ form.meta_title || 'Your page title will appear here' }}</div>
                    <div class="seo-preview__desc">{{ form.meta_description || 'Your short description will help people decide whether to click through to your website.' }}</div>
                </div>
            </div>
        </div>

        <!-- Page title -->
        <div class="seo-card seo-card--pad">
            <label class="seo-field">
                <span class="seo-field__label">Page title</span>
                <Input
                    id="title"
                    v-model="form.meta_title"
                    type="text"
                    placeholder="e.g. Dave's Painting & Decorating — Harrogate"
                    class="seo-input"
                    required
                />
                <span class="seo-field__hint">This appears in the browser tab and in Google. Use your business name and where you work.</span>
                <span v-if="form.errors.meta_title" class="seo-error">{{ form.errors.meta_title }}</span>
                <button type="button" class="seo-ai-btn" :disabled="isGeneratingTitle" @click="generateField('meta_title')">
                    <Loader2 v-if="isGeneratingTitle" :size="14" class="seo-ai-spin" />
                    <Wand2 v-else :size="14" />
                    {{ isGeneratingTitle ? 'Writing…' : 'Write it for me' }}
                </button>
            </label>
        </div>

        <!-- Description -->
        <div class="seo-card seo-card--pad">
            <label class="seo-field">
                <span class="seo-field__label">Short description</span>
                <Textarea
                    id="description"
                    v-model="form.meta_description"
                    rows="3"
                    placeholder="e.g. Professional painter and decorator in Manchester. 20 years experience. Free quotes."
                    class="seo-textarea"
                    required
                />
                <span class="seo-field__hint">One or two sentences. This shows up under your site link in Google search results.</span>
                <span v-if="form.errors.meta_description" class="seo-error">{{ form.errors.meta_description }}</span>
                <button type="button" class="seo-ai-btn" :disabled="isGeneratingMetaDesc" @click="generateField('meta_description')">
                    <Loader2 v-if="isGeneratingMetaDesc" :size="14" class="seo-ai-spin" />
                    <Wand2 v-else :size="14" />
                    {{ isGeneratingMetaDesc ? 'Writing…' : 'Write it for me' }}
                </button>
            </label>
        </div>

        <!-- Show on Google toggle -->
        <div class="seo-card seo-card--pad">
            <div class="seo-toggle-row">
                <div>
                    <div class="seo-toggle-row__label">Show my site on Google</div>
                    <div class="seo-toggle-row__hint">Turn this off while you're still setting things up. Turn it back on when you're ready for customers to find you.</div>
                </div>
                <Switch id="allow-indexing" v-model="form.allow_indexing" class="seo-switch" />
            </div>
        </div>

        <!-- Google Analytics (Premium) -->
        <div class="seo-card seo-card--pad" :class="{ 'seo-card--dimmed': !isPremium }">
            <label class="seo-field">
                <span class="seo-field__label">
                    Google Analytics
                    <span v-if="!isPremium" class="seo-premium-badge">
                        <Sparkles :size="11" /> Premium
                    </span>
                </span>
                <Input
                    id="ga-id"
                    v-model="form.google_analytics_id"
                    placeholder="G-XXXXXXXXXX"
                    class="seo-input seo-input--narrow"
                    :disabled="!isPremium"
                />
                <span class="seo-field__hint">
                    <template v-if="isPremium">Paste your Measurement ID here. Leave blank if you don't use Analytics.</template>
                    <template v-else>Upgrade to Premium to connect Google Analytics and track your visitors.</template>
                </span>
                <span v-if="form.errors.google_analytics_id" class="seo-error">{{ form.errors.google_analytics_id }}</span>
            </label>
        </div>

        <!-- Save -->
        <div class="seo-actions">
            <button type="button" class="seo-save-btn" :disabled="saving" @click="saveForm">
                <Loader2 v-if="saving" :size="18" class="seo-spin" />
                {{ saving ? 'Saving…' : 'Save changes' }}
            </button>
        </div>
    </div>
</template>

<style scoped>
.seo-wrap { display: flex; flex-direction: column; gap: 20px; max-width: 820px; }

.seo-card {
    background: var(--db-surface);
    border: 1.5px solid var(--db-line);
    border-radius: 14px;
    overflow: hidden;
}
.seo-card--pad { padding: 24px; }
.seo-card--dimmed { opacity: 0.6; }

.seo-card__head {
    padding: 20px 24px 16px;
    border-bottom: 1px solid var(--db-line-soft);
}
.seo-card__body { padding: 20px 24px; }
.seo-card__title { font-size: 17px; font-weight: 700; color: var(--db-ink); }
.seo-card__sub   { font-size: 14px; color: var(--db-ink-soft); margin-top: 4px; line-height: 1.5; }
.seo-console-link {
    display: inline-block; margin-top: 8px;
    font-size: 13px; font-weight: 600; color: var(--db-accent);
    text-decoration: none;
}
.seo-console-link:hover { text-decoration: underline; }

/* ── Google search preview ────────────────────────────────────────────── */
.seo-preview {
    padding: 16px; border-radius: 10px; background: var(--db-panel);
    font-family: Arial, sans-serif;
}
.seo-preview__url   { font-size: 13px; color: #1e8e3e; }
.seo-preview__title { font-size: 18px; color: #1a0dab; font-weight: 500; margin: 4px 0; }
.seo-preview__desc  { font-size: 14px; color: #4d5156; line-height: 1.45; }

/* ── Field ────────────────────────────────────────────────────────────── */
.seo-field { display: flex; flex-direction: column; gap: 8px; }
.seo-field__label { font-size: 14px; font-weight: 600; color: var(--db-ink); display: flex; align-items: center; gap: 8px; }
.seo-field__hint  { font-size: 13px; color: var(--db-ink-soft); line-height: 1.5; }
.seo-error        { font-size: 13px; color: var(--db-danger); font-weight: 500; }

.seo-input {
    height: 44px; font-size: 15px; border-radius: 8px;
    border: 1.5px solid var(--db-line) !important; background: #fff !important;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}
.seo-input:focus {
    border-color: var(--db-accent) !important;
    box-shadow: 0 0 0 3px rgba(30, 102, 245, 0.12) !important;
    outline: none !important;
}
.seo-input--narrow { max-width: 280px; }
.seo-textarea {
    font-size: 15px; border-radius: 8px;
    border: 1.5px solid var(--db-line) !important; background: #fff !important;
    resize: vertical;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}
.seo-textarea:focus {
    border-color: var(--db-accent) !important;
    box-shadow: 0 0 0 3px rgba(30, 102, 245, 0.12) !important;
    outline: none !important;
}

/* ── Toggle row ───────────────────────────────────────────────────────── */
.seo-toggle-row {
    display: flex; align-items: center; justify-content: space-between; gap: 16px;
}
.seo-toggle-row__label { font-size: 16px; font-weight: 700; color: var(--db-ink); }
.seo-toggle-row__hint  { font-size: 14px; color: var(--db-ink-soft); line-height: 1.5; margin-top: 4px; max-width: 500px; }
.seo-switch { flex-shrink: 0; }

/* ── Premium badge ────────────────────────────────────────────────────── */
.seo-premium-badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 2px 8px; border-radius: 999px;
    background: #fef9c3; color: #854d0e;
    font-size: 12px; font-weight: 700;
}

/* ── Save button ──────────────────────────────────────────────────────── */
.seo-actions { display: flex; justify-content: flex-end; }
.seo-save-btn {
    display: inline-flex; align-items: center; gap: 8px;
    height: 44px; padding: 0 24px; border-radius: 10px;
    background: var(--db-accent); color: var(--db-accent-fg);
    border: none; font-family: inherit; font-size: 15px; font-weight: 600;
    cursor: pointer; transition: opacity 0.1s;
}
.seo-save-btn:disabled { opacity: 0.5; cursor: not-allowed; }
.seo-save-btn:hover:not(:disabled) { opacity: 0.9; }
.seo-spin { animation: seo-spin 1s linear infinite; }
@keyframes seo-spin { to { transform: rotate(360deg); } }

/* ── AI generate button ─────────────────────────────────────────────── */
.seo-ai-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 0 14px; height: 34px; border-radius: 8px;
    font-family: inherit; font-size: 13px; font-weight: 600;
    background: var(--db-panel); color: var(--db-ink);
    border: 1.5px solid var(--db-line);
    cursor: pointer; transition: border-color 0.15s, background 0.15s;
    align-self: flex-start;
}
.seo-ai-btn:hover:not(:disabled) { border-color: var(--db-accent); background: var(--db-accent-soft); color: var(--db-accent); }
.seo-ai-btn:disabled { opacity: 0.5; cursor: not-allowed; }
.seo-ai-spin { animation: seo-spin 1s linear infinite; }
</style>
