<script setup lang="ts">
import { inject, ref } from 'vue';
import { Switch } from '@/components/ui/switch';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const form = inject('form')
const enabled = ref(false)
</script>

<template>
    <div class="flex flex-col gap-4">
        <div class="flex items-center gap-3">
            <Switch id="contact-toggle" v-model="enabled" />
            <Label for="contact-toggle">Enable contact form</Label>
        </div>

        <div v-if="enabled" class="flex flex-col gap-2">
            <Label for="to">To Address</Label>
            <Input
                type="text"
                name="to"
                id="to"
                placeholder="yourname@domain.com"
                v-model="form.contact"
            />
        </div>
    </div>
</template>
