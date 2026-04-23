<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue';
import { useForm } from '@inertiajs/vue3';
import { computed, provide, shallowRef } from 'vue';
import { ArrowRight, ArrowLeft } from 'lucide-vue-next';
import { store as previewStore } from '@/routes/preview';
import Logo from '@/components/setup/Logo.vue';
import Description from '@/components/setup/Description.vue';
import Socials from '@/components/setup/Socials.vue';
import Contact from '@/components/setup/Contact.vue';
import Buttons from '@/components/setup/Buttons.vue';
import Services from '@/components/setup/Services.vue';
import { Button } from '@/components/ui/button';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    id: String,
    site: {
        type: Object,
        default: () => ({})
    }
});

const currentStep = shallowRef(Logo);

const form = useForm({
    logo: props.site.data?.logo ?? null,
    suggested_logo_url: null as string | null,
    description: props.site.data?.description ?? '',
    socials: {
        facebook: props.site.data?.socials?.facebook ?? '',
        x: props.site.data?.socials?.x ?? '',
        instagram: props.site.data?.socials?.instagram ?? '',
        linkedin: props.site.data?.socials?.linkedin ?? '',
    },
    contact: props.site.data?.contact ?? '',
    quickLinks: props.site.data?.quickLinks ?? [],
    // Services — pre-populated from FetchBusinessServices job if available
    services: (props.site.data?.services ?? []) as Array<{
        id: string;
        name: string;
        description: string | null;
        price: string | null;
        show_price: boolean;
        featured: boolean;
    }>,
});

provide('form', form);
provide('siteData', props.site.data ?? null);
provide('siteId', props.id ?? null);

interface Step {
    component: any;
    title: string;
    subtitle: string;
    skippableTitle?: string;
    skippableSubtitle?: string;
    skippable: boolean;
}

const steps: Step[] = [
    {
        component: Logo,
        title: 'Do you have a logo?',
        subtitle: 'Upload it and it will appear on your website. You can always add one later.',
        skippable: true,
    },
    {
        component: Description,
        title: 'What does your business do?',
        subtitle: 'Write 2–3 sentences that customers will see when they visit your website.',
        skippable: false,
    },
    {
        component: Socials,
        title: 'Are you on social media?',
        subtitle: 'Add your pages so customers can find and follow you online. Skip if you\'re not on them.',
        skippable: true,
    },
    {
        component: Contact,
        title: 'Want customers to message you?',
        subtitle: 'We\'ll add a contact form to your website. Messages go straight to your email.',
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
        subtitle: 'We\'ve found some services to get you started — check the prices and remove anything that doesn\'t apply.',
        skippableTitle: 'Do you want to list your services?',
        skippableSubtitle: 'Adding your services helps customers know what you offer and what to expect to pay.',
        skippable: true,
    },
];

const currentIndex = computed(() =>
    steps.findIndex((step) => step.component === currentStep.value)
);

const hasSuggestedServices = computed(() =>
    (props.site.data?.suggested_services ?? []).length > 0
);

const currentStepDef = computed(() => {
    const step = steps[currentIndex.value];
    // For the Services step, swap to the "no suggestions" copy when there's nothing pre-filled
    if (step.component === Services && !hasSuggestedServices.value && step.skippableTitle) {
        return { ...step, title: step.skippableTitle, subtitle: step.skippableSubtitle ?? step.subtitle };
    }
    return step;
});

const businessName = computed(() => props.site.data?.displayName?.text ?? null);
const isLastStep = computed(() => currentIndex.value === steps.length - 1);

const goToNext = () => {
    if (!isLastStep.value) {
        currentStep.value = steps[currentIndex.value + 1].component;
    } else {
        complete();
    }
};

const goToPrev = () => {
    if (currentIndex.value >= 1) {
        currentStep.value = steps[currentIndex.value - 1].component;
    }
};

const skip = () => goToNext();

const complete = () => {
    form.post(previewStore.url(props.id!), { forceFormData: true });
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
        class="min-h-screen flex flex-col items-center px-6 py-10 lg:justify-center lg:py-16"
        style="
            background: #ffffff;
            font-family: 'Inter', ui-sans-serif, system-ui, sans-serif;
            color: #0f172a;
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
                    <p v-if="businessName" class="text-sm font-medium truncate max-w-[160px]" style="color: #64748b;">
                        {{ businessName }}
                    </p>
                    <p class="text-sm flex-shrink-0" style="color: #64748b;">
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
                    :style="i <= currentIndex ? 'background: #1e66f5;' : 'background: #dde1e8;'"
                />
            </div>

            <!-- Step question + subtitle -->
            <div class="flex flex-col gap-2">
                <h1 class="text-2xl font-bold leading-snug" style="color: #0f172a;">
                    {{ currentStepDef.title }}
                </h1>
                <p class="leading-relaxed" style="color: #64748b;">
                    {{ currentStepDef.subtitle }}
                </p>
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
                            {{ isLastStep ? 'Build my website' : 'Continue' }}
                            <ArrowRight class="h-4 w-4 ml-1" />
                        </Button>
                    </div>

                    <!-- Skip link (only for skippable steps) -->
                    <button
                        v-if="currentStepDef.skippable"
                        type="button"
                        class="text-sm text-center w-full py-1 transition-opacity hover:opacity-60"
                        style="color: #64748b;"
                        @click="skip"
                    >
                        Skip for now →
                    </button>
                </div>
            </form>

        </div>
    </div>
</template>

<style scoped>
/* ── Inputs — match auth page and marketing page style ───────────────────── */
:deep(input[type="email"]),
:deep(input[type="password"]),
:deep(input[type="text"]),
:deep(input[type="url"]),
:deep(input[type="tel"]),
:deep(input:not([type])) {
    height: 44px !important;
    font-size: 15px !important;
    border-radius: 8px !important;
    border-color: #dde1e8 !important;
    background: #ffffff !important;
    font-family: 'Inter', ui-sans-serif, system-ui, sans-serif !important;
}

:deep(input[type="email"]:focus),
:deep(input[type="password"]:focus),
:deep(input[type="text"]:focus),
:deep(input[type="url"]:focus),
:deep(input[type="tel"]:focus),
:deep(input:not([type]):focus) {
    border-color: #1e66f5 !important;
    box-shadow: 0 0 0 3px rgba(30, 102, 245, 0.12) !important;
    outline: none !important;
}

:deep(textarea) {
    font-size: 15px !important;
    border-radius: 8px !important;
    border-color: #dde1e8 !important;
    background: #ffffff !important;
    font-family: 'Inter', ui-sans-serif, system-ui, sans-serif !important;
    line-height: 1.6 !important;
}

:deep(textarea:focus) {
    border-color: #1e66f5 !important;
    box-shadow: 0 0 0 3px rgba(30, 102, 245, 0.12) !important;
    outline: none !important;
}

:deep(label) {
    font-size: 14px;
    font-weight: 600;
    color: #0f172a;
}
</style>
