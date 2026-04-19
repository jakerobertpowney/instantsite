<script setup lang="ts">
import { computed } from 'vue';
import { Phone } from 'lucide-vue-next';
import { formatPrice } from '@/lib/currencies';

interface Service {
    id: string;
    name: string;
    description: string | null;
    price: string | null;
    currency: string;
    show_price: boolean;
    featured: boolean;
}

const props = defineProps({
    services:       { type: Array as () => Service[], default: () => [] },
    heading:        { type: String,  default: 'Our Services' },
    ctaLabel:       { type: String,  default: '' },
    ctaLink:        { type: String,  default: '' },
    phoneNumber:    { type: String,  default: '' },
});

const featuredServices = computed(() =>
    props.services.filter(s => s.featured)
);

const standardServices = computed(() =>
    props.services.filter(s => !s.featured)
);

const hasServices = computed(() => props.services.length > 0);

const showCta = computed(() =>
    (props.ctaLabel && props.ctaLink) || props.phoneNumber
);
</script>

<template>
    <section v-if="hasServices" class="pt-6 pb-14">

        <!-- Section heading -->
        <p
            class="text-xs font-semibold uppercase tracking-widest mb-6"
            style="color: var(--site-primary)"
        >
            {{ heading }}
        </p>

        <!-- Featured services (if any) — larger cards displayed first -->
        <div
            v-if="featuredServices.length > 0"
            class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6"
        >
            <div
                v-for="service in featuredServices"
                :key="service.id"
                class="rounded-2xl border-2 p-5 flex flex-col gap-2 relative overflow-hidden"
                style="border-color: var(--site-primary)"
            >
                <!-- Featured accent line -->
                <div
                    class="absolute top-0 left-0 right-0 h-1 rounded-t-2xl"
                    style="background-color: var(--site-primary)"
                />

                <div class="flex items-start justify-between gap-3 mt-1">
                    <h3 class="font-semibold text-gray-900 text-base leading-snug">
                        {{ service.name }}
                    </h3>
                    <span
                        v-if="service.price && service.show_price"
                        class="text-base font-bold shrink-0 tabular-nums"
                        style="color: var(--site-primary)"
                    >
                        {{ formatPrice(service.price, service.currency) }}
                    </span>
                </div>

                <p
                    v-if="service.description"
                    class="text-sm text-gray-500 leading-relaxed"
                >
                    {{ service.description }}
                </p>
            </div>
        </div>

        <!-- Standard services — compact list rows -->
        <div
            v-if="standardServices.length > 0"
            class="divide-y divide-gray-100"
        >
            <div
                v-for="service in standardServices"
                :key="service.id"
                class="flex items-start gap-4 py-4"
            >
                <div class="flex-1 min-w-0">
                    <p class="font-medium text-gray-900 text-sm">{{ service.name }}</p>
                    <p
                        v-if="service.description"
                        class="text-xs text-gray-500 mt-0.5 leading-relaxed"
                    >
                        {{ service.description }}
                    </p>
                </div>
                <span
                    v-if="service.price && service.show_price"
                    class="text-sm font-semibold shrink-0 tabular-nums"
                    style="color: var(--site-primary)"
                >
                    {{ formatPrice(service.price, service.currency) }}
                </span>
                <span
                    v-else-if="!service.price || !service.show_price"
                    class="text-xs text-gray-400 shrink-0 italic self-center"
                >
                    Price on request
                </span>
            </div>
        </div>

        <!-- CTA strip -->
        <div v-if="showCta" class="mt-8 flex flex-wrap gap-3 items-center">
            <a
                v-if="ctaLabel && ctaLink"
                :href="ctaLink"
                target="_blank"
                rel="noopener noreferrer"
                class="inline-flex items-center justify-center gap-2 rounded-full px-6 py-3 text-sm font-semibold shadow-sm transition-opacity hover:opacity-90"
                style="background-color: var(--site-primary); color: var(--site-primary-fg)"
            >
                {{ ctaLabel }}
            </a>
            <a
                v-else-if="phoneNumber"
                :href="'tel:' + phoneNumber"
                class="inline-flex items-center justify-center gap-2 rounded-full px-6 py-3 text-sm font-semibold shadow-sm transition-opacity hover:opacity-90"
                style="background-color: var(--site-primary); color: var(--site-primary-fg)"
            >
                <Phone class="h-4 w-4" />
                Call for a quote
            </a>
        </div>

    </section>
</template>
