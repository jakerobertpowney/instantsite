<script setup lang="ts">
import { inject, computed } from 'vue';
import { Switch } from '@/components/ui/switch';

const form = inject<any>('form');

// Each row needs an "isOpen" boolean that's the inverse of `closed`.
// We create a writable computed per index so the Switch's v-model works correctly.
function makeIsOpen(index: number) {
    return computed({
        get: () => !form.opening_hours[index].closed,
        set: (v: boolean) => { form.opening_hours[index].closed = !v; },
    });
}
</script>

<template>
    <div class="flex flex-col gap-3">
        <div
            v-for="(hours, index) in form.opening_hours"
            :key="hours.day"
            class="flex items-center gap-3 py-2 border-b border-border last:border-0"
        >
            <span class="w-24 text-sm font-medium shrink-0">{{ hours.day }}</span>
            <Switch v-model="makeIsOpen(index).value" />
            <template v-if="!hours.closed">
                <input
                    type="time"
                    v-model="form.opening_hours[index].open"
                    class="h-9 rounded-md border border-input bg-transparent px-3 text-sm"
                />
                <span class="text-muted-foreground text-sm">–</span>
                <input
                    type="time"
                    v-model="form.opening_hours[index].close"
                    class="h-9 rounded-md border border-input bg-transparent px-3 text-sm"
                />
            </template>
            <span v-else class="text-sm text-muted-foreground">Closed</span>
        </div>
    </div>
</template>
