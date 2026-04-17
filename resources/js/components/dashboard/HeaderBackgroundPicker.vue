<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Check, Image, Palette, Search, Sparkles, Upload } from 'lucide-vue-next';

// ─── Types ────────────────────────────────────────────────────────────────────

type BgType = 'auto' | 'google_image' | 'custom_image' | 'color' | 'stock';

type StockPhoto = {
    id: number;
    url: string;
    thumb: string;
    alt: string;
    credit: string;
    credit_url: string;
};

// ─── Props / emits ────────────────────────────────────────────────────────────

const props = defineProps<{
    bgType: BgType;
    bgValue: string;
    bgThumb: string;
    bgCredit: string;
    bgCreditUrl: string;
    googleImages: string[];   // relative paths, e.g. "storage/images/.../photo_1.jpg"
    businessName: string;
    businessType: string;
}>();

const emit = defineEmits<{
    'update:bgType': [v: BgType];
    'update:bgValue': [v: string];
    'update:bgThumb': [v: string];
    'update:bgCredit': [v: string];
    'update:bgCreditUrl': [v: string];
    'update:imageFile': [v: File | null];
}>();

// ─── Mode selector ────────────────────────────────────────────────────────────

const modes: { key: BgType; label: string; icon: typeof Image }[] = [
    { key: 'auto',         label: 'Auto',         icon: Sparkles },
    { key: 'google_image', label: 'My photos',     icon: Image    },
    { key: 'custom_image', label: 'Upload',        icon: Upload   },
    { key: 'color',        label: 'Colour',        icon: Palette  },
    { key: 'stock',        label: 'Stock photos',  icon: Search   },
];

function selectMode(mode: BgType) {
    emit('update:bgType', mode);
    emit('update:bgValue', '');
    emit('update:bgThumb', '');
    emit('update:bgCredit', '');
    emit('update:bgCreditUrl', '');
    emit('update:imageFile', null);
    customBgPreview.value = null;
}

// ─── Current background preview (mini hero) ───────────────────────────────────

const previewStyle = computed<Record<string, string>>(() => {
    if (props.bgType === 'color' && props.bgValue) {
        return { background: props.bgValue };
    }
    if (props.bgType === 'google_image' && props.bgValue) {
        return { backgroundImage: `url('/${props.bgValue}')`, backgroundSize: 'cover', backgroundPosition: 'center' };
    }
    if ((props.bgType === 'custom_image' || props.bgType === 'stock') && props.bgValue) {
        return { backgroundImage: `url('${props.bgValue}')`, backgroundSize: 'cover', backgroundPosition: 'center' };
    }
    if (props.bgType === 'auto' && props.googleImages.length > 0) {
        return { backgroundImage: `url('/${props.googleImages[0]}')`, backgroundSize: 'cover', backgroundPosition: 'center' };
    }
    return { background: 'linear-gradient(135deg, #1e3a6e 0%, #0f2346 100%)' };
});

// ─── Google photos ────────────────────────────────────────────────────────────

// ─── Custom image upload ──────────────────────────────────────────────────────

const customBgInput = ref<HTMLInputElement | null>(null);
const customBgPreview = ref<string | null>(null);

// If we already have a custom image saved, initialise preview from the value
watch(() => props.bgType, (t) => {
    if (t === 'custom_image' && props.bgValue && !customBgPreview.value) {
        customBgPreview.value = props.bgValue;
    }
}, { immediate: true });

function triggerCustomBgUpload() {
    customBgInput.value?.click();
}

function onCustomBgChange(event: Event) {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    if (file) {
        customBgPreview.value = URL.createObjectURL(file);
        emit('update:bgValue', customBgPreview.value);
        emit('update:imageFile', file);
    }
}

// ─── Colour swatches ──────────────────────────────────────────────────────────

const colourSwatches = [
    // Dark, rich backgrounds that look great with the white-text scrim overlay
    { hex: '#0f172a', label: 'Midnight' },
    { hex: '#1e3a5f', label: 'Navy'     },
    { hex: '#14532d', label: 'Forest'   },
    { hex: '#3b0764', label: 'Plum'     },
    { hex: '#450a0a', label: 'Burgundy' },
    { hex: '#134e4a', label: 'Teal'     },
    { hex: '#713f12', label: 'Walnut'   },
    { hex: '#1c1917', label: 'Charcoal' },
    // Mid-tone options
    { hex: '#1d4ed8', label: 'Cobalt'   },
    { hex: '#0369a1', label: 'Sapphire' },
    { hex: '#059669', label: 'Emerald'  },
    { hex: '#7c3aed', label: 'Violet'   },
    { hex: '#dc2626', label: 'Crimson'  },
    { hex: '#d97706', label: 'Amber'    },
    { hex: '#0891b2', label: 'Cyan'     },
    { hex: '#475569', label: 'Slate'    },
];

function selectColour(hex: string) {
    emit('update:bgValue', hex);
}

// ─── Stock photos (Pexels) ────────────────────────────────────────────────────

const stockQuery  = ref('');
const stockPhotos = ref<StockPhoto[]>([]);
const stockLoading = ref(false);
const stockAvailable = ref<boolean | null>(null); // null = not checked yet

// Derive a sensible default query from business type/name
const defaultStockQuery = computed(() =>
    props.businessType || props.businessName || 'professional business',
);

async function searchStock(query?: string) {
    const q = ((query ?? stockQuery.value) || defaultStockQuery.value).trim();
    if (!q) return;

    stockLoading.value  = true;
    stockPhotos.value   = [];

    try {
        const url = new URL('/dashboard/stock-photos', window.location.origin);
        url.searchParams.set('q', q);
        const res = await fetch(url.toString(), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        });
        const json = await res.json();
        stockAvailable.value = json.available ?? false;
        stockPhotos.value    = json.photos ?? [];
    } catch {
        stockAvailable.value = false;
    } finally {
        stockLoading.value = false;
    }
}

// Trigger default search when user switches to stock tab
watch(() => props.bgType, (t) => {
    if (t === 'stock' && stockPhotos.value.length === 0 && stockAvailable.value !== false) {
        stockQuery.value = defaultStockQuery.value;
        searchStock(defaultStockQuery.value);
    }
});

function selectStockPhoto(photo: StockPhoto) {
    emit('update:bgValue',      photo.url);
    emit('update:bgThumb',      photo.thumb);
    emit('update:bgCredit',     photo.credit);
    emit('update:bgCreditUrl',  photo.credit_url);
}
</script>

<template>
    <div class="flex flex-col gap-4">
        <!-- Label -->
        <div>
            <Label class="text-base font-semibold">Header background</Label>
            <p class="text-sm text-muted-foreground mt-0.5">Choose what appears behind your business name at the top of your site.</p>
        </div>

        <!-- Mini hero preview -->
        <div
            class="relative w-full rounded-xl overflow-hidden border"
            style="height: 80px"
            :style="previewStyle"
        >
            <div class="absolute inset-0" style="background: linear-gradient(to top, rgba(0,0,0,0.55) 0%, rgba(0,0,0,0.15) 100%)" />
            <div class="relative z-10 flex items-end h-full px-4 pb-3">
                <span class="text-white text-sm font-semibold opacity-90 drop-shadow">Preview</span>
            </div>
        </div>

        <!-- Mode selector -->
        <div class="grid grid-cols-5 gap-1.5">
            <button
                v-for="mode in modes"
                :key="mode.key"
                type="button"
                class="flex flex-col items-center gap-1 rounded-lg border-2 px-2 py-2.5 text-xs font-medium transition-colors"
                :class="bgType === mode.key
                    ? 'border-primary bg-primary/5 text-primary'
                    : 'border-transparent bg-muted/60 text-muted-foreground hover:bg-muted hover:text-foreground'"
                @click="selectMode(mode.key)"
            >
                <component :is="mode.icon" class="h-4 w-4 shrink-0" />
                {{ mode.label }}
            </button>
        </div>

        <!-- Auto panel -->
        <div v-if="bgType === 'auto'" class="rounded-lg bg-muted/50 px-4 py-3 text-sm text-muted-foreground">
            <p v-if="googleImages.length > 0">
                Using your first Google photo automatically. Switch to a different mode to change it.
            </p>
            <p v-else>
                No Google photos found — a gradient using your brand colours will be shown instead. Switch to "Colour" to choose a specific background colour.
            </p>
        </div>

        <!-- Google photos panel -->
        <div v-else-if="bgType === 'google_image'">
            <div v-if="googleImages.length > 0" class="grid grid-cols-3 gap-2">
                <button
                    v-for="(img, i) in googleImages"
                    :key="i"
                    type="button"
                    class="relative overflow-hidden rounded-lg border-2 transition-all focus:outline-none focus-visible:ring-2"
                    :class="bgValue === img ? 'border-primary ring-1 ring-primary' : 'border-transparent hover:border-muted-foreground/40'"
                    style="aspect-ratio: 16/9"
                    @click="$emit('update:bgValue', img)"
                >
                    <img :src="'/' + img" :alt="`Photo ${i + 1}`" class="w-full h-full object-cover" />
                    <div v-if="bgValue === img" class="absolute inset-0 bg-primary/20 flex items-center justify-center">
                        <div class="bg-primary rounded-full p-1">
                            <Check class="h-3 w-3 text-primary-foreground" />
                        </div>
                    </div>
                </button>
            </div>
            <p v-else class="text-sm text-muted-foreground italic">
                No Google photos available. Try uploading your own instead.
            </p>
        </div>

        <!-- Upload panel -->
        <div v-else-if="bgType === 'custom_image'" class="flex flex-col gap-3">
            <div v-if="customBgPreview" class="relative overflow-hidden rounded-lg border" style="aspect-ratio: 16/9; max-height: 180px">
                <img :src="customBgPreview" alt="Background preview" class="w-full h-full object-cover" />
            </div>
            <div class="flex items-center gap-3">
                <Button type="button" variant="outline" size="lg" class="text-base h-11 gap-2" @click="triggerCustomBgUpload">
                    <Upload class="h-5 w-5" />
                    {{ customBgPreview ? 'Change image' : 'Upload an image' }}
                </Button>
                <span class="text-sm text-muted-foreground">JPG, PNG or WebP. Max 5MB.</span>
            </div>
            <input ref="customBgInput" type="file" accept="image/*" class="hidden" @change="onCustomBgChange" />
        </div>

        <!-- Colour panel -->
        <div v-else-if="bgType === 'color'" class="flex flex-col gap-3">
            <div class="grid grid-cols-8 gap-2">
                <button
                    v-for="swatch in colourSwatches"
                    :key="swatch.hex"
                    type="button"
                    class="relative rounded-lg border-2 transition-all focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-1"
                    :class="bgValue === swatch.hex ? 'border-foreground ring-1 ring-foreground' : 'border-transparent hover:border-muted-foreground/50'"
                    :style="{ backgroundColor: swatch.hex, width: '100%', aspectRatio: '1' }"
                    :title="swatch.label"
                    @click="selectColour(swatch.hex)"
                >
                    <Check v-if="bgValue === swatch.hex" class="absolute inset-0 m-auto h-4 w-4 text-white drop-shadow" />
                </button>
            </div>
            <!-- Custom hex input -->
            <div class="flex items-center gap-2 mt-1">
                <div
                    class="h-9 w-9 rounded-md border shrink-0"
                    :style="{ backgroundColor: bgValue || '#0f172a' }"
                />
                <Input
                    :value="bgValue"
                    placeholder="#1e3a5f"
                    class="h-9 font-mono text-sm max-w-[120px]"
                    maxlength="7"
                    @input="(e: Event) => $emit('update:bgValue', (e.target as HTMLInputElement).value)"
                />
                <span class="text-sm text-muted-foreground">Or enter any hex colour</span>
            </div>
        </div>

        <!-- Stock photos panel -->
        <div v-else-if="bgType === 'stock'" class="flex flex-col gap-3">
            <!-- API not configured -->
            <template v-if="stockAvailable === false && !stockLoading">
                <div class="rounded-lg bg-muted/50 px-4 py-4 text-sm text-muted-foreground">
                    Stock photos via Pexels aren't set up yet. Ask your developer to add a <code class="font-mono bg-muted px-1 rounded">PEXELS_API_KEY</code> to the server configuration.
                </div>
            </template>

            <template v-else>
                <!-- Search bar -->
                <div class="flex gap-2">
                    <Input
                        v-model="stockQuery"
                        :placeholder="defaultStockQuery"
                        class="h-10 text-base"
                        @keydown.enter.prevent="searchStock()"
                    />
                    <Button type="button" variant="outline" size="default" class="h-10 gap-1.5 shrink-0" :disabled="stockLoading" @click="searchStock()">
                        <Search class="h-4 w-4" />
                        Search
                    </Button>
                </div>

                <!-- Loading -->
                <div v-if="stockLoading" class="grid grid-cols-3 gap-2">
                    <div v-for="n in 9" :key="n" class="animate-pulse rounded-lg bg-muted" style="aspect-ratio: 16/9" />
                </div>

                <!-- Results -->
                <div v-else-if="stockPhotos.length > 0" class="grid grid-cols-3 gap-2">
                    <button
                        v-for="photo in stockPhotos"
                        :key="photo.id"
                        type="button"
                        class="relative overflow-hidden rounded-lg border-2 transition-all focus:outline-none focus-visible:ring-2"
                        :class="bgValue === photo.url ? 'border-primary ring-1 ring-primary' : 'border-transparent hover:border-muted-foreground/40'"
                        style="aspect-ratio: 16/9"
                        @click="selectStockPhoto(photo)"
                    >
                        <img :src="photo.thumb" :alt="photo.alt" class="w-full h-full object-cover" loading="lazy" />
                        <div v-if="bgValue === photo.url" class="absolute inset-0 bg-primary/20 flex items-center justify-center">
                            <div class="bg-primary rounded-full p-1">
                                <Check class="h-3 w-3 text-primary-foreground" />
                            </div>
                        </div>
                    </button>
                </div>

                <p v-else-if="!stockLoading && stockPhotos.length === 0 && stockAvailable !== null" class="text-sm text-muted-foreground italic">
                    No photos found. Try a different search term.
                </p>

                <!-- Pexels attribution (required by terms of service) -->
                <p v-if="stockPhotos.length > 0" class="text-xs text-muted-foreground">
                    Photos provided by
                    <a href="https://www.pexels.com" target="_blank" rel="noopener noreferrer" class="underline hover:text-foreground">Pexels</a>.
                    Free to use.
                </p>
            </template>
        </div>
    </div>
</template>
