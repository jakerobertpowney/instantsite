<script setup lang="ts">
import { inject, ref, computed } from 'vue';
import { CheckCircle, Pencil, Trash2, Plus, X, Star } from 'lucide-vue-next';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';

const form = inject<any>('form');
const siteData = inject<any>('siteData', null);

// ─── Source badge ────────────────────────────────────────────────────────────

const sourceLabels: Record<string, string> = {
    website:      'From your website',
    fresha:       'Found on Fresha',
    treatwell:    'Found on Treatwell',
    booksy:       'Found on Booksy',
    yelp:         'Found on Yelp',
    checkatrade:  'Found on Checkatrade',
    rated_people: 'Found on Rated People',
    trustatrader: 'Found on TrustATrader',
    bark:         'Found on Bark.com',
    ai:           'Suggested — edit to match yours',
};

const source = computed<string>(() => siteData?.suggested_services_source ?? 'ai');
const sourceLabel = computed<string>(() => sourceLabels[source.value] ?? 'Suggested services');

// ─── Service type (service vs product label) ──────────────────────────────────

const itemLabel = computed(() => {
    const type = (siteData?.primaryTypeDisplayName?.text ?? '').toLowerCase();
    if (type.includes('restaurant') || type.includes('cafe') || type.includes('bakery') || type.includes('food')) {
        return { singular: 'item', plural: 'items', verb: 'Add an item' };
    }
    if (type.includes('shop') || type.includes('store') || type.includes('retail')) {
        return { singular: 'product', plural: 'products', verb: 'Add a product' };
    }
    return { singular: 'service', plural: 'services', verb: 'Add a service' };
});

// ─── State ───────────────────────────────────────────────────────────────────

// Track which suggested service IDs the user has dismissed
const dismissed = ref<Set<string>>(new Set());

const suggestions = computed(() =>
    (siteData?.suggested_services ?? []).filter(
        (s: any) => !dismissed.value.has(s.id) && !form.services.some((f: any) => f.id === s.id)
    )
);

const hasSuggestions = computed(() => suggestions.value.length > 0);

// Inline editing state — tracks the index currently being edited
const editingIndex = ref<number | null>(null);

// ─── Actions ─────────────────────────────────────────────────────────────────

const acceptSuggestion = (suggestion: any) => {
    form.services.push({ ...suggestion });
    dismissed.value = new Set([...dismissed.value, suggestion.id]);
};

const acceptAll = () => {
    for (const s of suggestions.value) {
        form.services.push({ ...s });
    }
    dismissed.value = new Set([
        ...dismissed.value,
        ...suggestions.value.map((s: any) => s.id),
    ]);
};

const dismissSuggestion = (id: string) => {
    dismissed.value = new Set([...dismissed.value, id]);
};

const addBlank = () => {
    form.services.push({
        id:          crypto.randomUUID(),
        name:        '',
        description: null,
        price:       null,
        show_price:  true,
        featured:    false,
    });
    editingIndex.value = form.services.length - 1;
};

const removeService = (index: number) => {
    if (editingIndex.value === index) editingIndex.value = null;
    form.services.splice(index, 1);
};

const toggleFeatured = (index: number) => {
    form.services[index].featured = !form.services[index].featured;
};

const startEdit = (index: number) => {
    editingIndex.value = editingIndex.value === index ? null : index;
};
</script>

<template>
    <div class="flex flex-col gap-6">

        <!-- ── Suggestions banner ──────────────────────────────────────────── -->
        <div v-if="hasSuggestions" class="flex flex-col gap-3">

            <div class="flex items-center justify-between">
                <p class="text-xs font-medium text-muted-foreground uppercase tracking-wide">
                    {{ sourceLabel }}
                </p>
                <button
                    v-if="suggestions.length > 1"
                    type="button"
                    class="text-xs font-medium underline underline-offset-2 text-primary"
                    @click="acceptAll"
                >
                    Add all {{ suggestions.length }}
                </button>
            </div>

            <div
                v-for="suggestion in suggestions"
                :key="suggestion.id"
                class="rounded-xl border-2 border-dashed bg-muted/40 px-4 py-3 flex flex-col gap-1.5"
            >
                <div class="flex items-start justify-between gap-2">
                    <div class="flex flex-col gap-0.5 min-w-0">
                        <span class="text-sm font-medium truncate">{{ suggestion.name }}</span>
                        <span v-if="suggestion.description" class="text-xs text-muted-foreground line-clamp-2">
                            {{ suggestion.description }}
                        </span>
                    </div>
                    <span v-if="suggestion.price" class="text-sm font-semibold shrink-0 tabular-nums" style="color: var(--site-primary, #000)">
                        {{ suggestion.price }}
                    </span>
                </div>
                <div class="flex items-center gap-2 mt-1">
                    <Button
                        type="button"
                        size="sm"
                        class="h-8 px-3 text-xs gap-1.5"
                        @click="acceptSuggestion(suggestion)"
                    >
                        <CheckCircle class="h-3.5 w-3.5" />
                        Add this
                    </Button>
                    <button
                        type="button"
                        class="text-xs text-muted-foreground hover:text-foreground"
                        @click="dismissSuggestion(suggestion.id)"
                    >
                        Not mine
                    </button>
                </div>
            </div>
        </div>

        <!-- ── Added services ─────────────────────────────────────────────── -->
        <div v-if="form.services.length > 0" class="flex flex-col gap-3">

            <p v-if="hasSuggestions" class="text-xs font-medium text-muted-foreground uppercase tracking-wide">
                Added
            </p>

            <div
                v-for="(service, index) in form.services"
                :key="service.id"
                class="rounded-xl border bg-muted/20 overflow-hidden"
            >
                <!-- Row summary (always visible) -->
                <div class="flex items-center gap-3 px-4 py-3">
                    <!-- Featured star -->
                    <button
                        type="button"
                        class="shrink-0 transition-colors"
                        :class="service.featured ? 'text-amber-500' : 'text-muted-foreground/40 hover:text-muted-foreground'"
                        :title="service.featured ? 'Remove from featured' : 'Mark as featured'"
                        @click="toggleFeatured(index)"
                    >
                        <Star class="h-4 w-4" :fill="service.featured ? 'currentColor' : 'none'" />
                    </button>

                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium truncate">
                            {{ service.name || '(no name yet)' }}
                        </p>
                        <p v-if="service.price && service.show_price" class="text-xs text-muted-foreground">
                            {{ service.price }}
                        </p>
                        <p v-else-if="!service.price" class="text-xs text-muted-foreground italic">
                            No price set
                        </p>
                    </div>

                    <div class="flex items-center gap-1 shrink-0">
                        <button
                            type="button"
                            class="p-1.5 rounded text-muted-foreground hover:text-foreground transition-colors"
                            :title="editingIndex === index ? 'Close' : 'Edit'"
                            @click="startEdit(index)"
                        >
                            <X v-if="editingIndex === index" class="h-4 w-4" />
                            <Pencil v-else class="h-4 w-4" />
                        </button>
                        <button
                            type="button"
                            class="p-1.5 rounded text-muted-foreground hover:text-destructive transition-colors"
                            title="Remove"
                            @click="removeService(index)"
                        >
                            <Trash2 class="h-4 w-4" />
                        </button>
                    </div>
                </div>

                <!-- Inline editor (shown when editing this item) -->
                <div v-if="editingIndex === index" class="border-t bg-muted/30 px-4 py-4 flex flex-col gap-3">

                    <div class="flex flex-col gap-1.5">
                        <label :for="`svc-name-${index}`" class="text-xs text-muted-foreground">
                            {{ itemLabel.singular.charAt(0).toUpperCase() + itemLabel.singular.slice(1) }} name
                        </label>
                        <Input
                            :id="`svc-name-${index}`"
                            type="text"
                            v-model="form.services[index].name"
                            :placeholder="`e.g. Gents Haircut`"
                            class="h-10"
                        />
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label :for="`svc-desc-${index}`" class="text-xs text-muted-foreground">
                            Short description <span class="opacity-60">(optional)</span>
                        </label>
                        <Input
                            :id="`svc-desc-${index}`"
                            type="text"
                            :value="form.services[index].description ?? ''"
                            @input="form.services[index].description = ($event.target as HTMLInputElement).value || null"
                            placeholder="e.g. Includes shampoo and blow dry"
                            class="h-10"
                        />
                    </div>

                    <div class="flex gap-3">
                        <div class="flex-1 flex flex-col gap-1.5">
                            <label :for="`svc-price-${index}`" class="text-xs text-muted-foreground">
                                Price <span class="opacity-60">(optional)</span>
                            </label>
                            <Input
                                :id="`svc-price-${index}`"
                                type="text"
                                :value="form.services[index].price ?? ''"
                                @input="form.services[index].price = ($event.target as HTMLInputElement).value || null"
                                placeholder="e.g. £18 or From £45"
                                class="h-10"
                            />
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label class="text-xs text-muted-foreground">Show price</label>
                            <label class="flex items-center gap-2 h-10 cursor-pointer select-none">
                                <input
                                    type="checkbox"
                                    v-model="form.services[index].show_price"
                                    class="rounded border-input"
                                />
                                <span class="text-sm">Visible</span>
                            </label>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <label class="flex items-center gap-2 cursor-pointer select-none">
                            <input
                                type="checkbox"
                                v-model="form.services[index].featured"
                                class="rounded border-input"
                            />
                            <span class="text-sm">
                                <Star class="h-3.5 w-3.5 inline text-amber-500 mr-0.5" fill="currentColor" />
                                Featured — shows this {{ itemLabel.singular }} more prominently
                            </span>
                        </label>
                    </div>

                </div>
            </div>
        </div>

        <!-- ── Add button ─────────────────────────────────────────────────── -->
        <Button
            type="button"
            variant="outline"
            class="w-full h-11 gap-2"
            @click="addBlank"
        >
            <Plus class="h-4 w-4" />
            {{ form.services.length === 0 ? itemLabel.verb : `Add another ${itemLabel.singular}` }}
        </Button>

        <!-- ── Empty state hint ───────────────────────────────────────────── -->
        <p v-if="form.services.length === 0 && !hasSuggestions" class="text-xs text-muted-foreground text-center">
            Not sure what to add? You can set up your {{ itemLabel.plural }} later from your dashboard.
        </p>

    </div>
</template>
