<script setup lang="ts">
import { inject, computed } from 'vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { Plus, Trash2 } from 'lucide-vue-next';
const form     = inject<any>('form');
const siteData = inject<any>('siteData', null);

// Was pre-filled from Google during discover — user confirms/corrects these
const googleRating      = computed(() => siteData?.rating ?? null);
const googleReviewCount = computed(() => siteData?.review_count ?? null);

const setRating = (n: number) => { form.rating = n; };

const addTestimonial = () => {
    form.reviews = [
        ...(form.reviews ?? []),
        { id: Math.random().toString(36).slice(2), author: '', text: '', rating: 5, date: '' },
    ];
};

const removeTestimonial = (id: string) => {
    form.reviews = (form.reviews ?? []).filter((r: any) => r.id !== id);
};
</script>

<template>
    <div class="flex flex-col gap-6">

        <!-- ── Star rating ─────────────────────────────────────────────────── -->
        <div class="flex flex-col gap-2">
            <Label class="text-sm font-medium">Your Google star rating</Label>

            <p v-if="googleRating" class="text-xs text-muted-foreground">
                Pre-filled from your Google listing — adjust if it's changed.
            </p>

            <!-- Interactive star picker -->
            <div class="flex items-center gap-1">
                <button
                    v-for="n in 5"
                    :key="n"
                    type="button"
                    class="p-0.5 transition-transform hover:scale-110"
                    @click="setRating(n)"
                    :aria-label="`${n} star${n === 1 ? '' : 's'}`"
                >
                    <svg
                        class="h-8 w-8"
                        :style="n <= (form.rating ?? 0) ? 'color: #f59e0b' : 'color: #d1d5db'"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path d="M13.849 4.22c-.684-1.626-3.014-1.626-3.698 0L8.397 8.387l-4.552.361c-1.775.14-2.495 2.331-1.142 3.477l3.468 2.937-1.06 4.392c-.413 1.713 1.472 3.067 2.992 2.149L12 19.35l3.897 2.354c1.52.918 3.405-.436 2.992-2.15l-1.06-4.39 3.468-2.938c1.353-1.146.633-3.336-1.142-3.477l-4.552-.36-1.754-4.17Z" />
                    </svg>
                </button>
                <span v-if="form.rating" class="ml-2 text-sm font-semibold text-gray-700">
                    {{ Number(form.rating).toFixed(1) }}
                </span>
                <button
                    v-if="form.rating"
                    type="button"
                    class="ml-2 text-xs text-muted-foreground hover:text-foreground underline"
                    @click="form.rating = null"
                >
                    Clear
                </button>
            </div>
        </div>

        <!-- ── Review count ────────────────────────────────────────────────── -->
        <div class="flex flex-col gap-2">
            <Label for="review_count" class="text-sm font-medium">Number of Google reviews</Label>
            <p v-if="googleReviewCount" class="text-xs text-muted-foreground">
                Pre-filled from your Google listing — adjust if it's changed.
            </p>
            <Input
                id="review_count"
                type="number"
                min="0"
                inputmode="numeric"
                placeholder="e.g. 47"
                v-model.number="form.review_count"
                class="h-12 text-base max-w-40"
            />
            <p class="text-xs text-muted-foreground">
                Shown as "X reviews" next to your star rating on your website, with a link to your Google listing.
            </p>
        </div>

        <!-- ── Testimonials ────────────────────────────────────────────────── -->
        <div class="flex flex-col gap-3">
            <div>
                <p class="text-sm font-medium">Customer testimonials</p>
                <p class="text-xs text-muted-foreground mt-0.5">
                    Add quotes from happy customers. These appear as review cards on your website — separate from your Google rating.
                </p>
            </div>

            <div v-for="(review, i) in form.reviews" :key="review.id" class="rounded-lg border p-4 flex flex-col gap-3">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-medium text-muted-foreground uppercase tracking-wide">Testimonial {{ i + 1 }}</span>
                    <button
                        type="button"
                        class="text-muted-foreground hover:text-destructive transition-colors"
                        @click="removeTestimonial(review.id)"
                        aria-label="Remove testimonial"
                    >
                        <Trash2 class="h-4 w-4" />
                    </button>
                </div>

                <!-- Star rating for this testimonial -->
                <div class="flex items-center gap-0.5">
                    <button
                        v-for="n in 5"
                        :key="n"
                        type="button"
                        class="transition-transform hover:scale-110"
                        @click="form.reviews[i].rating = n"
                    >
                        <svg
                            class="h-5 w-5"
                            :style="n <= review.rating ? 'color: #f59e0b' : 'color: #d1d5db'"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path d="M13.849 4.22c-.684-1.626-3.014-1.626-3.698 0L8.397 8.387l-4.552.361c-1.775.14-2.495 2.331-1.142 3.477l3.468 2.937-1.06 4.392c-.413 1.713 1.472 3.067 2.992 2.149L12 19.35l3.897 2.354c1.52.918 3.405-.436 2.992-2.15l-1.06-4.39 3.468-2.938c1.353-1.146.633-3.336-1.142-3.477l-4.552-.36-1.754-4.17Z" />
                        </svg>
                    </button>
                </div>

                <Input
                    :id="`testimonial-author-${i}`"
                    v-model="form.reviews[i].author"
                    placeholder="Customer name"
                    class="h-11 text-base"
                />

                <Textarea
                    :id="`testimonial-text-${i}`"
                    v-model="form.reviews[i].text"
                    placeholder="What did they say?"
                    class="min-h-20 resize-none text-base"
                />

                <Input
                    :id="`testimonial-date-${i}`"
                    type="month"
                    v-model="form.reviews[i].date"
                    class="h-11 text-base max-w-48"
                />
            </div>

            <Button
                type="button"
                variant="outline"
                class="gap-2 h-11"
                @click="addTestimonial"
            >
                <Plus class="h-4 w-4" />
                Add a testimonial
            </Button>
        </div>

    </div>
</template>
