<script setup lang="ts">
import { SquareArrowOutUpRight, Instagram, Facebook, Twitter, Linkedin } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps({
    formattedAddress: {
        type: String,
        default: ''
    },
    phoneNumber: String,
    openingHours: {
        type: Array,
        default: () => []
    },
    socials: {
        type: Array,
        default: () => []
    }
});

const weekdayDescriptions = computed(() => {
    const dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

    // Create an array to store the formatted hours for each day
    const formattedHours = [];

    // Process each period in the openingHours array
    props.openingHours.periods.forEach(period => {
        if (period.open && period.close) {
            const dayIndex = period.open.day;
            const dayName = dayNames[dayIndex];

            // Format opening time
            const openHour = period.open.hour;
            const openMinute = period.open.minute;
            const openAmPm = openHour >= 12 ? 'pm' : 'am';
            const formattedOpenHour = openHour % 12 === 0 ? 12 : openHour % 12;
            const formattedOpenTime = `${formattedOpenHour}${openMinute > 0 ? `:${openMinute.toString().padStart(2, '0')}` : ''}${openAmPm}`;

            // Format closing time
            const closeHour = period.close.hour;
            const closeMinute = period.close.minute;
            const closeAmPm = closeHour >= 12 ? 'pm' : 'am';
            const formattedCloseHour = closeHour % 12 === 0 ? 12 : closeHour % 12;
            const formattedCloseTime = `${formattedCloseHour}${closeMinute > 0 ? `:${closeMinute.toString().padStart(2, '0')}` : ''}${closeAmPm}`;

            // Create the formatted string for this day
            formattedHours[dayIndex] = {
                day: dayName,
                hours: `${formattedOpenTime}–${formattedCloseTime}`
            };
        }
    });

    // Return the array of formatted hours
    return formattedHours;
})

</script>

<template>
    <section class="grid grid-cols-2 gap-6">
        <div class="flex flex-col gap-1.5">
            <h2 class="text-xl font-semibold text-gray-800">Contact Information</h2>

            <div v-if="formattedAddress">
                <p class="mb-1 text-lg font-semibold flex flex-row items-center gap-1.5">
                    Address
                    <a :href="`https://www.google.com/maps/place/${encodeURIComponent(formattedAddress)}`" target="_blank"
                    ><SquareArrowOutUpRight class="h-4 w-4"
                    /></a>
                </p>
                <p class="flex items-st gap-1.5 text-gray-600">
                    {{ formattedAddress }}
                </p>
            </div>

            <div v-if="phoneNumber">
                <p class="mb-1 text-lg font-semibold">Phone Number</p>
                <p class="text-gray-600">{{ phoneNumber }}</p>
            </div>

            <div v-if="Object.values(socials).length">
                <p class="mb-1 text-lg font-semibold">Socials</p>
                <div class="flex flex-row gap-1.5 items-center">
                    <a
                        v-if="socials.instagram"
                        :href="socials.instagram"
                        target="_blank"
                    >
                        <Instagram/>
                    </a>
                    <a
                        v-if="socials.x"
                        :href="socials.x"
                        target="_blank"
                    >
                        <Twitter/>
                    </a>
                    <a
                        v-if="socials.facebook"
                        :href="socials.facebook"
                        target="_blank"
                    >
                        <Facebook/>
                    </a>
                    <a
                        v-if="socials.linkedin"
                        :href="socials.linkedin"
                        target="_blank"
                    >
                        <Linkedin/>
                    </a>
                </div>
            </div>
        </div>

        <div class="flex flex-col gap-1.5">
            <div v-if="weekdayDescriptions.length">
                <p class="mb-1 text-lg font-semibold">Opening Hours</p>
                <div class="flex max-w-56 flex-col gap-1">
                    <p v-for="(day, index) in weekdayDescriptions" :key="index" class="grid grid-cols-2">
                        <span>{{ day.day }}</span>
                        <span>{{ day.hours }}</span>
                    </p>
                </div>
            </div>
        </div>
    </section>
</template>
