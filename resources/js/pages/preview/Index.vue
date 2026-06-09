<script setup lang="ts">
import Gallery from '@/components/site/Gallery.vue';
import Contact from '@/components/site/Contact.vue';
import QuickActions from '@/components/site/QuickActions.vue';
import Description from '@/components/site/Description.vue';
import Header from '@/components/site/Header.vue';
import Reviews from '@/components/site/Reviews.vue';
import { ArrowRight, ArrowLeft } from 'lucide-vue-next';
import { router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { complete as previewComplete, setup as previewSetup } from '@/routes/preview';

const goBack = () => router.visit(previewSetup(props.id!).url);
import { computed, onMounted } from 'vue';
import { bookingCtaLabel, findPrimaryBookingLink } from '@/lib/bookingLinks';
import { getEffectivePalette, paletteToCssVars } from '@/lib/palette';

const props = defineProps({
    id:   String,
    data: Object,
});

const nextStep = () => {
    router.post(previewComplete.url(props.id!));
};

// Data is now a flat shape from buildSiteData() — no normalisation needed
const normalizedData = computed(() => props.data ?? {});

// ── Everything below mirrors site/Index.vue exactly ──────────────────────────

// All components are shown in preview — no flags during onboarding.
// contact_form is intentionally hidden (requires a live account + premium).

const description  = computed(() => normalizedData.value?.description);
const logo         = computed(() => normalizedData.value?.logo_path);
const contactEmail = computed(() => normalizedData.value?.contact_email);
const primaryBookingLink = computed(() => findPrimaryBookingLink(normalizedData.value?.quick_links ?? []));
const primaryBookingLabel = computed(() => bookingCtaLabel(primaryBookingLink.value));

const palette  = computed(() => getEffectivePalette(normalizedData.value));
const cssVars  = computed(() => paletteToCssVars(palette.value));

const headerBg = computed(() => normalizedData.value?.settings?.header_bg ?? null);

const heroStyle = computed<Record<string, string>>(() => {
    const bg = headerBg.value;

    if (bg?.type === 'color' && bg.value) {
        return { backgroundColor: bg.value };
    }
    if (bg?.type === 'google_image' && bg.value) {
        return { backgroundImage: `url('/${bg.value}')`, backgroundSize: 'cover', backgroundPosition: 'center' };
    }
    if ((bg?.type === 'custom_image' || bg?.type === 'stock') && bg.value) {
        return { backgroundImage: `url('${bg.value}')`, backgroundSize: 'cover', backgroundPosition: 'center' };
    }

    const first = normalizedData.value?.images?.[0];
    if (first) {
        return { backgroundImage: `url('/${first}')`, backgroundSize: 'cover', backgroundPosition: 'center' };
    }
    return {
        background: `linear-gradient(135deg, ${palette.value.primary} 0%, ${palette.value.secondary} 100%)`,
    };
});

// No GA or indexing controls in preview
onMounted(() => { /* intentionally empty */ });
</script>

<template>
    <!-- Sticky "Continue" bar — floats above the site preview -->
    <div class="fixed bottom-0 left-0 right-0 z-50 border-t bg-white/95 backdrop-blur-sm px-5 py-3 shadow-lg dark:bg-background/95 dark:border-border">
        <div class="flex flex-row items-center justify-between gap-3 w-full max-w-5xl mx-auto">
            <Button
                variant="outline"
                size="lg"
                class="gap-2 shrink-0"
                @click="goBack"
            >
                <ArrowLeft class="h-4 w-4" /> Edit
            </Button>
            <p class="text-sm text-muted-foreground hidden sm:block flex-1 text-center">
                Here's a preview of your website — looking good! 👇
            </p>
            <Button @click="nextStep" type="button" size="lg" class="gap-2 shrink-0">
                Create my account <ArrowRight class="h-4 w-4" />
            </Button>
        </div>
    </div>

    <!-- Site preview — same template as the published site -->
    <div class="min-h-screen bg-gray-50 font-sans antialiased pb-24" :style="cssVars">

        <!-- ── Hero ───────────────────────────────────────────────────────── -->
        <div class="relative overflow-hidden" style="min-height: 380px">
            <div class="absolute inset-0" :style="heroStyle" />
            <div class="absolute inset-0" style="background: linear-gradient(to top, rgba(0,0,0,0.75) 0%, rgba(0,0,0,0.35) 55%, rgba(0,0,0,0.15) 100%)" />
            <div class="absolute bottom-0 left-0 right-0 h-1" style="background-color: var(--site-primary)" />

            <a
                v-if="headerBg?.type === 'stock' && headerBg.credit"
                :href="headerBg.credit_url || 'https://www.pexels.com'"
                target="_blank"
                rel="noopener noreferrer"
                class="absolute top-2 right-3 z-20 text-[10px] text-white/60 hover:text-white/90 transition-colors"
            >
                Photo by {{ headerBg.credit }} on Pexels
            </a>

            <div class="relative z-10 flex flex-col justify-end" style="min-height: 380px">
                <div class="px-6 pb-8 md:px-14 md:pb-12 max-w-5xl mx-auto w-full">
                    <Header
                        :logo="logo"
                        :name="normalizedData?.business_name"
                        :business-type="normalizedData?.business_type"
                        :city="normalizedData?.city"
                        :region="normalizedData?.region"
                    />
                </div>
            </div>
        </div>

        <!-- ── Quick action buttons ───────────────────────────────────────── -->
        <div
            class="bg-white shadow-sm sticky top-0 z-30"
            style="border-bottom: 2px solid var(--site-primary)"
        >
            <div class="max-w-5xl mx-auto px-6 md:px-14 py-4">
                <QuickActions
                    :phone-number="normalizedData?.phone"
                    :whatsapp-number="normalizedData?.whatsapp_number"
                    :contact="contactEmail"
                    :show-form="false"
                    :quick-links="normalizedData?.quick_links"
                    :preview="true"
                />
            </div>
        </div>

        <!-- ── Main content ───────────────────────────────────────────────── -->
        <div class="bg-white">
            <div class="max-w-5xl mx-auto px-6 md:px-14">
                <Description
                    v-if="description"
                    :description="description"
                />
                <Gallery :photos="normalizedData?.images" />
            </div>
        </div>

        <!-- ── Reviews ────────────────────────────────────────────────────── -->
        <div v-if="normalizedData?.rating || normalizedData?.review_count || normalizedData?.reviews?.length" class="py-14" style="background-color: var(--site-primary-muted)">
            <div class="max-w-5xl mx-auto px-6 md:px-14">
                <Reviews
                    :reviews="normalizedData?.reviews ?? []"
                    :rating="normalizedData?.rating ?? 0"
                    :review-count="normalizedData?.review_count ?? 0"
                    :google-places-id="normalizedData?.google_places_id ?? ''"
                />
            </div>
        </div>

        <!-- ── Contact / info strip ───────────────────────────────────────── -->
        <Contact
            :formatted-address="normalizedData?.formatted_address"
            :phone-number="normalizedData?.phone"
            :opening-hours="normalizedData?.opening_hours"
            :socials="normalizedData?.socials"
            :contact="contactEmail"
            :show-form="false"
            :show-premium-teaser="!!contactEmail"
            :business-type="normalizedData?.business_type"
            :preview="true"
        />

        <!-- ── Footer ─────────────────────────────────────────────────────── -->
        <footer
            class="text-xs text-center py-5"
            style="background-color: var(--site-primary-dark)"
        >
            <span style="color: rgba(255,255,255,0.5)">Website powered by </span>
            <a
                href="https://321sites.com"
                target="_blank"
                rel="noopener noreferrer"
                class="font-medium text-white underline underline-offset-4"
            >
                321Sites
            </a>
        </footer>

    </div>
</template>
