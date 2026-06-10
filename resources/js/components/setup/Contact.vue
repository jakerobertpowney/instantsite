<script setup lang="ts">
import { inject, ref, computed } from 'vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { Mail, Phone, MessageCircle, CheckCircle, AlertCircle } from 'lucide-vue-next';

const form = inject<any>('form');
const siteData = inject<any>('siteData', null);

const suggestedEmail = computed<string | null>(() => siteData?.contact_email ?? null);

const dismissed = ref(false);

const showSuggestion = computed(() =>
    suggestedEmail.value &&
    !dismissed.value &&
    form.contact !== suggestedEmail.value
);

const isAccepted = computed(() =>
    suggestedEmail.value && form.contact === suggestedEmail.value
);

const acceptSuggestion = () => { form.contact = suggestedEmail.value; };
const dismissSuggestion = () => { dismissed.value = true; };
</script>

<template>
    <div class="flex flex-col gap-5">

        <!-- Phone -->
        <div class="flex flex-col gap-2">
            <Label for="contact-phone" class="flex items-center gap-2 text-sm font-medium">
                <Phone class="h-4 w-4 text-muted-foreground" />
                Phone number
            </Label>
            <Input
                type="tel"
                id="contact-phone"
                inputmode="tel"
                autocomplete="tel"
                placeholder="e.g. 0161 234 5678"
                v-model="form.phone"
                class="h-12 text-base"
            />
            <p class="text-xs text-muted-foreground">
                Adds a tap-to-call button on your website.
            </p>
        </div>

        <!-- WhatsApp -->
        <div class="flex flex-col gap-2">
            <Label for="whatsapp" class="flex items-center gap-2 text-sm font-medium">
                <MessageCircle class="h-4 w-4 text-muted-foreground" />
                WhatsApp number
            </Label>
            <Input
                type="tel"
                id="whatsapp"
                inputmode="tel"
                placeholder="e.g. 447911123456"
                v-model="form.whatsapp_number"
                class="h-12 text-base"
            />
            <p class="text-xs text-muted-foreground">
                Include your country code without the + sign — UK numbers start with 44, e.g. 447911123456. Leave blank to skip.
            </p>
        </div>

        <!-- Email suggestion found from website -->
        <div class="flex flex-col gap-2">
            <Label for="contact" class="flex items-center gap-2 text-sm font-medium">
                <Mail class="h-4 w-4 text-muted-foreground" />
                Contact form email
            </Label>

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

            <div
                v-if="isAccepted"
                class="flex items-center gap-2 text-sm text-green-600 dark:text-green-400"
            >
                <CheckCircle class="h-4 w-4 shrink-0" />
                Email added
            </div>

            <Input
                type="email"
                name="contact"
                id="contact"
                placeholder="e.g. dave@hotmail.com"
                v-model="form.contact"
                class="h-12 text-base"
                :class="{ 'border-destructive ring-destructive': form.errors.contact }"
                inputmode="email"
                autocomplete="email"
            />
            <p v-if="form.errors.contact" class="text-sm text-destructive flex items-center gap-1.5">
                <AlertCircle class="h-4 w-4 shrink-0" />
                {{ form.errors.contact }}
            </p>
            <p class="text-xs text-muted-foreground">
                Customer messages from your contact form get sent here. Leave blank to skip.
            </p>
        </div>

    </div>
</template>
