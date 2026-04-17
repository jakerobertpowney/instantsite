<script setup lang="ts">
import axios from 'axios'
import { onMounted, onUnmounted, ref } from 'vue';
import { router } from '@inertiajs/vue3';
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
    <div class="flex min-h-screen flex-col items-center justify-center bg-background p-6 text-foreground">
        <div class="max-w-md w-full flex flex-col gap-6">
            <div class="flex flex-col gap-2">
                <p class="text-lg font-medium">Fetching your business details…</p>
                <p class="text-sm text-muted-foreground">This may take a few seconds.</p>
            </div>

            <Progress :model-value="progress" class="w-full" />

            <div class="flex flex-col gap-3">
                <Skeleton class="h-6 w-3/4" />
                <Skeleton class="h-4 w-1/2" />
                <Skeleton class="h-4 w-2/3" />
                <Skeleton class="h-32 w-full rounded-lg" />
            </div>
        </div>
    </div>
</template>
