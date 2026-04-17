<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, provide, shallowRef } from 'vue';
import { ArrowRight, ArrowLeft } from 'lucide-vue-next';
import { store as previewStore } from '@/routes/preview';
import Logo from '@/components/setup/Logo.vue';
import Description from '@/components/setup/Description.vue';
import Socials from '@/components/setup/Socials.vue';
import Contact from '@/components/setup/Contact.vue';
import Buttons from '@/components/setup/Buttons.vue';
import { Button } from '@/components/ui/button';

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
});

provide('form', form);
provide('siteData', props.site.data ?? null);
provide('siteId', props.id ?? null);

interface Step {
    component: any;
    title: string;
    subtitle: string;
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
];

const currentIndex = computed(() =>
    steps.findIndex((step) => step.component === currentStep.value)
);

const currentStepDef = computed(() => steps[currentIndex.value]);
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
    <div class="min-h-screen bg-background text-foreground flex flex-col items-center px-6 py-10 lg:justify-center lg:py-16">
        <div class="w-full max-w-md flex flex-col gap-8">

            <!-- Top bar: business name + subtle counter -->
            <div class="flex items-center justify-between">
                <p v-if="businessName" class="text-sm font-medium truncate text-muted-foreground max-w-[70%]">
                    {{ businessName }}
                </p>
                <p class="text-sm text-muted-foreground ml-auto">
                    {{ currentIndex + 1 }} / {{ steps.length }}
                </p>
            </div>

            <!-- Progress bar -->
            <div class="flex gap-1.5 -mt-4">
                <div
                    v-for="(step, i) in steps"
                    :key="i"
                    class="h-1 flex-1 rounded-full transition-all duration-300"
                    :class="i <= currentIndex ? 'bg-primary' : 'bg-muted'"
                />
            </div>

            <!-- Step question + subtitle -->
            <div class="flex flex-col gap-2">
                <h1 class="text-2xl font-bold leading-snug">
                    {{ currentStepDef.title }}
                </h1>
                <p class="text-muted-foreground leading-relaxed">
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
                            class="flex-1 h-12 text-base font-semibold"
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
                        class="text-sm text-muted-foreground hover:text-foreground text-center w-full py-1 transition-colors"
                        @click="skip"
                    >
                        Skip for now →
                    </button>
                </div>
            </form>

        </div>
    </div>
</template>
