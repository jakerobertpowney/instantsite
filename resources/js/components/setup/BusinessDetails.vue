<script setup lang="ts">
import { inject } from 'vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { AlertCircle } from 'lucide-vue-next';

const form = inject<any>('form');
</script>

<template>
    <div class="flex flex-col gap-5">
        <div class="flex flex-col gap-2">
            <Label for="business_name">Business name <span class="text-destructive">*</span></Label>
            <Input id="business_name" v-model="form.business_name" placeholder="e.g. Dave's Painting & Decorating"
                class="h-12 text-base" :class="{'border-destructive': form.errors.business_name}" />
            <p v-if="form.errors.business_name" class="text-sm text-destructive flex items-center gap-1.5">
                <AlertCircle class="h-4 w-4 shrink-0" />{{ form.errors.business_name }}
            </p>
        </div>
        <div class="flex flex-col gap-2">
            <Label for="business_type">Type of business</Label>
            <Input id="business_type" v-model="form.business_type" placeholder="e.g. Painter & Decorator"
                class="h-12 text-base" />
        </div>
        <div class="flex flex-col gap-2">
            <Label for="formatted_address">Address</Label>
            <Input id="formatted_address" v-model="form.formatted_address" placeholder="e.g. 42 High Street, Manchester, M1 1AB"
                class="h-12 text-base" autocomplete="street-address" />
        </div>

    </div>
</template>
