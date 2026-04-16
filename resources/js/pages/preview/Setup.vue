<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, provide, shallowRef } from 'vue';
import { ArrowRight, ArrowLeft } from 'lucide-vue-next';
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
        default: () => {}
    }
});

const currentStep = shallowRef(Logo);

const form = useForm({
    logo: props.site.data.logo ?? null,
    description: props.site.data.description ?? '',
    socials: {
        facebook: props.site.data.socials?.facebook ?? '',
        x: props.site.data.socials?.x ?? '',
        instagram: props.site.data.socials?.instagram ?? '',
        linkedin: props.site.data.socials?.linkedin ?? '',
    },
    contact: props.site.data.contact ?? '',
    quicklinks: props.site.data.quicklinks ?? []
});

provide('form', form);

const steps = [
    { component: Logo },
    { component: Description },
    { component: Socials },
    { component: Contact },
    { component: Buttons },
];

const currentIndex = computed(() => {
    return steps.findIndex((step) => step.component === currentStep.value);
})

const nextStep = () => {
    if (currentIndex.value < steps.length - 1) {
        currentStep.value = steps[currentIndex.value + 1].component;
    } else {
        complete();
    }
};

const previousStep = () => {
    if (currentIndex.value >= 1) {
        currentStep.value = steps[currentIndex.value - 1].component;
    }
}

const complete = () => {
    form.post(route('preview.complete', props.id))
}
</script>

<template>
    <div class="flex min-h-screen flex-col items-center bg-background p-6 text-foreground lg:justify-center lg:p-8">
        <form class="flex flex-col gap-4 min-w-xl">
            <component :is="currentStep" />

            <div class="flex flex-row gap-4 w-full">
                <Button
                    v-if="currentIndex >= 1"
                    @click="previousStep"
                    type="button"
                    variant="outline"
                    class="flex-1"
                >
                    <ArrowLeft class="h-4 w-4" /> Back
                </Button>
                <Button
                    @click="nextStep"
                    type="button"
                    class="flex-1"
                >
                    Continue <ArrowRight class="h-4 w-4" />
                </Button>
            </div>

        </form>
    </div>
</template>
