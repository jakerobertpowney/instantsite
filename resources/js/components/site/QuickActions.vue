<script setup lang="ts">
import { Mail, MessageCircle, Phone } from 'lucide-vue-next';
import { computed } from 'vue';
import { detectPlatformBrand, getFaviconUrl } from '@/lib/platformBrands';

const props = defineProps({
    phoneNumber: String,
    whatsappNumber: String,
    quickLinks: Array as () => Array<{ label: string; link: string }>,
    contact: String,
    preview: Boolean,
});

const whatsappUrl = computed(() =>
    props.whatsappNumber ? `https://wa.me/${props.whatsappNumber}` : null,
);

function quickLinkStyle(url: string): Record<string, string> {
    const brand = detectPlatformBrand(url);
    return brand
        ? { backgroundColor: brand.bgColor, color: brand.textColor }
        : { backgroundColor: 'var(--site-primary)', color: 'var(--site-primary-fg)' };
}

function quickLinkFavicon(url: string): string | null {
    return getFaviconUrl(url);
}

function scrollToContactForm() {
    const el = document.getElementById('contact-form');
    if (el) {
        el.scrollIntoView({ behavior: 'smooth' });
    }
}
</script>

<template>
    <section class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center sm:gap-2.5">

        <!-- Call — full-width on mobile (most important action) -->
        <a
            v-if="phoneNumber"
            :href="'tel:' + phoneNumber"
            class="w-full inline-flex items-center justify-center gap-2 rounded-full px-6 py-3.5 text-base font-semibold shadow-sm transition-opacity hover:opacity-90 focus:outline-none focus-visible:ring-2 sm:w-auto sm:py-2.5 sm:text-sm"
            style="background-color: var(--site-primary); color: var(--site-primary-fg)"
        >
            <Phone class="h-5 w-5 sm:h-4 sm:w-4" />
            Call us
        </a>

        <!-- Secondary actions row on mobile -->
        <div class="flex gap-2 sm:contents">

            <!-- Send message — scrolls to inline contact form -->
            <button
                v-if="contact"
                type="button"
                class="flex-1 inline-flex items-center justify-center gap-2 rounded-full border-2 px-5 py-3 text-sm font-semibold transition-colors hover:bg-gray-50 focus:outline-none focus-visible:ring-2 sm:flex-none sm:py-2"
                style="border-color: var(--site-primary); color: var(--site-primary)"
                @click="scrollToContactForm"
            >
                <Mail class="h-4 w-4" />
                Message
            </button>

            <!-- WhatsApp -->
            <a
                v-if="whatsappUrl"
                :href="whatsappUrl"
                target="_blank"
                rel="noopener noreferrer"
                class="flex-1 inline-flex items-center justify-center gap-2 rounded-full px-5 py-3 text-sm font-semibold shadow-sm transition-opacity hover:opacity-90 sm:flex-none sm:py-2.5"
                style="background-color: #25D366; color: #fff"
            >
                <MessageCircle class="h-4 w-4" />
                WhatsApp
            </a>

            <!-- Quick links -->
            <a
                v-for="(link, index) in quickLinks"
                :key="index"
                :href="link.link"
                target="_blank"
                rel="noopener noreferrer"
                :style="quickLinkStyle(link.link)"
                class="flex-1 inline-flex items-center justify-center gap-2 rounded-full px-5 py-3 text-sm font-semibold shadow-sm transition-opacity hover:opacity-90 sm:flex-none sm:py-2.5"
            >
                {{ link.label }}
                <img
                    v-if="quickLinkFavicon(link.link)"
                    :src="quickLinkFavicon(link.link)!"
                    class="h-4 w-4 rounded-sm object-contain"
                    :alt="link.label"
                    aria-hidden="true"
                />
            </a>

        </div>

    </section>
</template>
