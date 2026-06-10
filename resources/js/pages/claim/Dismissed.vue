<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="No problem — 321Sites" />

    <div class="min-h-screen bg-gray-50 flex items-center justify-center px-4">
        <div class="max-w-md w-full text-center">
            <div class="w-14 h-14 rounded-full bg-green-100 flex items-center justify-center mx-auto mb-5">
                <svg class="w-7 h-7 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </div>

            <h1 class="text-2xl font-bold text-gray-900 mb-2">No problem at all</h1>
            <p class="text-gray-500 mb-8">
                We've removed your details and won't contact you again. If you change your mind,
                visit <a href="https://321sites.com" class="text-indigo-600 underline underline-offset-4 hover:text-indigo-700">321sites.com</a> any time to get started.
            </p>

            <a
                href="https://321sites.com"
                class="inline-flex items-center gap-2 bg-indigo-600 text-white text-sm font-medium px-5 py-2.5 rounded-lg hover:bg-indigo-700 transition-colors"
            >
                Visit 321Sites
            </a>
        </div>
    </div>
</template>
