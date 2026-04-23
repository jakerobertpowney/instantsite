<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue';
import axios from 'axios'
import { onMounted, onUnmounted, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { Head } from '@inertiajs/vue3';
import { Skeleton } from '@/components/ui/skeleton';
import { Progress } from '@/components/ui/progress';
import { poll as discoverPoll } from '@/routes/search/discover';
import { setup as previewSetup } from '@/routes/preview';

const props = defineProps({
    id: [Number, String],
    batchId: String
})

const poll = ref(0)
const progress = ref(10)

const checkBatch = () => {
    axios.get(discoverPoll.url(props.batchId!)).then(response => {
        if (response.data) {
            progress.value = 100
            router.visit(previewSetup.url(props.id!))
        } else {
            progress.value = Math.min(progress.value + 15, 90)
        }
    })
}

onMounted(() => {
    poll.value = setInterval(() => {
        checkBatch()
    }, 5000)
})

onUnmounted(() => {
    clearInterval(poll.value)
})
</script>

<template>
    <Head>
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="" />
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet" />
    </Head>

    <div class="flex min-h-screen flex-col" style="background: #ffffff; font-family: 'Inter', ui-sans-serif, system-ui, sans-serif;">

        <!-- Minimal top nav — matches homepage -->
        <header class="flex items-center gap-2.5 px-6 py-5">
            <AppLogo />
        </header>

        <!-- Loading content — centred in remaining height -->
        <div class="flex flex-1 flex-col items-center justify-center px-6 pb-16">
            <div class="max-w-md w-full flex flex-col gap-8">

                <!-- Spinner + headline -->
                <div class="flex flex-col items-center gap-4 text-center">
                    <!-- Animated ring -->
                    <div class="relative flex h-14 w-14 items-center justify-center">
                        <svg class="absolute inset-0 animate-spin" width="56" height="56" viewBox="0 0 56 56" fill="none" style="animation-duration: 1.1s;">
                            <circle cx="28" cy="28" r="24" stroke="#dde1e8" stroke-width="4"/>
                            <path d="M28 4a24 24 0 0 1 24 24" stroke="#1E66F5" stroke-width="4" stroke-linecap="round"/>
                        </svg>
                        <div class="flex items-center justify-center" style="color: #0f172a;">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 500 160" width="32" height="10" aria-hidden="true">
                                <text x="60" y="115" font-family="system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif" font-size="100" font-weight="600" fill="currentColor">3</text>
                                <circle cx="143" cy="90" r="9" fill="#1e66f5"/>
                                <text x="170" y="115" font-family="system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif" font-size="100" font-weight="600" fill="currentColor">2</text>
                                <circle cx="253" cy="90" r="9" fill="#1e66f5"/>
                                <text x="280" y="115" font-family="system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif" font-size="100" font-weight="600" fill="currentColor">1</text>
                            </svg>
                        </div>
                    </div>
                    <div>
                        <p class="text-xl font-bold" style="color: #0f172a;">Pulling in your Google info…</p>
                        <p class="mt-1 text-sm" style="color: #64748b;">This only takes a few seconds.</p>
                    </div>
                </div>

                <!-- Progress bar — blue to match brand accent -->
                <div style="--primary: #1e66f5; --secondary: #dde1e8;">
                    <Progress :model-value="progress" class="w-full h-2" />
                </div>

                <!-- Skeleton placeholders -->
                <div class="flex flex-col gap-3 rounded-2xl p-5" style="background: #edf1f8;">
                    <Skeleton class="h-5 w-3/4 rounded-lg" style="background: #dde1e8;" />
                    <Skeleton class="h-4 w-1/2 rounded-lg" style="background: #dde1e8;" />
                    <Skeleton class="h-4 w-2/3 rounded-lg" style="background: #dde1e8;" />
                    <Skeleton class="mt-2 h-28 w-full rounded-xl" style="background: #dde1e8;" />
                </div>

            </div>
        </div>

    </div>
</template>
