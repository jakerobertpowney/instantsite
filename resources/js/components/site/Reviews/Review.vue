<script setup lang="ts">
import { computed } from 'vue';
import moment from 'moment';

const props = defineProps({
    review: Object
});

const publishTime = computed(() =>
    props.review?.publishTime
        ? moment(props.review.publishTime).fromNow()
        : ''
);

// Generate a two-letter initial from the reviewer's display name
const initials = computed(() => {
    const name: string = props.review?.authorAttribution?.displayName ?? '?';
    const parts = name.trim().split(' ').filter(Boolean);
    if (parts.length >= 2) return (parts[0][0] + parts[1][0]).toUpperCase();
    return (parts[0]?.[0] ?? '?').toUpperCase();
});

// Deterministic background colour from name (keeps avatars visually varied)
const AVATAR_COLOURS = [
    '#6366f1', '#8b5cf6', '#ec4899', '#f43f5e',
    '#f97316', '#eab308', '#22c55e', '#14b8a6',
    '#3b82f6', '#06b6d4',
];
const avatarBg = computed(() => {
    const name: string = props.review?.authorAttribution?.displayName ?? '';
    const hash = name.split('').reduce((acc, c) => acc + c.charCodeAt(0), 0);
    return AVATAR_COLOURS[hash % AVATAR_COLOURS.length];
});
</script>

<template>
    <div
        v-if="review"
        class="flex flex-col gap-3 rounded-2xl border border-gray-100 bg-white p-5 shadow-sm"
    >
        <!-- Reviewer row -->
        <div class="flex items-center gap-3">
            <!-- Avatar with initials -->
            <div
                class="h-9 w-9 rounded-full flex items-center justify-center text-white text-xs font-bold shrink-0 select-none"
                :style="{ backgroundColor: avatarBg }"
                aria-hidden="true"
            >
                {{ initials }}
            </div>

            <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold text-gray-900 truncate">
                    {{ review.authorAttribution?.displayName }}
                </p>
                <p class="text-xs text-gray-400">{{ publishTime }}</p>
            </div>

            <!-- Star rating -->
            <div class="flex items-center gap-0.5 shrink-0">
                <svg
                    v-for="n in 5"
                    :key="n"
                    class="h-3.5 w-3.5"
                    :style="n <= review.rating ? 'color: #f59e0b' : 'color: #e5e7eb'"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="currentColor"
                    viewBox="0 0 24 24"
                    aria-hidden="true"
                >
                    <path d="M13.849 4.22c-.684-1.626-3.014-1.626-3.698 0L8.397 8.387l-4.552.361c-1.775.14-2.495 2.331-1.142 3.477l3.468 2.937-1.06 4.392c-.413 1.713 1.472 3.067 2.992 2.149L12 19.35l3.897 2.354c1.52.918 3.405-.436 2.992-2.15l-1.06-4.39 3.468-2.938c1.353-1.146.633-3.336-1.142-3.477l-4.552-.36-1.754-4.17Z" />
                </svg>
            </div>
        </div>

        <!-- Review text -->
        <p class="text-sm text-gray-600 leading-relaxed line-clamp-5">
            {{ review.text?.text }}
        </p>
    </div>
</template>
