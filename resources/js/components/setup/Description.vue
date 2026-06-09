<script setup lang="ts">
import { inject, computed, ref } from 'vue';
import { Textarea } from '@/components/ui/textarea';
import { Button } from '@/components/ui/button';
import { Wand2, RefreshCw, AlertCircle } from 'lucide-vue-next';
import axios from 'axios';
import { generateDescription as generateDescriptionRoute } from '@/routes/setup';

const form = inject<any>('form');
const siteData = inject<any>('siteData', null);
const siteId = inject<string | null>('siteId', null);

const googleDescription = computed<string | null>(() =>
    siteData?.description ?? null
);

const isGenerating = ref(false);
const generateError = ref<string | null>(null);

const generate = async () => {
    if (!siteId) return;
    isGenerating.value = true;
    generateError.value = null;
    try {
        const response = await axios.post(generateDescriptionRoute.url(siteId));
        form.description = response.data.description ?? '';
    } catch (err: any) {
        generateError.value =
            err?.response?.data?.error ?? 'Something went wrong. Please try again.';
    } finally {
        isGenerating.value = false;
    }
};

const placeholderText = computed(() =>
    googleDescription.value
        ? googleDescription.value
        : 'e.g. Dave\'s Painting & Decorating has been serving Manchester homeowners for over 15 years. We offer interior and exterior painting, wallpapering, and full decorating services. Fully insured and always tidy.'
);

// If Google has a description and the user hasn't typed anything, show it as a prefill hint
const showGooglePrefill = computed(() =>
    !!googleDescription.value && !form.description
);
</script>

<template>
    <div class="flex flex-col gap-3">

        <!-- Google prefill notice -->
        <div
            v-if="showGooglePrefill"
            class="rounded-lg border bg-muted/40 p-3 flex flex-col gap-2"
        >
            <p class="text-xs font-medium text-muted-foreground">From your Google listing</p>
            <p class="text-sm leading-relaxed">{{ googleDescription }}</p>
            <button
                type="button"
                class="text-xs text-primary font-medium text-left hover:underline"
                @click="form.description = googleDescription"
            >
                Use this description →
            </button>
        </div>

        <Textarea
            name="description"
            id="description"
            :placeholder="placeholderText"
            v-model="form.description"
            class="min-h-36 resize-none text-base leading-relaxed"
            :class="{ 'border-destructive ring-destructive': form.errors.description }"
        />
        <p v-if="form.errors.description" class="text-sm text-destructive flex items-center gap-1.5">
            <AlertCircle class="h-4 w-4 shrink-0" />
            {{ form.errors.description }}
        </p>

        <!-- Error -->
        <div v-if="generateError" class="flex items-start gap-2 text-sm text-destructive">
            <AlertCircle class="h-4 w-4 mt-0.5 shrink-0" />
            {{ generateError }}
        </div>

        <!-- Write for me button -->
        <Button
            v-if="siteId"
            type="button"
            variant="outline"
            class="w-full gap-2 h-11"
            :disabled="isGenerating"
            @click="generate"
        >
            <RefreshCw v-if="isGenerating" class="h-4 w-4 animate-spin" />
            <Wand2 v-else class="h-4 w-4" />
            {{ isGenerating ? 'Writing your description…' : 'Write it for me' }}
        </Button>

        <p class="text-xs text-muted-foreground text-center">
            Not sure what to write? Tap "Write it for me" and we'll create one based on your Google listing.
        </p>
    </div>
</template>
