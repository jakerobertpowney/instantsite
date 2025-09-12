<script setup lang="ts">

defineProps({
    description: String
})

</script>

<template>
    <section>
        <p>{{ description }}</p>
    </section>
</template>
