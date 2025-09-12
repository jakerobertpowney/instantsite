<script setup lang="ts">
import { Mail, Phone, Send } from 'lucide-vue-next';

const props = defineProps({
    phoneNumber: String,
    quickLinks: Array,
    contact: String,
    preview: Boolean
})

const submit = () => {
    if(props.preview) {
        alert('In preview mode, no form will be submitted.')
    }
}

</script>

<template>
    <section class="flex items-end justify-start b-1">

        <div class="flex gap-3">
            <a :href="'tel:' + phoneNumber" class="flex gap-2 justify-center items-center rounded-lg bg-white text-black border border-black px-5 py-3 cursor-pointer">
                Call <Phone class="h-4 w-4" />
            </a>

            <button type="button" data-modal-target="contact-modal" data-modal-toggle="contact-modal" class="flex gap-2 justify-center items-center rounded-lg bg-white text-black border border-black px-5 py-3 cursor-pointer">
                Contact <Mail class="h-4 w-4" />
            </button>

            <a
                v-for="(link, index) in quickLinks"
                :key="index"
                :href="link.link"
                target="_blank"
                class="flex gap-2 justify-center items-center rounded-lg bg-black text-white px-5 py-3 cursor-pointer"
            >
                {{ link.label }}
            </a>
        </div>

        <!-- Main modal -->
        <div id="contact-modal" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
            <div class="relative p-4 w-full max-w-2xl max-h-full">
                <!-- Modal content -->
                <div class="relative bg-white rounded-lg shadow-sm dark:bg-gray-700">
                    <!-- Modal header -->
                    <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t dark:border-gray-600 border-gray-200">
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                            Contact us
                        </h3>
                        <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white" data-modal-hide="contact-modal">
                            <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                            </svg>
                            <span class="sr-only">Close modal</span>
                        </button>
                    </div>
                    <!-- Modal body -->
                    <div class="p-4 md:p-5 space-y-4">
                        <form class="space-y-4">
                            <div>
                                <label for="email" class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">Your email</label>
                                <input type="email" id="email" class="shadow-sm bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500 dark:shadow-sm-light" placeholder="yourname@domain.com" required>
                            </div>
                            <div>
                                <label for="subject" class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">Subject</label>
                                <input type="text" id="subject" class="block p-3 w-full text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 shadow-sm focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500 dark:shadow-sm-light" placeholder="Let us know how we can help you" required>
                            </div>
                            <div class="sm:col-span-2">
                                <label for="message" class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-400">Your message</label>
                                <textarea id="message" rows="6" class="block p-2.5 w-full text-sm text-gray-900 bg-gray-50 rounded-lg shadow-sm border border-gray-300 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="Leave a comment..."></textarea>
                            </div>
                        </form>
                    </div>
                    <!-- Modal footer -->
                    <div class="flex flex-row gap-4 items-center p-4 md:p-5 border-t border-gray-200 rounded-b dark:border-gray-600">
                        <button @click.prevent="submit" type="submit" class="flex gap-2 justify-center items-center rounded-lg bg-black text-white px-5 py-3 cursor-pointer">
                            Submit <Send class="h-4 w-4" />
                        </button>
                        <button data-modal-hide="contact-modal" type="button" data-modal-target="contact-modal" data-modal-toggle="contact-modal" class="flex gap-2 justify-center items-center rounded-lg bg-white text-black border border-black px-5 py-3 cursor-pointer">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </section>
</template>
