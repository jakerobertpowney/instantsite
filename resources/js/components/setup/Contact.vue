<script setup lang="ts">
import { inject, ref, computed } from 'vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { Mail, CheckCircle } from 'lucide-vue-next';

const form = inject<any>('form');
const siteData = inject<any>('siteData', null);

const suggestedEmail = computed<string | null>(() => siteData?.suggested_email ?? null);

const dismissed = ref(false);

const showSuggestion = computed(() =>
    suggestedEmail.value &&
    !dismissed.value &&
    form.contact !== suggestedEmail.value
);

const isAccepted = computed(() =>
    suggestedEmail.value && form.contact === suggestedEmail.value
);

const acceptSuggestion = () => {
    form.contact = suggestedEmail.value;
};

const dismissSuggestion = () => {
    dismissed.value = true;
};
</script>

<template>
    <div class="flex flex-col gap-3">

        <!-- Email suggestion found from website -->
        <div
            v-if="showSuggestion"
            class="rounded-lg border-2 border-dashed bg-muted/40 px-4 py-3 flex flex-col gap-2"
        >
            <p class="text-xs font-medium text-muted-foreground uppercase tracking-wide">
                Found on your website
            </p>
            <div class="flex items-center gap-3">
                <Mail class="h-4 w-4 text-muted-foreground shrink-0" />
                <span class="flex-1 text-sm font-medium truncate">{{ suggestedEmail }}</span>
                <Button
                    type="button"
                    size="sm"
                    variant="secondary"
                    class="shrink-0 h-7 px-3 text-xs"
                    @click="acceptSuggestion"
                >
                    Yes, that's me
                </Button>
                <button
                    type="button"
                    class="text-xs text-muted-foreground hover:text-foreground shrink-0"
                    @click="dismissSuggestion"
                >
                    Not mine
                </button>
            </div>
        </div>

        <!-- Accepted state -->
        <div
            v-if="isAccepted"
            class="flex items-center gap-2 text-sm text-green-600 dark:text-green-400"
        >
            <CheckCircle class="h-4 w-4 shrink-0" />
            Email added
        </div>

        <Label for="contact" class="flex items-center gap-2 text-sm font-medium">
            <Mail class="h-4 w-4 text-muted-foreground" />
            Your email address
        </Label>
        <Input
            type="email"
            name="contact"
            id="contact"
            placeholder="e.g. dave@hotmail.com"
            v-model="form.contact"
            class="h-12 text-base"
            inputmode="email"
            autocomplete="email"
        />
        <p class="text-sm text-muted-foreground">
            When a customer fills in the contact form on your website, their message gets sent here.
            Leave it blank if you'd rather skip this.
        </p>
    </div>
</template>
