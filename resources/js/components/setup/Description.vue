<script setup lang="ts">
import { inject } from 'vue';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

const form = inject('form')
</script>

<template>
    <div class="flex flex-col gap-2">
        <Label for="description">Enter company description</Label>
        <Textarea
            name="description"
            id="description"
            placeholder="Describe your company"
            required
            v-model="form.description"
            class="min-h-32"
        />
    </div>
</template>
