<script setup lang="ts">
import { inject } from 'vue';
import { Check } from 'lucide-vue-next';

const form = inject<any>('form');

const freeFeatures = [
    'Your own one-page website',
    'Free address: yourname.321sites.com',
    'Photo gallery & reviews',
    'Call, message & WhatsApp buttons',
    'Works on any device',
];

const premiumFeatures = [
    'Everything in Free',
    'Your own web address (e.g. thompsondec.co.uk)',
    'Remove the "Powered by 321Sites" badge',
    'On-page contact form',
    'Priority support',
];
</script>

<template>
    <div class="flex flex-col gap-4">

        <!-- Free -->
        <button
            type="button"
            class="w-full rounded-xl border-2 p-5 text-left transition-all"
            :class="!form.premium
                ? 'border-primary bg-primary/5 ring-2 ring-primary/20'
                : 'border-border bg-white hover:border-gray-300'"
            @click="form.premium = false"
        >
            <div class="flex items-start justify-between gap-3 mb-3">
                <div>
                    <p class="font-bold text-base">Free</p>
                    <p class="text-2xl font-black mt-0.5">£0 <span class="text-sm font-normal text-muted-foreground">forever</span></p>
                </div>
                <div
                    class="mt-1 h-5 w-5 rounded-full border-2 flex items-center justify-center shrink-0 transition-colors"
                    :class="!form.premium ? 'border-primary bg-primary' : 'border-gray-300'"
                >
                    <Check v-if="!form.premium" class="h-3 w-3 text-white" stroke-width="3" />
                </div>
            </div>
            <ul class="flex flex-col gap-1.5">
                <li v-for="f in freeFeatures" :key="f" class="flex items-start gap-2 text-sm text-gray-600">
                    <Check class="h-4 w-4 text-green-500 shrink-0 mt-0.5" />
                    {{ f }}
                </li>
            </ul>
        </button>

        <!-- Premium -->
        <button
            type="button"
            class="w-full rounded-xl border-2 p-5 text-left transition-all"
            :class="form.premium
                ? 'border-primary bg-primary/5 ring-2 ring-primary/20'
                : 'border-border bg-white hover:border-gray-300'"
            @click="form.premium = true"
        >
            <div class="flex items-start justify-between gap-3 mb-3">
                <div>
                    <div class="flex items-center gap-2 mb-0.5">
                        <p class="font-bold text-base">Premium</p>
                        <span class="text-[10px] font-bold uppercase tracking-wide px-1.5 py-0.5 rounded bg-amber-100 text-amber-700">Best value</span>
                    </div>
                    <p class="text-2xl font-black">£9 <span class="text-sm font-normal text-muted-foreground">/ month</span></p>
                </div>
                <div
                    class="mt-1 h-5 w-5 rounded-full border-2 flex items-center justify-center shrink-0 transition-colors"
                    :class="form.premium ? 'border-primary bg-primary' : 'border-gray-300'"
                >
                    <Check v-if="form.premium" class="h-3 w-3 text-white" stroke-width="3" />
                </div>
            </div>
            <ul class="flex flex-col gap-1.5">
                <li v-for="f in premiumFeatures" :key="f" class="flex items-start gap-2 text-sm text-gray-600">
                    <Check class="h-4 w-4 text-green-500 shrink-0 mt-0.5" />
                    {{ f }}
                </li>
            </ul>
        </button>

        <p class="text-xs text-muted-foreground text-center">
            You won't be charged until after you create your account. No commitment — cancel any time.
        </p>

    </div>
</template>
