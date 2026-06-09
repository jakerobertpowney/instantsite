<script setup lang="ts">
import { inject, ref, computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Upload, CheckCircle, X, AlertCircle } from 'lucide-vue-next';

const form = inject<any>('form');
const siteData = inject<any>('siteData', null);

// Existing logo already saved on the TemporarySite (e.g. from a previous wizard run)
const existingLogoPath = computed<string | null>(() => {
    const path = siteData?.logo_path;
    if (!path) return null;
    // Normalise to a root-relative URL
    if (path.startsWith('http') || path.startsWith('/')) return path;
    return '/' + path;
});

const websiteDomain = computed<string | null>(() => {
    const uri = siteData?.website_url;
    if (!uri) return null;
    try { return new URL(uri).hostname.replace(/^www\./, ''); } catch { return null; }
});

const clearbitLogoUrl = computed<string | null>(() =>
    websiteDomain.value ? `https://logo.clearbit.com/${websiteDomain.value}` : null
);

const clearbitLoaded  = ref(false);
const clearbitFailed  = ref(false);
const suggestionAccepted = ref(false);
// Set to true initially if there's already a saved logo
const existingAccepted = ref(!!existingLogoPath.value);

const onClearbitLoad  = () => { clearbitLoaded.value = true; };
const onClearbitError = () => { clearbitFailed.value = true; };

const acceptSuggestion = () => {
    form.logo = null;
    form.suggested_logo_url = clearbitLogoUrl.value;
    suggestionAccepted.value = true;
    existingAccepted.value = false;
};

const declineSuggestion = () => {
    suggestionAccepted.value = false;
    form.suggested_logo_url = null;
};

const fileInput = ref<HTMLInputElement | null>(null);
const previewUrl = ref<string | null>(null);

const triggerUpload = () => fileInput.value?.click();

const handleFile = (event: Event) => {
    const file = (event.target as HTMLInputElement).files?.[0];
    if (file) {
        form.logo = file;
        form.suggested_logo_url = null;
        suggestionAccepted.value = false;
        existingAccepted.value = false;
        previewUrl.value = URL.createObjectURL(file);
    }
};

const clearLogo = () => {
    form.logo = null;
    form.suggested_logo_url = null;
    previewUrl.value = null;
    suggestionAccepted.value = false;
    existingAccepted.value = false;
};

const showSuggestion = computed(() =>
    clearbitLogoUrl.value && clearbitLoaded.value && !clearbitFailed.value
);

// The currently displayed image URL (new upload > Clearbit suggestion > existing saved)
const displayUrl = computed<string | null>(() => {
    if (previewUrl.value)             return previewUrl.value;
    if (suggestionAccepted.value)     return clearbitLogoUrl.value;
    if (existingAccepted.value)       return existingLogoPath.value;
    return null;
});

const hasSelection = computed(() => !!displayUrl.value);
</script>

<template>
    <div class="flex flex-col gap-4">

        <!-- Hidden Clearbit preload -->
        <img
            v-if="clearbitLogoUrl"
            :src="clearbitLogoUrl"
            class="hidden"
            @load="onClearbitLoad"
            @error="onClearbitError"
            alt=""
        />

        <!-- Clearbit suggestion (only when no logo already selected) -->
        <div
            v-if="showSuggestion && !suggestionAccepted && !previewUrl && !existingAccepted"
            class="rounded-xl border-2 border-dashed p-5 flex flex-col items-center gap-4 text-center"
        >
            <img
                :src="clearbitLogoUrl!"
                class="h-20 w-20 rounded-lg object-contain bg-white border shadow-sm"
                alt="Suggested logo"
            />
            <div>
                <p class="font-medium">We found your logo</p>
                <p class="text-sm text-muted-foreground mt-0.5">From {{ websiteDomain }}</p>
            </div>
            <div class="flex gap-3 w-full">
                <Button type="button" class="flex-1 h-11" @click="acceptSuggestion">
                    <CheckCircle class="h-4 w-4 mr-2" /> Yes, use this logo
                </Button>
                <Button type="button" variant="outline" class="h-11 px-4" @click="declineSuggestion">
                    <X class="h-4 w-4" />
                </Button>
            </div>
        </div>

        <!-- Selected / uploaded / existing logo -->
        <div
            v-if="hasSelection"
            class="rounded-xl border-2 border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-950/30 p-5 flex items-center gap-4"
        >
            <img
                :src="displayUrl!"
                class="h-16 w-16 rounded-lg object-contain bg-white border"
                alt="Your logo"
            />
            <div class="flex-1">
                <p class="font-medium text-green-700 dark:text-green-400">Logo added ✓</p>
                <div class="flex gap-3 mt-0.5">
                    <button
                        type="button"
                        class="text-sm text-muted-foreground hover:text-foreground underline"
                        @click="triggerUpload"
                    >
                        Upload a different one
                    </button>
                    <button
                        type="button"
                        class="text-sm text-muted-foreground hover:text-foreground underline"
                        @click="clearLogo"
                    >
                        Remove
                    </button>
                </div>
            </div>
        </div>

        <!-- Upload button (shown when nothing selected and no Clearbit suggestion pending) -->
        <div
            v-if="!hasSelection && !(showSuggestion && !suggestionAccepted)"
            class="flex flex-col gap-3"
        >
            <button
                type="button"
                class="w-full rounded-xl border-2 border-dashed h-32 flex flex-col items-center justify-center gap-2 text-muted-foreground hover:border-primary hover:text-foreground transition-colors"
                @click="triggerUpload"
            >
                <Upload class="h-6 w-6" />
                <span class="text-sm font-medium">Tap to upload your logo</span>
                <span class="text-xs">JPG, JPEG, or PNG</span>
            </button>
        </div>

        <!-- Hidden file input -->
        <input
            ref="fileInput"
            type="file"
            name="logo"
            id="logo"
            class="hidden"
            accept="image/jpeg,image/jpg,image/png"
            @change="handleFile"
        />

        <p v-if="form.errors.logo" class="text-sm text-destructive flex items-center gap-1.5">
            <AlertCircle class="h-4 w-4 shrink-0" />
            {{ form.errors.logo }}
        </p>

        <p class="text-sm text-muted-foreground text-center">
            Don't have a logo? That's fine — tap <strong>Skip for now</strong> below.
        </p>
    </div>
</template>
