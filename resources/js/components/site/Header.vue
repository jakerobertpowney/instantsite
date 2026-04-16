<script setup lang="ts">
    import { computed } from 'vue';

    const props = defineProps({
        logo: String,
        name: String,
        businessType: String,
        addressComponents: Array
    })

    const town = computed(() => {
        // Find town (locality)
        const townComponent = props.addressComponents.find(component =>
            component.types && component.types.includes('locality')
        );

        return townComponent?.longText || '';
    })

    const country = computed(() => {
        // Find country (administrative_area_level_1)
        const countryComponent = props.addressComponents.find(component =>
            component.types && component.types.includes('administrative_area_level_1')
        );

        return countryComponent?.longText || '';
    })

    const logoSrc = computed(() => {
        if (!props.logo) return '';

        if (props.logo.startsWith('http://') || props.logo.startsWith('https://') || props.logo.startsWith('/')) {
            return props.logo;
        }

        return `/${props.logo}`;
    })
</script>

<template>
    <section class="flex flex-row gap-4 justify-between">
        <div>
            <img v-if="logoSrc" :src="logoSrc" class="rounded-full max-w-32 mb-6" :alt="name">
            <h1 class="text-3xl font-semibold text-gray-800">{{ name }}</h1>
            <p class="text-gray-600">{{ businessType }} in {{ town }}, {{ country }}</p>
        </div>
        <slot />
    </section>
</template>
