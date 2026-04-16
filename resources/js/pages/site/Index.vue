<script setup lang="ts">
import Gallery from '@/components/site/Gallery.vue';
import Contact from '@/components/site/Contact.vue';
import QuickActions from '@/components/site/QuickActions.vue';
import Description from '@/components/site/Description.vue';
import Header from '@/components/site/Header.vue';
import Reviews from '@/components/site/Reviews.vue';
import { Head } from '@inertiajs/vue3';
import { computed, onMounted } from 'vue';

const props = defineProps({
    data: Object
})

// Resolve component visibility flags — default to true when absent (legacy sites)
const components = computed(() => {
    const flags = props.data?.components ?? {};
    return {
        header:       flags.header?.enabled       !== false,
        description:  flags.description?.enabled  !== false,
        gallery:      flags.gallery?.enabled      !== false,
        quick_actions: flags.quick_actions?.enabled !== false,
        reviews:      flags.reviews?.enabled      !== false,
        contact:      flags.contact?.enabled      !== false,
    };
});

// Use overrides when present, fall back to Google Places data
const description = computed(() => props.data?.overrides?.description || props.data?.description);
const logo = computed(() => props.data?.overrides?.logo_path || props.data?.logo);

// SEO / analytics settings
const googleAnalyticsId = computed(() => props.data?.google_analytics_id || '');
const allowIndexing = computed(() => props.data?.allow_indexing !== false);

onMounted(() => {
    if (googleAnalyticsId.value) {
        const script = document.createElement('script');
        script.async = true;
        script.src = `https://www.googletagmanager.com/gtag/js?id=${googleAnalyticsId.value}`;
        document.head.appendChild(script);

        (window as any).dataLayer = (window as any).dataLayer || [];
        function gtag(...args: any[]) { (window as any).dataLayer.push(args); }
        gtag('js', new Date());
        gtag('config', googleAnalyticsId.value);
    }
});
</script>

<template>
    <Head>
        <meta v-if="!allowIndexing" name="robots" content="noindex,nofollow" />
    </Head>
    <div class="min-h-screen bg-slate-50">
        <div class="py-12">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden sm:rounded-lg py-16 px-32 flex flex-col gap-8 relative">

                    <Header
                        v-if="components.header"
                        :logo="logo"
                        :name="props.data.displayName.text"
                        :business-type="props.data.primaryTypeDisplayName.text"
                        :address-components="props.data.addressComponents"
                    >
                    </Header>

                    <QuickActions
                        v-if="components.quick_actions"
                        :phone-number="props.data.nationalPhoneNumber"
                        :contact="props.data.contact"
                        :quick-links="props.data.quicklinks"
                        :preview="true"
                    />

                    <Description
                        v-if="components.description"
                        :description="description"
                    />

                    <Gallery
                        v-if="components.gallery"
                        :photos="props.data.images"
                    />

                    <hr>

                    <Contact
                        v-if="components.contact"
                        :formatted-address="props.data.formattedAddress"
                        :phone-number="props.data.nationalPhoneNumber"
                        :opening-hours="props.data.regularOpeningHours"
                        :socials="props.data.socials"
                    />

                    <hr>

                    <Reviews
                        v-if="components.reviews"
                        :reviews="props.data.reviews"
                        :rating="props.data.rating"
                    />

                </div>
            </div>
        </div>
    </div>
</template>
