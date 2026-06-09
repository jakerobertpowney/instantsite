<script setup lang="ts">
import { inject, ref, computed } from 'vue';
import { Trash2, Plus, ExternalLink, CheckCircle, AlertCircle } from 'lucide-vue-next';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';

const form = inject<any>('form');
const siteData = inject<any>('siteData', null);

// Booking platform suggestions scraped from their website
type BookingLink = { platform: string; label: string; url: string };

// No longer using suggested_booking_links — FetchSocialLinks now writes directly to quick_links
const suggestedBookingLinks = computed<BookingLink[]>(() => []);

// Track which suggestions have been accepted or dismissed
const acceptedUrls = ref<Set<string>>(new Set());
const dismissedUrls = ref<Set<string>>(new Set());

const visibleSuggestions = computed(() =>
    suggestedBookingLinks.value.filter(
        s => !acceptedUrls.value.has(s.url) && !dismissedUrls.value.has(s.url)
    )
);

const hasSuggestions = computed(() => visibleSuggestions.value.length > 0);

const acceptBookingSuggestion = (suggestion: BookingLink) => {
    form.quickLinks.push({ label: suggestion.label, link: suggestion.url });
    acceptedUrls.value = new Set([...acceptedUrls.value, suggestion.url]);
};

const dismissBookingSuggestion = (suggestion: BookingLink) => {
    dismissedUrls.value = new Set([...dismissedUrls.value, suggestion.url]);
};

const platformLabel: Record<string, string> = {
    calendly:   'Calendly',
    booksy:     'Booksy',
    fresha:     'Fresha',
    treatwell:  'Treatwell',
    vagaro:     'Vagaro',
    acuity:     'Acuity Scheduling',
    simplybook: 'SimplyBook',
    opentable:  'OpenTable',
};

const EXAMPLES = [
    { label: 'Book a visit',      link: '' },
    { label: 'Get a free quote',  link: '' },
    { label: 'Order online',      link: '' },
];

const addFromExample = (example: { label: string; link: string }) => {
    form.quickLinks.push({ label: example.label, link: '' });
};

const addBlank = () => {
    form.quickLinks.push({ label: '', link: '' });
};

const remove = (index: number) => {
    form.quickLinks.splice(index, 1);
};

// Friendly display of a URL — strip https:// and trailing slash
const displayUrl = (url: string) =>
    url.replace(/^https?:\/\//, '').replace(/\/$/, '');
</script>

<template>
    <div class="flex flex-col gap-5">

        <!-- Booking platform suggestions found on their website -->
        <div v-if="hasSuggestions" class="flex flex-col gap-3">
            <p class="text-xs font-medium text-muted-foreground uppercase tracking-wide">
                Found on your website
            </p>

            <div
                v-for="suggestion in visibleSuggestions"
                :key="suggestion.url"
                class="rounded-lg border-2 border-dashed bg-muted/40 px-4 py-3 flex flex-col gap-2"
            >
                <p class="text-sm font-medium">
                    We found your {{ platformLabel[suggestion.platform] ?? 'booking page' }}
                </p>
                <p class="text-xs text-muted-foreground flex items-center gap-1 truncate">
                    <ExternalLink class="h-3 w-3 shrink-0" />
                    {{ displayUrl(suggestion.url) }}
                </p>
                <div class="flex items-center gap-2 mt-1">
                    <Button
                        type="button"
                        size="sm"
                        class="h-8 px-3 text-xs gap-1.5"
                        @click="acceptBookingSuggestion(suggestion)"
                    >
                        <CheckCircle class="h-3.5 w-3.5" />
                        Yes, add a "{{ suggestion.label }}" button
                    </Button>
                    <button
                        type="button"
                        class="text-xs text-muted-foreground hover:text-foreground"
                        @click="dismissBookingSuggestion(suggestion)"
                    >
                        Not mine
                    </button>
                </div>
            </div>
        </div>

        <!-- Example starters (shown when no links added yet and no suggestions) -->
        <div v-if="form.quickLinks.length === 0 && !hasSuggestions" class="flex flex-col gap-2">
            <p class="text-xs font-medium text-muted-foreground uppercase tracking-wide">
                Common examples — tap one to add it
            </p>
            <div class="flex flex-wrap gap-2">
                <button
                    v-for="ex in EXAMPLES"
                    :key="ex.label"
                    type="button"
                    class="rounded-full border px-4 py-1.5 text-sm hover:bg-muted transition-colors"
                    @click="addFromExample(ex)"
                >
                    + {{ ex.label }}
                </button>
            </div>
        </div>

        <!-- Added links -->
        <div v-if="form.quickLinks.length > 0" class="flex flex-col gap-4">
            <div
                v-for="(link, index) in form.quickLinks"
                :key="index"
                class="rounded-xl border bg-muted/30 p-4 flex flex-col gap-3"
            >
                <div class="flex items-center justify-between">
                    <span class="text-xs font-medium text-muted-foreground uppercase tracking-wide">
                        Button {{ index + 1 }}
                    </span>
                    <button
                        type="button"
                        class="text-muted-foreground hover:text-destructive transition-colors"
                        @click="remove(index)"
                        aria-label="Remove button"
                    >
                        <Trash2 class="h-4 w-4" />
                    </button>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label :for="`btn-label-${index}`" class="text-xs text-muted-foreground">
                        Button text (what the customer sees)
                    </label>
                    <Input
                        type="text"
                        :id="`btn-label-${index}`"
                        placeholder="e.g. Book a visit"
                        v-model="form.quickLinks[index].label"
                        class="h-11"
                        :class="{ 'border-destructive ring-destructive': form.errors[`quickLinks.${index}.label`] }"
                    />
                    <p v-if="form.errors[`quickLinks.${index}.label`]" class="text-sm text-destructive flex items-center gap-1.5">
                        <AlertCircle class="h-4 w-4 shrink-0" />
                        {{ form.errors[`quickLinks.${index}.label`] }}
                    </p>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label :for="`btn-link-${index}`" class="text-xs text-muted-foreground flex items-center gap-1">
                        <ExternalLink class="h-3 w-3" /> Where it takes them (the web address)
                    </label>
                    <Input
                        type="url"
                        :id="`btn-link-${index}`"
                        placeholder="e.g. https://calendly.com/yourname"
                        v-model="form.quickLinks[index].link"
                        class="h-11"
                        :class="{ 'border-destructive ring-destructive': form.errors[`quickLinks.${index}.link`] }"
                        inputmode="url"
                        autocomplete="off"
                    />
                    <p v-if="form.errors[`quickLinks.${index}.link`]" class="text-sm text-destructive flex items-center gap-1.5">
                        <AlertCircle class="h-4 w-4 shrink-0" />
                        {{ form.errors[`quickLinks.${index}.link`] }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Add another -->
        <Button
            type="button"
            variant="outline"
            class="w-full h-11 gap-2"
            @click="addBlank"
        >
            <Plus class="h-4 w-4" />
            {{ form.quickLinks.length === 0 ? 'Add a button' : 'Add another button' }}
        </Button>

        <p v-if="form.quickLinks.length === 0 && !hasSuggestions" class="text-xs text-muted-foreground text-center">
            Not sure? You can always add buttons later from your dashboard.
        </p>

    </div>
</template>
