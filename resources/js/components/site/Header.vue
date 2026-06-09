<script setup lang="ts">
import { computed } from 'vue';
import { MapPin } from 'lucide-vue-next';

const props = defineProps({
    logo: String,
    name: String,
    businessType: String,
    city: String,
    region: String,
});

const locationLabel = computed(() => {
    const parts = [props.city, props.region].filter(Boolean);
    return parts.join(', ');
});

const logoSrc = computed(() => {
    if (!props.logo) return '';
    if (props.logo.startsWith('http://') || props.logo.startsWith('https://') || props.logo.startsWith('/')) {
        return props.logo;
    }
    return `/${props.logo}`;
});
</script>

<template>
    <!-- Renders inside the hero section — parent provides the bg context -->
    <div class="flex flex-col gap-4">

        <!-- Logo -->
        <div v-if="logoSrc" class="shrink-0">
            <img
                :src="logoSrc"
                :alt="name"
                class="h-16 w-16 md:h-20 md:w-20 rounded-2xl object-contain bg-white/90 shadow-lg ring-2 ring-white/40 p-1.5"
            />
        </div>

        <!-- Business name + type / location -->
        <div>
            <!-- Primary type badge -->
            <div
                v-if="businessType"
                class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium mb-3"
                style="background-color: var(--site-primary); color: var(--site-primary-fg); opacity: 0.92"
            >
                {{ businessType }}
            </div>

            <h1 class="text-3xl md:text-5xl font-bold text-white leading-tight tracking-tight drop-shadow-sm">
                {{ name }}
            </h1>

            <p
                v-if="locationLabel"
                class="mt-2 flex items-center gap-1.5 text-sm md:text-base text-white/80"
            >
                <MapPin class="h-4 w-4 shrink-0 text-white/60" />
                {{ locationLabel }}
            </p>


        </div>

    </div>
</template>
