<script setup lang="ts">

import { computed } from 'vue';

const props = defineProps({
    photos: Array
})

const slicedPhotos = computed(() => {
    return props.photos?.slice(0, 6)
})

</script>

<template>
    <section>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
            <div
                v-for="(photo, index) in slicedPhotos"
                :key="index"
            >
                <img class="h-52 w-full max-w-full object-center object-cover rounded-lg overflow-hidden" :src="'/' + photo">
            </div>
        </div>
    </section>
</template>
