<script setup lang="ts">

import { computed } from 'vue';
import moment from 'moment';

const props = defineProps({
    review: Object
})

const publishTime = computed(() => {
    return moment(props.review.publishTime).format('MMMM Do YYYY, h:mm:ss a')
})

</script>

<template>
    <div
        class="grid grid-cols-5 gap-8 py-6 sm:items-start"
    >
        <div class="shrink-0 space-y-2 col-span-2">
            <div class="flex items-center gap-0.5">
                <svg
                    v-for="n in review.rating"
                    :key="n"
                    class="h-4 w-4 text-yellow-300"
                    aria-hidden="true"
                    xmlns="http://www.w3.org/2000/svg"
                    width="24"
                    height="24"
                    fill="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path d="M13.849 4.22c-.684-1.626-3.014-1.626-3.698 0L8.397 8.387l-4.552.361c-1.775.14-2.495 2.331-1.142 3.477l3.468 2.937-1.06 4.392c-.413 1.713 1.472 3.067 2.992 2.149L12 19.35l3.897 2.354c1.52.918 3.405-.436 2.992-2.15l-1.06-4.39 3.468-2.938c1.353-1.146.633-3.336-1.142-3.477l-4.552-.36-1.754-4.17Z" />
                </svg>
            </div>

            <div class="space-y-0.5">
                <p class="text-base font-semibold text-gray-900 dark:text-white">{{ review.authorAttribution.displayName }}</p>
                <p class="text-sm font-normal text-gray-500 dark:text-gray-400">{{ publishTime }}</p>
            </div>
        </div>

        <div class="col-span-3 mt-4 min-w-0 flex-1 space-y-4 sm:mt-0">
            <p class="text-base font-normal">{{ review.text.text }}</p>
        </div>
    </div>
</template>
