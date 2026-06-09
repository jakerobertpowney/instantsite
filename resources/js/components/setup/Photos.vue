<script setup lang="ts">
import { inject, ref, computed, onUnmounted } from 'vue';
import { Upload, X, ImageIcon, Loader2 } from 'lucide-vue-next';
import axios from 'axios';

const form     = inject<any>('form');
const siteData = inject<any>('siteData', null);
const siteId   = inject<string | null>('siteId', null);

// ── Local upload state ────────────────────────────────────────────────────────

interface Preview {
    id: string;
    file: File;
    url: string;
}

const previews  = ref<Preview[]>([]);
const dragging  = ref(false);
const fileInput = ref<HTMLInputElement | null>(null);

// Pre-populate from already-saved images (edit flow).
// removedExisting tracks paths the user has deleted so we can tell the backend.
const removedExisting = ref<Set<string>>(new Set());
const existingImages = computed<string[]>(() =>
    (siteData?.images ?? []).filter((p: string) => !removedExisting.value.has(p))
);

const removeExisting = (path: string) => {
    removedExisting.value = new Set([...removedExisting.value, path]);
    form.remove_photos = [...removedExisting.value];
};

const addFiles = (files: FileList | File[]) => {
    for (const file of Array.from(files)) {
        if (!file.type.startsWith('image/')) continue;
        previews.value.push({
            id:   Math.random().toString(36).slice(2),
            file,
            url:  URL.createObjectURL(file),
        });
    }
    form.photos = previews.value.map((p) => p.file);
};

const removeNew = (id: string) => {
    const item = previews.value.find((p) => p.id === id);
    if (item) URL.revokeObjectURL(item.url);
    previews.value = previews.value.filter((p) => p.id !== id);
    form.photos = previews.value.map((p) => p.file);
};

// Revoke blob URLs when unmounting to avoid memory leaks
onUnmounted(() => previews.value.forEach((p) => URL.revokeObjectURL(p.url)));

// ── Drag-and-drop ─────────────────────────────────────────────────────────────

const onDragover  = (e: DragEvent) => { e.preventDefault(); dragging.value = true; };
const onDragleave = ()              => { dragging.value = false; };
const onDrop      = (e: DragEvent) => {
    e.preventDefault();
    dragging.value = false;
    if (e.dataTransfer?.files) addFiles(e.dataTransfer.files);
};

const triggerUpload  = () => fileInput.value?.click();
const handleFileInput = (e: Event) => {
    const files = (e.target as HTMLInputElement).files;
    if (files) addFiles(files);
    // Reset input so re-selecting same files triggers change again
    (e.target as HTMLInputElement).value = '';
};

// ── Google photo inspiration panel ────────────────────────────────────────────

const googlePhotos        = ref<{ url: string }[]>([]);
const loadingGooglePhotos = ref(false);
const googlePhotosLoaded  = ref(false);
const googlePhotosError   = ref(false);
const showGooglePanel     = ref(false);

const canShowGooglePhotos = computed(() => !!siteId && !!siteData?.places_id && !siteData.places_id.startsWith('manual-'));

const loadGooglePhotos = async () => {
    if (!canShowGooglePhotos.value || googlePhotosLoaded.value) return;
    showGooglePanel.value    = true;
    loadingGooglePhotos.value = true;
    googlePhotosError.value  = false;
    try {
        const res = await axios.get(`/api/place/${siteData.places_id}/photos`);
        googlePhotos.value = res.data.photos ?? [];
    } catch {
        googlePhotosError.value = true;
    } finally {
        loadingGooglePhotos.value = false;
        googlePhotosLoaded.value  = true;
    }
};

const totalCount = computed(() => existingImages.value.length + previews.value.length);
</script>

<template>
    <div class="flex flex-col gap-5">

        <!-- ── Already-saved photos (edit flow) ─────────────────────────── -->
        <div v-if="existingImages.length" class="flex flex-col gap-2">
            <p class="text-sm font-medium">Your current photos</p>
            <div class="grid grid-cols-3 gap-2">
                <div
                    v-for="(src, i) in existingImages"
                    :key="i"
                    class="relative aspect-square rounded-lg overflow-hidden bg-muted group"
                >
                    <img :src="src.startsWith('/') ? src : '/' + src" class="w-full h-full object-cover" alt="" />
                    <button
                        type="button"
                        class="absolute top-1 right-1 w-6 h-6 rounded-full bg-black/60 text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity"
                        @click.stop="removeExisting(src)"
                        aria-label="Remove photo"
                    >
                        <X class="h-3.5 w-3.5" />
                    </button>
                </div>
            </div>
            <p class="text-xs text-muted-foreground">Upload more below — they'll be added to the ones above.</p>
        </div>

        <!-- ── Drop / upload zone ─────────────────────────────────────────── -->
        <div
            class="rounded-xl border-2 border-dashed transition-colors cursor-pointer"
            :class="dragging ? 'border-primary bg-primary/5' : 'border-border hover:border-primary/50'"
            @dragover="onDragover"
            @dragleave="onDragleave"
            @drop="onDrop"
            @click="triggerUpload"
        >
            <div class="flex flex-col items-center justify-center gap-3 py-10 px-4 text-center pointer-events-none">
                <div class="w-12 h-12 rounded-full bg-muted flex items-center justify-center">
                    <Upload class="h-5 w-5 text-muted-foreground" />
                </div>
                <div>
                    <p class="font-medium text-sm">
                        {{ dragging ? 'Drop photos here' : 'Tap to add photos from your camera roll' }}
                    </p>
                    <p class="text-xs text-muted-foreground mt-0.5">
                        JPG, PNG or WebP · up to 10 MB each · up to 20 photos
                    </p>
                </div>
            </div>
        </div>

        <input
            ref="fileInput"
            type="file"
            name="photos[]"
            multiple
            accept="image/jpeg,image/jpg,image/png,image/webp,image/heic"
            class="hidden"
            @change="handleFileInput"
        />

        <!-- ── New upload previews ─────────────────────────────────────────── -->
        <div v-if="previews.length" class="grid grid-cols-3 gap-2">
            <div
                v-for="p in previews"
                :key="p.id"
                class="relative aspect-square rounded-lg overflow-hidden bg-muted group"
            >
                <img :src="p.url" class="w-full h-full object-cover" alt="" />
                <button
                    type="button"
                    class="absolute top-1 right-1 w-6 h-6 rounded-full bg-black/60 text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity"
                    @click.stop="removeNew(p.id)"
                    aria-label="Remove photo"
                >
                    <X class="h-3.5 w-3.5" />
                </button>
            </div>
        </div>

        <p v-if="totalCount > 0" class="text-xs text-muted-foreground text-center">
            {{ totalCount }} photo{{ totalCount === 1 ? '' : 's' }} selected
        </p>

        <p class="text-sm text-muted-foreground text-center">
            Don't have photos yet? Tap <strong>Skip for now</strong> — you can always add them later from your dashboard.
        </p>

        <!-- ── Google photo inspiration panel ─────────────────────────────── -->
        <div v-if="canShowGooglePhotos" class="border-t pt-4 flex flex-col gap-3">
            <button
                v-if="!showGooglePanel"
                type="button"
                class="text-sm text-primary font-medium text-left hover:underline flex items-center gap-1.5"
                @click="loadGooglePhotos"
            >
                <ImageIcon class="h-4 w-4" />
                See what's currently on your Google listing →
            </button>

            <template v-else>
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium flex items-center gap-1.5">
                        <ImageIcon class="h-4 w-4 text-muted-foreground" />
                        Your Google listing photos
                    </p>
                    <span class="text-xs text-muted-foreground">For reference only — not published</span>
                </div>

                <div v-if="loadingGooglePhotos" class="flex justify-center py-6">
                    <Loader2 class="h-5 w-5 animate-spin text-muted-foreground" />
                </div>

                <div v-else-if="googlePhotosError" class="text-sm text-muted-foreground text-center py-4">
                    Couldn't load Google photos. They may still be available on your listing.
                </div>

                <div v-else-if="googlePhotos.length" class="grid grid-cols-3 gap-2">
                    <img
                        v-for="(p, i) in googlePhotos"
                        :key="i"
                        :src="p.url"
                        class="aspect-square w-full object-cover rounded-lg bg-muted"
                        alt=""
                        loading="lazy"
                    />
                </div>

                <div v-else class="text-sm text-muted-foreground text-center py-4">
                    No photos found on your Google listing.
                </div>

                <p class="text-xs text-muted-foreground">
                    Photos sourced from Google Business Profile. These are shown for reference and are not published to your 321Sites website.
                </p>
            </template>
        </div>

    </div>
</template>
