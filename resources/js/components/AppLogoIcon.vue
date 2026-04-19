<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    className?: HTMLAttributes['class'];
}

defineProps<Props>();
</script>

<template>
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 500 160" :class="className" v-bind="$attrs">
        <text x="60" y="115" font-family="system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif" font-size="100" font-weight="600" fill="currentColor">3</text>
        <circle cx="143" cy="90" r="9" fill="#1e66f5"/>
        <text x="170" y="115" font-family="system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif" font-size="100" font-weight="600" fill="currentColor">2</text>
        <circle cx="253" cy="90" r="9" fill="#1e66f5"/>
        <text x="280" y="115" font-family="system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif" font-size="100" font-weight="600" fill="currentColor">1</text>
    </svg>
</template>
