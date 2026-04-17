<script setup lang="ts">
import { computed } from 'vue';
import Review from '@/components/site/Reviews/Review.vue';

const props = defineProps({
    reviews: {
        type: Array,
        default: () => []
    },
    rating: {
        type: Number,
        default: 0
    },
    reviewCount: {
        type: Number,
        default: 0
    },
    hiddenReviews: {
        type: Array as () => number[],
        default: () => []
    },
});

// Filter out reviews whose index is in the hiddenReviews list
const visibleReviews = computed(() =>
    (props.reviews as unknown[]).filter((_, i) => !props.hiddenReviews.includes(i)),
);
</script>

<template>
    <section>
        <!-- Section header -->
        <div class="flex flex-col sm:flex-row sm:items-end gap-3 mb-8">
            <div>
                <p
                    class="text-xs font-semibold uppercase tracking-widest mb-2"
                    style="color: var(--site-primary)"
                >
                    Reviews
                </p>
                <h2 class="text-xl font-bold text-gray-900 sm:text-2xl">What our customers say</h2>
            </div>

            <!-- Rating summary badge -->
            <div
                v-if="rating"
                class="sm:ml-auto flex items-center gap-2 rounded-2xl px-4 py-2 shrink-0"
                style="background-color: var(--site-primary-muted)"
            >
                <!-- Stars -->
                <div class="flex items-center gap-0.5">
                    <svg
                        v-for="n in 5"
                        :key="n"
                        class="h-4 w-4"
                        :style="n <= Math.round(rating) ? 'color: #f59e0b' : 'color: #d1d5db'"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="currentColor"
                        viewBox="0 0 24 24"
                        aria-hidden="true"
                    >
                        <path d="M13.849 4.22c-.684-1.626-3.014-1.626-3.698 0L8.397 8.387l-4.552.361c-1.775.14-2.495 2.331-1.142 3.477l3.468 2.937-1.06 4.392c-.413 1.713 1.472 3.067 2.992 2.149L12 19.35l3.897 2.354c1.52.918 3.405-.436 2.992-2.15l-1.06-4.39 3.468-2.938c1.353-1.146.633-3.336-1.142-3.477l-4.552-.36-1.754-4.17Z" />
                    </svg>
                </div>
                <span class="text-sm font-bold text-gray-800">{{ rating.toFixed(1) }}</span>
                <span v-if="reviewCount" class="text-xs text-gray-500">({{ reviewCount.toLocaleString() }})</span>
            </div>
        </div>

        <!-- Review cards — 2-col on desktop, stack on mobile -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <Review
                v-for="(review, i) in visibleReviews"
                :key="i"
                :review="review"
            />
        </div>
    </section>
</template>
