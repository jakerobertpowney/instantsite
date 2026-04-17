<script setup lang="ts">
import QuickActions from '@/components/site/QuickActions.vue';
import Description from '@/components/site/Description.vue';
import Gallery from '@/components/site/Gallery.vue';
import Header from '@/components/site/Header.vue';
import Contact from '@/components/site/Contact.vue';
import Reviews from '@/components/site/Reviews.vue';
import { ArrowRight } from 'lucide-vue-next';
import { router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { complete as previewComplete } from '@/routes/preview';

const props = defineProps({
    id: String,
    data: Object
})

const nextStep = () => {
    router.post(previewComplete.url(props.id!))
}

</script>

<template>
    <div class="min-h-screen bg-slate-50">

        <div class="fixed bottom-0 left-0 right-0 z-10 bg-white px-5 py-3">
            <div class="flex flex-row gap-4 w-full max-w-sm mx-auto">
                <Button @click="nextStep" type="button" class="flex-1" size="lg">
                    Continue <ArrowRight class="h-4 w-4" />
                </Button>
            </div>
        </div>

        <div class="py-12">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">

                <div class="bg-white overflow-hidden sm:rounded-lg py-16 px-32 flex flex-col gap-8 relative">

                    <Header
                        :logo="props.data.logo"
                        :name="props.data.displayName.text"
                        :business-type="props.data.primaryTypeDisplayName.text"
                        :address-components="props.data.addressComponents"
                    >
                    </Header>

                    <QuickActions
                        :phone-number="props.data.nationalPhoneNumber"
                        :contact="props.data.contact"
                        :quick-links="props.data.quickLinks"
                        :preview="true"
                    />

                    <Description
                        :description="props.data.description"
                    />

                    <Gallery
                        :photos="props.data.images"
                    />

                    <hr>

                    <Contact
                        :formatted-address="props.data.formattedAddress"
                        :phone-number="props.data.nationalPhoneNumber"
                        :opening-hours="props.data.regularOpeningHours"
                        :socials="props.data.socials"
                    />

                    <hr>

                    <Reviews
                        :reviews="props.data.reviews"
                        :rating="props.data.rating"
                    />

                </div>
            </div>
        </div>
    </div>
</template>
