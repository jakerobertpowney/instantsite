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

const form = useForm({
    meta_title: site.value.meta_title ?? '',
    meta_description: site.value.meta_description ?? '',
    google_analytics_id: site.value.settings?.google_analytics_id ?? '',
    allow_indexing: (site.value.settings?.allow_indexing ?? true) !== false,
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
    <div class="flex flex-col gap-5 max-w-[820px]">

        <!-- How your site looks on Google -->
        <div class="bg-brand-surface border-[1.5px] border-brand-line rounded-[14px] overflow-hidden">
            <div class="border-b border-brand-line-soft p-5 pb-4">
                <div class="text-[17px] font-bold text-brand-ink">How your site looks on Google</div>
                <div class="text-sm text-brand-ink-soft mt-1 leading-[1.5]">This is what people see when they find your site in Google search results. It updates as you type.</div>
                <a
                    v-if="searchConsoleUrl"
                    :href="searchConsoleUrl"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-block mt-2 text-xs font-semibold text-brand-blue no-underline hover:underline"
                >
                    Open Search Console ↗
                </a>
            </div>
            <div class="p-5">
                <!-- Preview -->
                <div class="p-4 rounded-[10px] bg-brand-panel font-[Arial, sans-serif]">
                    <div class="text-xs text-[#1e8e3e]">{{ liveSiteUrl || 'yourbusiness.321sites.com' }}</div>
                    <div class="text-[18px] text-[#1a0dab] font-medium my-1">{{ form.meta_title || 'Your page title will appear here' }}</div>
                    <div class="text-sm text-[#4d5156] leading-[1.45]">{{ form.meta_description || 'Your short description will help people decide whether to click through to your website.' }}</div>
                </div>
            </div>
        </div>

        <!-- Page title -->
        <div class="bg-brand-surface border-[1.5px] border-brand-line rounded-[14px] p-6">
            <label class="flex flex-col gap-2">
                <span class="text-sm font-semibold text-brand-ink flex items-center gap-2">Page title</span>
                <Input
                    id="title"
                    v-model="form.meta_title"
                    type="text"
                    placeholder="e.g. Dave's Painting & Decorating — Harrogate"
                    class="h-11 text-sm rounded-2 border-[1.5px] border-brand-line bg-white transition-colors focus:border-brand-blue focus:ring-3 focus:ring-[rgba(30, 102, 245, 0.12)] focus:outline-none"
                    required
                />
                <span class="text-xs text-brand-ink-soft leading-[1.5]">This appears in the browser tab and in Google. Use your business name and where you work.</span>
                <span v-if="form.errors.meta_title" class="text-xs text-red-600 font-medium">{{ form.errors.meta_title }}</span>
                <button type="button" class="inline-flex items-center gap-1.5 px-3.5 h-8.5 rounded-2 text-xs font-semibold bg-brand-panel text-brand-ink border-[1.5px] border-brand-line cursor-pointer transition-all hover:not(:disabled):border-brand-blue hover:not(:disabled):bg-[rgba(30, 102, 245, 0.06)] disabled:opacity-50 disabled:cursor-not-allowed self-start" :disabled="isGeneratingTitle" @click="generateField('meta_title')">
                    <Loader2 v-if="isGeneratingTitle" :size="14" class="animate-spin" />
                    <Wand2 v-else :size="14" />
                    {{ isGeneratingTitle ? 'Writing…' : 'Write it for me' }}
                </button>
            </label>
        </div>

        <!-- Description -->
        <div class="bg-brand-surface border-[1.5px] border-brand-line rounded-[14px] p-6">
            <label class="flex flex-col gap-2">
                <span class="text-sm font-semibold text-brand-ink flex items-center gap-2">Short description</span>
                <Textarea
                    id="description"
                    v-model="form.meta_description"
                    rows="3"
                    placeholder="e.g. Professional painter and decorator in Manchester. 20 years experience. Free quotes."
                    class="text-sm rounded-2 border-[1.5px] border-brand-line bg-white resize-vertical transition-colors focus:border-brand-blue focus:ring-3 focus:ring-[rgba(30, 102, 245, 0.12)] focus:outline-none"
                    required
                />
                <span class="text-xs text-brand-ink-soft leading-[1.5]">One or two sentences. This shows up under your site link in Google search results.</span>
                <span v-if="form.errors.meta_description" class="text-xs text-red-600 font-medium">{{ form.errors.meta_description }}</span>
                <button type="button" class="inline-flex items-center gap-1.5 px-3.5 h-8.5 rounded-2 text-xs font-semibold bg-brand-panel text-brand-ink border-[1.5px] border-brand-line cursor-pointer transition-all hover:not(:disabled):border-brand-blue hover:not(:disabled):bg-[rgba(30, 102, 245, 0.06)] disabled:opacity-50 disabled:cursor-not-allowed self-start" :disabled="isGeneratingMetaDesc" @click="generateField('meta_description')">
                    <Loader2 v-if="isGeneratingMetaDesc" :size="14" class="animate-spin" />
                    <Wand2 v-else :size="14" />
                    {{ isGeneratingMetaDesc ? 'Writing…' : 'Write it for me' }}
                </button>
            </label>
        </div>

        <!-- Show on Google toggle -->
        <div class="bg-brand-surface border-[1.5px] border-brand-line rounded-[14px] p-6">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <div class="text-base font-bold text-brand-ink">Show my site on Google</div>
                    <div class="text-sm text-brand-ink-soft leading-[1.5] mt-1 max-w-[500px]">Turn this off while you're still setting things up. Turn it back on when you're ready for customers to find you.</div>
                </div>
                <Switch id="allow-indexing" v-model="form.allow_indexing" class="flex-shrink-0" />
            </div>
        </div>

        <!-- Google Analytics (Premium) -->
        <div class="bg-brand-surface border-[1.5px] border-brand-line rounded-[14px] p-6" :class="{ 'opacity-60': !isPremium }">
            <label class="flex flex-col gap-2">
                <span class="text-sm font-semibold text-brand-ink flex items-center gap-2">
                    Google Analytics
                    <span v-if="!isPremium" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-[#fef9c3] text-[#854d0e] text-xs font-bold">
                        <Sparkles :size="11" /> Premium
                    </span>
                </span>
                <Input
                    id="ga-id"
                    v-model="form.google_analytics_id"
                    placeholder="G-XXXXXXXXXX"
                    class="h-11 text-sm rounded-2 border-[1.5px] border-brand-line bg-white transition-colors focus:border-brand-blue focus:ring-3 focus:ring-[rgba(30, 102, 245, 0.12)] focus:outline-none max-w-[280px]"
                    :disabled="!isPremium"
                />
                <span class="text-xs text-brand-ink-soft leading-[1.5]">
                    <template v-if="isPremium">Paste your Measurement ID here. Leave blank if you don't use Analytics.</template>
                    <template v-else>Upgrade to Premium to connect Google Analytics and track your visitors.</template>
                </span>
                <span v-if="form.errors.google_analytics_id" class="text-xs text-red-600 font-medium">{{ form.errors.google_analytics_id }}</span>
            </label>
        </div>

        <!-- Save -->
        <div class="flex justify-end">
            <button type="button" class="inline-flex items-center justify-center gap-2 h-11 px-6 rounded-[10px] bg-brand-blue text-white border-none font-semibold text-sm cursor-pointer transition-opacity hover:opacity-90 disabled:opacity-50 disabled:cursor-not-allowed" :disabled="saving" @click="saveForm">
                <Loader2 v-if="saving" :size="18" class="animate-spin" />
                {{ saving ? 'Saving…' : 'Save changes' }}
            </button>
        </div>
    </div>
</template>
