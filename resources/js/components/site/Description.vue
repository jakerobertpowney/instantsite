<script setup lang="ts">
defineProps({
    description: String
});
</script>

<template>
    <section class="py-14">
        <p
            class="text-xs font-semibold uppercase tracking-widest mb-4"
            style="color: var(--site-primary)"
        >
            About us
        </p>
        <p class="text-gray-700 text-lg leading-relaxed max-w-2xl">
            {{ description }}
        </p>
    </section>
</template>
