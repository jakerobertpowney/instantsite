<script setup lang="ts">
import { ChevronLeft, ChevronRight, X } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref } from 'vue';

const props = defineProps({
    photos: Array as () => string[],
});

// Up to 7 photos — first is featured, rest fill a grid
const allPhotos = computed(() => (props.photos?.slice(0, 7) ?? []) as string[]);
const featured  = computed(() => allPhotos.value[0] ?? null);
const remaining = computed(() => allPhotos.value.slice(1));
const hasPhotos = computed(() => allPhotos.value.length > 0);

// ── Lightbox ──────────────────────────────────────────────────────────────────
const lightboxIndex = ref<number | null>(null);
const lightboxPhoto = computed(() =>
    lightboxIndex.value !== null ? allPhotos.value[lightboxIndex.value] ?? null : null,
);

function openLightbox(index: number) {
    lightboxIndex.value = index;
}

function closeLightbox() {
    lightboxIndex.value = null;
}

function prevPhoto() {
    if (lightboxIndex.value === null) return;
    lightboxIndex.value =
        (lightboxIndex.value - 1 + allPhotos.value.length) % allPhotos.value.length;
}

function nextPhoto() {
    if (lightboxIndex.value === null) return;
    lightboxIndex.value = (lightboxIndex.value + 1) % allPhotos.value.length;
}

function handleKeydown(e: KeyboardEvent) {
    if (lightboxIndex.value === null) return;
    if (e.key === 'Escape')      closeLightbox();
    if (e.key === 'ArrowLeft')   prevPhoto();
    if (e.key === 'ArrowRight')  nextPhoto();
}

onMounted(() => window.addEventListener('keydown', handleKeydown));
onUnmounted(() => window.removeEventListener('keydown', handleKeydown));
</script>

<template>
    <section v-if="hasPhotos" class="pt-0 pb-14" style="border-bottom: 1px solid var(--site-primary-muted)">
        <p
            class="text-xs font-semibold uppercase tracking-widest mb-6"
            style="color: var(--site-primary)"
        >
            Our work
        </p>

        <div class="flex flex-col gap-2">

            <!-- Featured photo — full width, 16:9 -->
            <div
                v-if="featured"
                class="overflow-hidden rounded-2xl cursor-zoom-in"
                @click="openLightbox(0)"
            >
                <img
                    :src="'/' + featured"
                    alt="Featured photo"
                    class="w-full aspect-video object-cover transition-transform duration-300 hover:scale-105"
                />
            </div>

            <!-- Supporting grid — up to 6 smaller squares -->
            <div
                v-if="remaining.length"
                class="grid gap-2"
                :class="remaining.length === 1 ? 'grid-cols-1' : 'grid-cols-3'"
            >
                <div
                    v-for="(photo, index) in remaining"
                    :key="index"
                    class="overflow-hidden rounded-xl cursor-zoom-in"
                    @click="openLightbox(index + 1)"
                >
                    <img
                        :src="'/' + photo"
                        :alt="`Photo ${index + 2}`"
                        class="w-full aspect-square object-cover transition-transform duration-300 hover:scale-105"
                    />
                </div>
            </div>

        </div>
    </section>

    <!-- ── Lightbox ──────────────────────────────────────────────────────────── -->
    <Teleport to="body">
        <div
            v-if="lightboxIndex !== null"
            class="fixed inset-0 z-50 flex items-center justify-center"
            role="dialog"
            aria-modal="true"
            aria-label="Photo lightbox"
        >
            <!-- Backdrop -->
            <div
                class="absolute inset-0 bg-black/90 backdrop-blur-sm"
                @click="closeLightbox"
            />

            <!-- Close button -->
            <button
                type="button"
                class="absolute top-4 right-4 z-10 flex h-10 w-10 items-center justify-center rounded-full bg-white/10 text-white transition-colors hover:bg-white/20 focus:outline-none focus-visible:ring-2 focus-visible:ring-white"
                @click="closeLightbox"
                aria-label="Close photo"
            >
                <X class="h-5 w-5" />
            </button>

            <!-- Photo counter -->
            <div
                v-if="allPhotos.length > 1"
                class="absolute top-4 left-1/2 -translate-x-1/2 z-10 rounded-full bg-black/50 px-3 py-1 text-xs font-medium text-white/80"
            >
                {{ (lightboxIndex ?? 0) + 1 }} / {{ allPhotos.length }}
            </div>

            <!-- Previous arrow -->
            <button
                v-if="allPhotos.length > 1"
                type="button"
                class="absolute left-3 z-10 flex h-11 w-11 items-center justify-center rounded-full bg-white/10 text-white transition-colors hover:bg-white/25 focus:outline-none focus-visible:ring-2 focus-visible:ring-white"
                @click="prevPhoto"
                aria-label="Previous photo"
            >
                <ChevronLeft class="h-6 w-6" />
            </button>

            <!-- Next arrow -->
            <button
                v-if="allPhotos.length > 1"
                type="button"
                class="absolute right-3 z-10 flex h-11 w-11 items-center justify-center rounded-full bg-white/10 text-white transition-colors hover:bg-white/25 focus:outline-none focus-visible:ring-2 focus-visible:ring-white"
                @click="nextPhoto"
                aria-label="Next photo"
            >
                <ChevronRight class="h-6 w-6" />
            </button>

            <!-- Main image -->
            <div class="relative z-10 flex max-h-[90vh] max-w-[90vw] items-center justify-center">
                <img
                    v-if="lightboxPhoto"
                    :src="'/' + lightboxPhoto"
                    :alt="`Photo ${(lightboxIndex ?? 0) + 1}`"
                    class="max-h-[90vh] max-w-[90vw] rounded-xl object-contain shadow-2xl"
                    @click.stop
                />
            </div>

            <!-- Dot strip (thumbnail row) -->
            <div
                v-if="allPhotos.length > 1"
                class="absolute bottom-4 left-1/2 -translate-x-1/2 z-10 flex gap-1.5"
            >
                <button
                    v-for="(_, i) in allPhotos"
                    :key="i"
                    type="button"
                    class="h-2 rounded-full transition-all focus:outline-none focus-visible:ring-2 focus-visible:ring-white"
                    :class="i === lightboxIndex ? 'w-5 bg-white' : 'w-2 bg-white/40'"
                    @click="lightboxIndex = i"
                    :aria-label="`Go to photo ${i + 1}`"
                />
            </div>
        </div>
    </Teleport>
</template>
