<script setup lang="ts">
import { computed } from 'vue';
import Review from '@/components/site/Reviews/Review.vue';

const props = defineProps({
    reviews: {
        type: Array,
        default: () => [],
    },
    rating: {
        type: Number,
        default: 0,
    },
    reviewCount: {
        type: Number,
        default: 0,
    },
    googlePlacesId: {
        type: String,
        default: '',
    },
});

const hasAggregate    = computed(() => props.rating > 0 || props.reviewCount > 0);
const hasTestimonials = computed(() => (props.reviews as unknown[]).length > 0);

// Links to the Google Maps listing so visitors can read all reviews there
const googleUrl = computed(() =>
    props.googlePlacesId
        ? `https://www.google.com/maps/search/?api=1&query_place_id=${props.googlePlacesId}`
        : null
);

const ratingDisplay = computed(() =>
    props.rating ? Number(props.rating).toFixed(1) : null
);
</script>

<template>
    <section>
        <p
            class="text-xs font-semibold uppercase tracking-widest mb-4"
            style="color: var(--site-primary)"
        >
            Reviews
        </p>

        <!-- Aggregate rating -->
        <div v-if="hasAggregate" class="flex flex-col sm:flex-row sm:items-center gap-4 mb-8">
            <div class="flex items-center gap-3">
                <div v-if="rating" class="flex items-center gap-0.5">
                    <svg
                        v-for="n in 5"
                        :key="n"
                        class="h-6 w-6"
                        :style="n <= Math.round(rating) ? 'color: #f59e0b' : 'color: #d1d5db'"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="currentColor"
                        viewBox="0 0 24 24"
                        aria-hidden="true"
                    >
                        <path d="M13.849 4.22c-.684-1.626-3.014-1.626-3.698 0L8.397 8.387l-4.552.361c-1.775.14-2.495 2.331-1.142 3.477l3.468 2.937-1.06 4.392c-.413 1.713 1.472 3.067 2.992 2.149L12 19.35l3.897 2.354c1.52.918 3.405-.436 2.992-2.15l-1.06-4.39 3.468-2.938c1.353-1.146.633-3.336-1.142-3.477l-4.552-.36-1.754-4.17Z" />
                    </svg>
                </div>
                <div>
                    <span v-if="ratingDisplay" class="text-3xl font-bold text-gray-900">{{ ratingDisplay }}</span>
                    <span v-if="reviewCount" class="ml-1.5 text-sm text-gray-500">
                        {{ reviewCount.toLocaleString() }} review{{ reviewCount === 1 ? '' : 's' }}
                    </span>
                </div>
            </div>

            <a
                v-if="googleUrl"
                :href="googleUrl"
                target="_blank"
                rel="noopener noreferrer"
                class="inline-flex items-center gap-1.5 text-sm font-medium underline underline-offset-2 sm:ml-auto shrink-0"
                style="color: var(--site-primary)"
            >
                <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                    <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05"/>
                    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                </svg>
                See all reviews on Google
            </a>
        </div>

        <!-- User-added testimonials -->
        <template v-if="hasTestimonials">
            <h2 class="text-xl font-bold text-gray-900 sm:text-2xl mb-6">What our customers say</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <Review
                    v-for="(review, i) in reviews"
                    :key="i"
                    :review="review"
                />
            </div>
        </template>

        <!-- Aggregate only, no testimonials — link out to Google -->
        <p
            v-else-if="hasAggregate && googleUrl"
            class="text-sm text-gray-500"
        >
            Read what customers say on
            <a :href="googleUrl" target="_blank" rel="noopener noreferrer"
               class="underline underline-offset-2" style="color: var(--site-primary)">Google</a>.
        </p>
    </section>
</template>
