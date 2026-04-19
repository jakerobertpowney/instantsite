<script setup lang="ts">
/**
 * ServiceInlineEditor
 *
 * Holds all typing state locally so that keystrokes NEVER bubble up to the
 * parent Components.vue and trigger its enormous render function.
 *
 * The parent reads the final values via getDraft() (called by flushDraft on
 * close / save), not via a prop binding — so the parent is completely still
 * while the user types.
 */
import { ref } from 'vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Star } from 'lucide-vue-next';
import { CURRENCIES } from '@/lib/currencies';

interface Service {
    id: string;
    name: string;
    description: string | null;
    price: string | null;
    currency: string;
    show_price: boolean;
    featured: boolean;
}

const props = defineProps<{ initial: Service }>();

// Independent local refs — not connected to any parent reactive state
const name        = ref(props.initial.name);
const description = ref(props.initial.description ?? '');
const price       = ref(props.initial.price ?? '');
const currency    = ref(props.initial.currency || 'GBP');
const showPrice   = ref(props.initial.show_price);
const featured    = ref(props.initial.featured);

// Parent calls this once (on close / on save) to retrieve the edited values
defineExpose({
    getDraft(): Service {
        return {
            id:          props.initial.id,
            name:        name.value,
            description: description.value || null,
            price:       price.value || null,
            currency:    currency.value,
            show_price:  showPrice.value,
            featured:    featured.value,
        };
    },
});
</script>

<template>
    <div class="border-t px-4 py-4 flex flex-col gap-3">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">

            <!-- Name -->
            <div class="flex flex-col gap-1.5">
                <Label :for="`svc-name-${initial.id}`" class="text-sm">Name</Label>
                <Input
                    :id="`svc-name-${initial.id}`"
                    v-model="name"
                    placeholder="e.g. Gents Haircut"
                    class="h-10"
                />
            </div>

            <!-- Price with currency prefix -->
            <div class="flex flex-col gap-1.5">
                <Label :for="`svc-price-${initial.id}`" class="text-sm">
                    Price <span class="text-muted-foreground font-normal">(optional)</span>
                </Label>
                <div class="flex gap-2">
                    <!-- Currency selector + price input, visually joined -->
                    <div class="flex flex-1 min-w-0">
                        <select
                            v-model="currency"
                            class="h-10 shrink-0 rounded-l-md rounded-r-none border border-r-0 border-input bg-muted pl-2 pr-6 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring focus:z-10 cursor-pointer"
                            :title="currency"
                        >
                            <option
                                v-for="c in CURRENCIES"
                                :key="c.code"
                                :value="c.code"
                            >{{ c.symbol }} {{ c.code }}</option>
                        </select>
                        <Input
                            :id="`svc-price-${initial.id}`"
                            v-model="price"
                            placeholder="e.g. 18.00"
                            inputmode="decimal"
                            class="h-10 flex-1 rounded-l-none"
                            @keydown="(e: KeyboardEvent) => {
                                // Allow: digits, one decimal point, backspace, delete,
                                // tab, escape, enter, and arrow/home/end navigation
                                const allowed = /^[0-9.]$/.test(e.key);
                                const nav = ['Backspace','Delete','Tab','Escape','Enter',
                                             'ArrowLeft','ArrowRight','Home','End'].includes(e.key);
                                const shortcut = e.ctrlKey || e.metaKey;
                                // Block a second decimal point
                                const alreadyHasDecimal = price.includes('.') && e.key === '.';
                                if (!allowed && !nav && !shortcut) e.preventDefault();
                                if (alreadyHasDecimal) e.preventDefault();
                            }"
                        />
                    </div>
                    <label class="flex items-center gap-1.5 shrink-0 cursor-pointer select-none text-sm">
                        <input type="checkbox" v-model="showPrice" class="rounded" />
                        Show
                    </label>
                </div>
            </div>
        </div>

        <!-- Description -->
        <div class="flex flex-col gap-1.5">
            <Label :for="`svc-desc-${initial.id}`" class="text-sm">
                Description <span class="text-muted-foreground font-normal">(optional)</span>
            </Label>
            <Input
                :id="`svc-desc-${initial.id}`"
                v-model="description"
                placeholder="e.g. Includes shampoo and blow dry"
                class="h-10"
            />
        </div>

        <!-- Featured -->
        <label class="flex items-center gap-2 cursor-pointer select-none text-sm">
            <input type="checkbox" v-model="featured" class="rounded" />
            <Star class="h-3.5 w-3.5 text-amber-500" fill="currentColor" />
            Featured — displayed more prominently at the top
        </label>
    </div>
</template>
