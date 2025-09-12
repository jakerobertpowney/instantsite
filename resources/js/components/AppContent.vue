<script setup lang="ts">
import { SidebarInset } from '@/components/ui/sidebar';
import { computed } from 'vue';

interface Props {
    variant?: 'header' | 'sidebar';
    class?: string;
}

const props = defineProps<Props>();
const className = computed(() => props.class);
</script>

<template>
    <SidebarInset v-if="props.variant === 'sidebar'" :class="className">
        <slot />
    </SidebarInset>
    <section v-else class="bg-gray-50 min-h-screen py-8">
        <main class="mx-auto flex h-full w-full max-w-5xl flex-1 flex-col gap-4 rounded-xl" :class="className">
            <slot />
        </main>
    </section>
</template>
