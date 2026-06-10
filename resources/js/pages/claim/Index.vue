<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import SiteIndex from '@/pages/site/Index.vue';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';

const props = defineProps<{
    data: Record<string, unknown>;
    placesId: string;
    businessName: string;
    metaTitle: string;
    hasPreviewData: boolean;
}>();

const showDismissDialog = ref(false);
const claiming = ref(false);
const dismissing = ref(false);

function handleClaim() {
    if (claiming.value) return;
    claiming.value = true;
    router.post(`/claim/${props.placesId}/claim`, {}, {
        onFinish: () => { claiming.value = false; },
    });
}

function handleDismiss() {
    if (dismissing.value) return;
    dismissing.value = true;
    router.delete(`/claim/${props.placesId}`, {
        onFinish: () => { dismissing.value = false; },
    });
}
</script>

<template>
    <!-- ── Preview data notice ────────────────────────────────────────────────── -->
    <div
        v-if="hasPreviewData"
        class="sticky top-0 z-[9998] bg-amber-50 border-b border-amber-200 px-4 py-2.5 flex items-center justify-center gap-2 text-center"
    >
        <svg class="w-4 h-4 text-amber-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <p class="text-xs text-amber-800">
            <span class="font-semibold">This is a preview.</span>
            Some content like services and reviews is example data.
            The contact form and other advanced features are available on our
            <span class="font-semibold">premium plan</span> — claim your site to find out more.
        </p>
    </div>

    <!-- Extra bottom padding so content isn't hidden behind the sticky footer -->
    <div class="pb-28">
        <SiteIndex
            :data="data"
            :is-premium="false"
            :meta-title="metaTitle"
            :meta-description="(data.description as string) ?? ''"
            :site-url="''"
            :canonical-url="''"
            :sitemap-url="null"
            :is-owner="false"
            :dashboard-url="''"
        />
    </div>

    <!-- ── Sticky claim footer ──────────────────────────────────────────────── -->
    <div class="fixed bottom-0 left-0 right-0 z-[9999] bg-white border-t border-gray-200 shadow-[0_-4px_24px_rgba(0,0,0,0.08)]">
        <div class="max-w-5xl mx-auto px-4 py-4 flex flex-col sm:flex-row items-center justify-between gap-3">
            <!-- Left: message -->
            <div class="flex items-center gap-3 text-center sm:text-left">
                <div class="hidden sm:flex w-9 h-9 rounded-full bg-indigo-100 items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-900">This is a preview of your free website</p>
                    <p class="text-xs text-gray-500 mt-0.5">Claim it now and it goes live at
                        <span class="font-medium text-gray-700">{{ businessName.toLowerCase().replace(/\s+/g, '-') }}.321sites.com</span>
                    </p>
                </div>
            </div>

            <!-- Right: actions -->
            <div class="flex items-center gap-2 flex-shrink-0">
                <button
                    type="button"
                    class="text-sm text-gray-500 hover:text-gray-700 px-3 py-2 rounded-md hover:bg-gray-100 transition-colors"
                    :disabled="dismissing"
                    @click="showDismissDialog = true"
                >
                    Not interested
                </button>

                <button
                    type="button"
                    class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-60 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition-colors shadow-sm"
                    :disabled="claiming"
                    @click="handleClaim"
                >
                    <svg v-if="claiming" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z" />
                    </svg>
                    {{ claiming ? 'Starting…' : 'Claim my site' }}
                </button>
            </div>
        </div>
    </div>

    <!-- ── Not Interested confirmation dialog ──────────────────────────────── -->
    <AlertDialog :open="showDismissDialog" @update:open="showDismissDialog = $event">
        <AlertDialogContent>
            <AlertDialogHeader>
                <AlertDialogTitle>Are you sure you're not interested?</AlertDialogTitle>
                <AlertDialogDescription>
                    We'll remove this preview and won't contact you again. You can always visit
                    321sites.com later to create your website.
                </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
                <AlertDialogCancel @click="showDismissDialog = false">Go back</AlertDialogCancel>
                <AlertDialogAction
                    class="bg-red-600 hover:bg-red-700 text-white"
                    :disabled="dismissing"
                    @click="handleDismiss"
                >
                    Yes, remove it
                </AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>
