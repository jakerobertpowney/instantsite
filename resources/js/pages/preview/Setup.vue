<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue';
import { useForm } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, provide, shallowRef } from 'vue';
import { ArrowRight, ArrowLeft, AlertCircle } from 'lucide-vue-next';
import { store as previewStore } from '@/routes/preview';
import BusinessDetails from '@/components/setup/BusinessDetails.vue';
import Logo from '@/components/setup/Logo.vue';
import Description from '@/components/setup/Description.vue';
import Socials from '@/components/setup/Socials.vue';
import Contact from '@/components/setup/Contact.vue';
import Buttons from '@/components/setup/Buttons.vue';
import Services from '@/components/setup/Services.vue';
import OpeningHours from '@/components/setup/OpeningHours.vue';
import Photos from '@/components/setup/Photos.vue';
import Reviews from '@/components/setup/Reviews.vue';
import { Button } from '@/components/ui/button';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    id: {
        type: String,
        default: null
    },
    site: {
        type: Object,
        default: () => null
    },
    isBlankFlow: {
        type: Boolean,
        default: false
    }
});

const defaultOpeningHours = () => [
    { day: 'Sunday',    open: '09:00', close: '17:00', closed: true },
    { day: 'Monday',    open: '09:00', close: '17:00', closed: false },
    { day: 'Tuesday',   open: '09:00', close: '17:00', closed: false },
    { day: 'Wednesday', open: '09:00', close: '17:00', closed: false },
    { day: 'Thursday',  open: '09:00', close: '17:00', closed: false },
    { day: 'Friday',    open: '09:00', close: '17:00', closed: false },
    { day: 'Saturday',  open: '09:00', close: '17:00', closed: true },
];

const currentStep = shallowRef(props.isBlankFlow ? BusinessDetails : Logo);

const form = useForm(`setup-wizard-${props.id ?? 'new'}`, {
    // Business details (new flat fields)
    business_name:     props.site?.business_name     ?? '',
    business_type:     props.site?.business_type     ?? '',
    formatted_address: props.site?.formatted_address ?? '',
    city:              props.site?.city              ?? '',
    region:            props.site?.region            ?? '',
    phone:             props.site?.phone             ?? '',
    whatsapp_number:   props.site?.whatsapp_number   ?? '',
    website_url:       props.site?.website_url       ?? '',
    // Wizard fields
    logo: null as File | null,
    suggested_logo_url: null as string | null,
    description: props.site?.description ?? '',
    socials: {
        facebook:  props.site?.socials?.facebook  ?? '',
        x:         props.site?.socials?.x         ?? '',
        instagram: props.site?.socials?.instagram ?? '',
        linkedin:  props.site?.socials?.linkedin  ?? '',
    },
    contact:    props.site?.contact_email ?? '',
    quickLinks: props.site?.quick_links   ?? [],
    opening_hours: (props.site?.opening_hours && props.site.opening_hours.length > 0)
        ? props.site.opening_hours
        : defaultOpeningHours(),
    // Photos — new uploads only; existing saved photos are shown from siteData
    photos: [] as File[],
    remove_photos: [] as string[],
    // Reviews
    rating: (props.site?.rating ?? null) as number | null,
    review_count: (props.site?.review_count ?? null) as number | null,
    reviews: (props.site?.reviews ?? []) as Array<{ id: string; author: string; text: string; rating: number; date: string }>,
    // Services — pre-populated from FetchBusinessServices job if available
    services: (props.site?.services ?? []) as Array<{
        id: string;
        name: string;
        description: string | null;
        price: string | null;
        show_price: boolean;
        featured: boolean;
    }>,
});

provide('form', form);
provide('siteData', props.site ?? null);
provide('siteId', props.id ?? null);

// When returning to edit (e.g. from the preview page), the useForm key cache may
// hold stale values that override the fresh server props. Reset all fields from
// props.site on mount so the user always sees the saved data.
onMounted(() => {
    if (!props.site) return;
    form.business_name     = props.site.business_name     ?? '';
    form.business_type     = props.site.business_type     ?? '';
    form.formatted_address = props.site.formatted_address ?? '';
    form.city              = props.site.city              ?? '';
    form.region            = props.site.region            ?? '';
    form.phone             = props.site.phone             ?? '';
    form.whatsapp_number   = props.site.whatsapp_number   ?? '';
    form.website_url       = props.site.website_url       ?? '';
    form.description       = props.site.description       ?? '';
    form.contact           = props.site.contact_email     ?? '';
    form.quickLinks        = props.site.quick_links       ?? [];
    form.services          = props.site.services          ?? [];
    form.socials = {
        facebook:  props.site.socials?.facebook  ?? '',
        x:         props.site.socials?.x         ?? '',
        instagram: props.site.socials?.instagram ?? '',
        linkedin:  props.site.socials?.linkedin  ?? '',
    };
    if (props.site.opening_hours && props.site.opening_hours.length > 0) {
        form.opening_hours = props.site.opening_hours;
    }
    form.rating       = props.site.rating       ?? null;
    form.review_count = props.site.review_count ?? null;
    form.reviews      = props.site.reviews      ?? [];
    // Reset removal list so re-entering edit doesn't carry over old removals.
    form.remove_photos = [];
    // Note: form.logo is intentionally NOT reset — File objects can't be
    // serialised, and the Logo component reads siteData.logo_path directly.
});

interface Step {
    component: any;
    title: string;
    subtitle: string;
    skippableTitle?: string;
    skippableSubtitle?: string;
    skippable: boolean;
}

const steps: Step[] = props.isBlankFlow
    ? [
        {
            component: BusinessDetails,
            title: 'Tell us about your business',
            subtitle: "We'll use this to build your website header and contact section.",
            skippable: false,
        },
        {
            component: Logo,
            title: 'Do you have a logo?',
            subtitle: 'Upload it and it will appear on your website. You can always add one later.',
            skippable: true,
        },
        {
            component: Photos,
            title: 'Add photos of your work',
            subtitle: "Your phone's camera roll is perfect for this — before/after shots, finished jobs, your team. You can add more later.",
            skippable: true,
        },
        {
            component: Description,
            title: 'What does your business do?',
            subtitle: 'Write 2–3 sentences that customers will see when they visit your website.',
            skippable: false,
        },
        {
            component: OpeningHours,
            title: 'When are you open?',
            subtitle: 'Help customers know when they can reach you.',
            skippable: true,
        },
        {
            component: Socials,
            title: 'Are you on social media?',
            subtitle: "Add your pages so customers can find and follow you online. Skip if you're not on them.",
            skippable: true,
        },
        {
            component: Contact,
            title: 'Want customers to message you?',
            subtitle: "Add the ways customers can reach you — phone, WhatsApp, and email.",
            skippable: true,
        },
        {
            component: Buttons,
            title: 'Add buttons for customers to tap',
            subtitle: 'Make it easy for people to book, order, or get a quote directly from your website.',
            skippable: true,
        },
        {
            component: Services,
            title: 'Do you want to list your services?',
            subtitle: 'Adding your services helps customers know what you offer and what to expect to pay.',
            skippable: true,
        },
        {
            component: Reviews,
            title: 'How are your reviews looking?',
            subtitle: "Confirm your Google star rating and review count — we'll show them on your site with a link to your Google listing. You can also add your own testimonials.",
            skippable: true,
        },
    ]
    : [
        {
            component: BusinessDetails,
            title: 'Check your business details',
            subtitle: "We've pre-filled these from your Google listing. Edit anything that looks wrong.",
            skippable: false,
        },
        {
            component: Logo,
            title: 'Do you have a logo?',
            subtitle: 'Upload it and it will appear on your website. You can always add one later.',
            skippable: true,
        },
        {
            component: Photos,
            title: 'Add photos of your work',
            subtitle: "Your phone's camera roll is perfect for this — before/after shots, finished jobs, your team. You can add more later.",
            skippable: true,
        },
        {
            component: Description,
            title: 'What does your business do?',
            subtitle: 'Write 2–3 sentences that customers will see when they visit your website.',
            skippable: false,
        },
        {
            component: OpeningHours,
            title: 'When are you open?',
            subtitle: 'Check your opening hours look right.',
            skippable: true,
        },
        {
            component: Socials,
            title: 'Are you on social media?',
            subtitle: "Add your pages so customers can find and follow you online. Skip if you're not on them.",
            skippable: true,
        },
        {
            component: Contact,
            title: 'Want customers to message you?',
            subtitle: "Add the ways customers can reach you — phone, WhatsApp, and email.",
            skippable: true,
        },
        {
            component: Buttons,
            title: 'Add buttons for customers to tap',
            subtitle: 'Make it easy for people to book, order, or get a quote directly from your website.',
            skippable: true,
        },
        {
            component: Services,
            title: 'What services do you offer?',
            subtitle: "We've found some services to get you started — check the prices and remove anything that doesn't apply.",
            skippableTitle: 'Do you want to list your services?',
            skippableSubtitle: 'Adding your services helps customers know what you offer and what to expect to pay.',
            skippable: true,
        },
        {
            component: Reviews,
            title: 'How are your reviews looking?',
            subtitle: "Confirm your Google star rating and review count — we'll show them on your site with a link to your Google listing. You can also add your own testimonials.",
            skippable: true,
        },
    ];

const currentIndex = computed(() =>
    steps.findIndex((step) => step.component === currentStep.value)
);

const hasSuggestedServices = computed(() =>
    (props.site?.services ?? []).length > 0
);

const currentStepDef = computed(() => {
    const step = steps[currentIndex.value];
    // For the Services step, swap to the "no suggestions" copy when there's nothing pre-filled
    if (step.component === Services && !hasSuggestedServices.value && step.skippableTitle) {
        return { ...step, title: step.skippableTitle, subtitle: step.skippableSubtitle ?? step.subtitle };
    }
    return step;
});

const businessName = computed(() => props.site?.business_name ?? form.business_name ?? null);
const isLastStep = computed(() => currentIndex.value === steps.length - 1);
const hasErrors = computed(() => Object.keys(form.errors).length > 0);

const goToNext = () => {
    if (!isLastStep.value) {
        form.clearErrors();
        currentStep.value = steps[currentIndex.value + 1].component;
    } else {
        complete();
    }
};

const goToPrev = () => {
    if (currentIndex.value >= 1) {
        form.clearErrors();
        currentStep.value = steps[currentIndex.value - 1].component;
    }
};

const skip = () => goToNext();

// Map each step index to the field name prefixes that belong to it.
const stepFields: string[][] = props.isBlankFlow
    ? [
        ['business_name', 'business_type', 'formatted_address'],            // 0: BusinessDetails
        ['logo', 'suggested_logo_url'],                                      // 1: Logo
        ['photos', 'remove_photos'],                                         // 2: Photos
        ['description'],                                                     // 3: Description
        ['opening_hours'],                                                   // 4: OpeningHours
        ['socials'],                                                         // 5: Socials
        ['phone', 'whatsapp_number', 'contact'],                             // 6: Contact
        ['quickLinks'],                                                      // 7: Buttons
        ['services'],                                                        // 8: Services
        ['rating', 'review_count', 'reviews'],                               // 9: Reviews
    ]
    : [
        ['business_name', 'business_type', 'formatted_address'],            // 0: BusinessDetails
        ['logo', 'suggested_logo_url'],                                      // 1: Logo
        ['photos', 'remove_photos'],                                         // 2: Photos
        ['description'],                                                     // 3: Description
        ['opening_hours'],                                                   // 4: OpeningHours
        ['socials'],                                                         // 5: Socials
        ['phone', 'whatsapp_number', 'contact'],                             // 6: Contact
        ['quickLinks'],                                                      // 7: Buttons
        ['services'],                                                        // 8: Services
        ['rating', 'review_count', 'reviews'],                               // 9: Reviews
    ];

const complete = () => {
    const postUrl = props.isBlankFlow || !props.id
        ? '/setup/new'
        : previewStore.url(props.id);

    form.post(postUrl, {
        forceFormData: true,
        onError: (errors) => {
            const errorKeys = Object.keys(errors);
            const firstErrorStepIndex = stepFields.findIndex((fields) =>
                fields.some((field) =>
                    errorKeys.some((key) => key === field || key.startsWith(field + '.'))
                )
            );
            if (firstErrorStepIndex !== -1) {
                // Use nextTick so form.errors is fully committed to Vue's
                // reactive system before the new step component mounts.
                nextTick(() => {
                    currentStep.value = steps[firstErrorStepIndex].component;
                });
            }
        },
    });
};
</script>

<template>
    <Head>
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="" />
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet" />
    </Head>

    <!--
        CSS variable overrides:  --primary → brand blue, --radius → 10px buttons
        Font-family declared here is applied to all text in this subtree.
        Shadcn inputs/buttons read --primary / --radius and will pick up the overrides.
    -->
    <div
        class="min-h-screen flex flex-col items-center px-6 py-10 lg:justify-center lg:py-16 bg-white text-brand-ink"
        style="
            font-family: 'Inter', ui-sans-serif, system-ui, sans-serif;
            --primary:            #1e66f5;
            --primary-foreground: #ffffff;
            --radius:             0.75rem;
            --border:             #dde1e8;
            --ring:               #1e66f5;
            --background:         #ffffff;
            --foreground:         #0f172a;
            --muted:              #edf1f8;
            --muted-foreground:   #64748b;
            --destructive:        #b91c1c;
        "
    >
        <div class="w-full max-w-md flex flex-col gap-8">

            <!-- Brand mark + top bar -->
            <div class="flex items-center justify-between">
                <!-- Logo -->
                <a href="/">
                    <AppLogo />
                </a>

                <!-- Business name + step counter -->
                <div class="flex items-center gap-3 ml-auto">
                    <p v-if="businessName" class="text-sm font-medium truncate max-w-[160px] text-brand-ink-soft">
                        {{ businessName }}
                    </p>
                    <p class="text-sm flex-shrink-0 text-brand-ink-soft">
                        {{ currentIndex + 1 }} / {{ steps.length }}
                    </p>
                </div>
            </div>

            <!-- Progress bar -->
            <div class="flex gap-1.5 -mt-4">
                <div
                    v-for="(step, i) in steps"
                    :key="i"
                    class="h-1 flex-1 rounded-full transition-all duration-300"
                    :class="i <= currentIndex ? 'bg-brand-blue' : 'bg-border-brand-line'"
                />
            </div>

            <!-- Step question + subtitle -->
            <div class="flex flex-col gap-2">
                <h1 class="text-2xl font-bold leading-snug text-brand-ink">
                    {{ currentStepDef.title }}
                </h1>
                <p class="leading-relaxed text-brand-ink-soft">
                    {{ currentStepDef.subtitle }}
                </p>
            </div>

            <!-- Error banner — shown when server returns validation errors -->
            <div
                v-if="hasErrors"
                class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 flex items-start gap-2.5 text-sm text-red-700"
            >
                <AlertCircle class="h-4 w-4 mt-0.5 shrink-0 text-red-600" />
                <div>
                    <p class="font-semibold">Please fix the highlighted fields below.</p>
                    <ul class="mt-1 list-disc list-inside space-y-0.5 text-red-600">
                        <li v-for="(msg, key) in form.errors" :key="key">{{ msg }}</li>
                    </ul>
                </div>
            </div>

            <!-- Step content -->
            <form class="flex flex-col gap-6" @submit.prevent>
                <component :is="currentStep" />

                <!-- Navigation buttons -->
                <div class="flex flex-col gap-3">
                    <div class="flex gap-3">
                        <Button
                            v-if="currentIndex >= 1"
                            @click="goToPrev"
                            type="button"
                            variant="outline"
                            class="flex-1 h-12"
                        >
                            <ArrowLeft class="h-4 w-4 mr-1" /> Back
                        </Button>
                        <Button
                            @click="goToNext"
                            type="button"
                            class="flex-1 h-12 text-[15px] font-bold"
                            :disabled="form.processing"
                        >
                            {{ isLastStep ? 'Preview my site' : 'Continue' }}
                            <ArrowRight class="h-4 w-4 ml-1" />
                        </Button>
                    </div>

                    <!-- Skip link (only for skippable steps) -->
                    <button
                        v-if="currentStepDef.skippable"
                        type="button"
                        class="text-sm text-center w-full py-1 transition-opacity hover:opacity-60 text-brand-ink-soft"
                        @click="skip"
                    >
                        Skip for now →
                    </button>
                </div>
            </form>

        </div>
    </div>
</template>
