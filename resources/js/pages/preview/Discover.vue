<script setup lang="ts">
import axios from 'axios'
import { onMounted, onUnmounted, ref } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    id: Number,
    batchId: String
})

const poll = ref(0)

const checkBatch = () => {
    axios.get(route('search.discover.poll', props.batchId)).then(response => {
        if(response.data) {
            router.visit(route('preview.setup', props.id))
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
    <div class="flex min-h-screen flex-col items-center bg-[#FDFDFC] p-6 text-[#1b1b18] lg:justify-center lg:p-8">
        <p>Please wait while we fetch your content...</p>
    </div>
</template>
