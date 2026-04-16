<script setup lang="ts">
import { Mail, Phone, Send, X } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps({
    phoneNumber: String,
    quickLinks: Array as () => Array<{ label: string; link: string }>,
    contact: String,
    preview: Boolean,
});

const showModal = ref(false);
const formEmail = ref('');
const formSubject = ref('');
const formMessage = ref('');
const submitting = ref(false);
const submitted = ref(false);
const submitError = ref('');

const openModal = () => {
    submitted.value = false;
    submitError.value = '';
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    formEmail.value = '';
    formSubject.value = '';
    formMessage.value = '';
    submitError.value = '';
    submitted.value = false;
};

const submit = async () => {
    if (props.preview) {
        alert('In preview mode, no form will be submitted.');
        return;
    }

    if (!formEmail.value || !formSubject.value || !formMessage.value) {
        submitError.value = 'Please fill in all fields.';
        return;
    }

    submitting.value = true;
    submitError.value = '';

    try {
        const response = await fetch('/contact', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                email: formEmail.value,
                subject: formSubject.value,
                message: formMessage.value,
            }),
        });

        if (!response.ok) {
            const data = await response.json();
            submitError.value = data.error ?? 'Something went wrong. Please try again.';
            return;
        }

        submitted.value = true;
    } catch {
        submitError.value = 'Failed to send your message. Please try again.';
    } finally {
        submitting.value = false;
    }
};
</script>

<template>
    <section class="flex items-end justify-start b-1">

        <div class="flex gap-3 flex-wrap">
            <a
                :href="'tel:' + phoneNumber"
                class="flex gap-2 justify-center items-center rounded-lg bg-white text-black border border-black px-5 py-3 cursor-pointer"
            >
                Call <Phone class="h-4 w-4" />
            </a>

            <button
                type="button"
                @click="openModal"
                class="flex gap-2 justify-center items-center rounded-lg bg-white text-black border border-black px-5 py-3 cursor-pointer"
            >
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

        <!-- Contact modal -->
        <Teleport to="body">
            <div
                v-if="showModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
                @click.self="closeModal"
            >
                <!-- Backdrop -->
                <div class="absolute inset-0 bg-black/50" @click="closeModal" />

                <!-- Modal panel -->
                <div class="relative z-10 w-full max-w-lg rounded-lg bg-white shadow-xl">
                    <!-- Header -->
                    <div class="flex items-center justify-between border-b px-6 py-4">
                        <h3 class="text-lg font-semibold text-gray-900">Contact us</h3>
                        <button
                            type="button"
                            @click="closeModal"
                            class="rounded-md p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600"
                        >
                            <X class="h-5 w-5" />
                            <span class="sr-only">Close</span>
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="px-6 py-5 space-y-4">
                        <div v-if="submitted" class="rounded-lg bg-green-50 border border-green-200 p-4 text-green-800 text-sm">
                            Your message has been sent. We'll be in touch soon!
                        </div>

                        <template v-else>
                            <div v-if="submitError" class="rounded-lg bg-red-50 border border-red-200 p-3 text-red-700 text-sm">
                                {{ submitError }}
                            </div>

                            <div>
                                <label for="contact-email" class="block mb-1.5 text-sm font-medium text-gray-900">Your email</label>
                                <input
                                    id="contact-email"
                                    v-model="formEmail"
                                    type="email"
                                    class="w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2.5 text-sm text-gray-900 focus:border-black focus:outline-none focus:ring-1 focus:ring-black"
                                    placeholder="yourname@domain.com"
                                    required
                                />
                            </div>

                            <div>
                                <label for="contact-subject" class="block mb-1.5 text-sm font-medium text-gray-900">Subject</label>
                                <input
                                    id="contact-subject"
                                    v-model="formSubject"
                                    type="text"
                                    class="w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2.5 text-sm text-gray-900 focus:border-black focus:outline-none focus:ring-1 focus:ring-black"
                                    placeholder="How can we help?"
                                    required
                                />
                            </div>

                            <div>
                                <label for="contact-message" class="block mb-1.5 text-sm font-medium text-gray-900">Your message</label>
                                <textarea
                                    id="contact-message"
                                    v-model="formMessage"
                                    rows="5"
                                    class="w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2.5 text-sm text-gray-900 focus:border-black focus:outline-none focus:ring-1 focus:ring-black"
                                    placeholder="Leave a message..."
                                    required
                                />
                            </div>
                        </template>
                    </div>

                    <!-- Footer -->
                    <div class="flex gap-3 border-t px-6 py-4">
                        <template v-if="!submitted">
                            <button
                                type="button"
                                @click="submit"
                                :disabled="submitting"
                                class="flex gap-2 items-center rounded-lg bg-black text-white px-5 py-2.5 text-sm cursor-pointer disabled:opacity-60"
                            >
                                {{ submitting ? 'Sending...' : 'Send message' }}
                                <Send class="h-4 w-4" />
                            </button>
                            <button
                                type="button"
                                @click="closeModal"
                                class="flex gap-2 items-center rounded-lg bg-white text-black border border-black px-5 py-2.5 text-sm cursor-pointer"
                            >
                                Cancel
                            </button>
                        </template>
                        <template v-else>
                            <button
                                type="button"
                                @click="closeModal"
                                class="flex gap-2 items-center rounded-lg bg-black text-white px-5 py-2.5 text-sm cursor-pointer"
                            >
                                Close
                            </button>
                        </template>
                    </div>
                </div>
            </div>
        </Teleport>

    </section>
</template>
