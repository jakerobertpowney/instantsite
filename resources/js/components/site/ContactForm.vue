<script setup lang="ts">
import { Send } from 'lucide-vue-next';
import { ref } from 'vue';

defineProps({
    contact: String,
    preview: Boolean,
});

const formEmail   = ref('');
const formSubject = ref('');
const formMessage = ref('');
const honeypot    = ref('');
const submitting  = ref(false);
const submitted   = ref(false);
const submitError = ref('');

const submit = async (preview: boolean | undefined) => {
    if (preview) {
        alert('In preview mode, no form will be submitted.');
        return;
    }

    if (!formEmail.value || !formSubject.value || !formMessage.value) {
        submitError.value = 'Please fill in all fields.';
        return;
    }

    submitting.value  = true;
    submitError.value = '';

    try {
        const response = await fetch('/contact', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({
                email:   formEmail.value,
                subject: formSubject.value,
                message: formMessage.value,
                website: honeypot.value,
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
    <section id="contact-form" class="py-14" style="border-bottom: 1px solid var(--site-primary-muted)">
        <!-- Section label -->
        <p
            class="text-xs font-semibold uppercase tracking-widest mb-6"
            style="color: var(--site-primary)"
        >
            Get in touch
        </p>

        <div class="grid gap-10 lg:grid-cols-2 lg:gap-16">

            <!-- Left: heading + sub-copy -->
            <div class="flex flex-col justify-center">
                <h2 class="text-2xl font-bold text-gray-900 mb-3">Send us a message</h2>
                <p class="text-gray-500 text-sm leading-relaxed">
                    Fill in the form and we'll get back to you as soon as possible.
                </p>
            </div>

            <!-- Right: form -->
            <div>
                <!-- Success state -->
                <div
                    v-if="submitted"
                    class="rounded-2xl border border-green-200 bg-green-50 p-8 text-center"
                >
                    <div class="mb-3 text-3xl" aria-hidden="true">✓</div>
                    <p class="mb-1 font-semibold text-green-800">Message sent!</p>
                    <p class="text-sm text-green-700">We'll get back to you soon.</p>
                </div>

                <form v-else @submit.prevent="submit(preview)" novalidate class="space-y-4">
                    <div class="sr-only" aria-hidden="true">
                        <label for="cf-website">Website</label>
                        <input
                            id="cf-website"
                            v-model="honeypot"
                            type="text"
                            name="website"
                            tabindex="-1"
                            autocomplete="off"
                        />
                    </div>

                    <!-- Inline error -->
                    <div
                        v-if="submitError"
                        role="alert"
                        class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700"
                    >
                        {{ submitError }}
                    </div>

                    <div>
                        <label
                            for="cf-email"
                            class="mb-1.5 block text-sm font-medium text-gray-800"
                        >
                            Your email
                        </label>
                        <input
                            id="cf-email"
                            v-model="formEmail"
                            type="email"
                            autocomplete="email"
                            placeholder="yourname@example.com"
                            required
                            class="cf-input w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-900 transition focus:outline-none"
                        />
                    </div>

                    <div>
                        <label
                            for="cf-subject"
                            class="mb-1.5 block text-sm font-medium text-gray-800"
                        >
                            Subject
                        </label>
                        <input
                            id="cf-subject"
                            v-model="formSubject"
                            type="text"
                            placeholder="How can we help?"
                            required
                            class="cf-input w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-900 transition focus:outline-none"
                        />
                    </div>

                    <div>
                        <label
                            for="cf-message"
                            class="mb-1.5 block text-sm font-medium text-gray-800"
                        >
                            Your message
                        </label>
                        <textarea
                            id="cf-message"
                            v-model="formMessage"
                            rows="5"
                            placeholder="Tell us what you need…"
                            required
                            class="cf-input w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-900 transition focus:outline-none"
                        />
                    </div>

                    <button
                        type="submit"
                        :disabled="submitting"
                        class="inline-flex items-center gap-2 rounded-full px-6 py-3 text-sm font-semibold shadow-sm transition-opacity hover:opacity-90 disabled:opacity-60 focus:outline-none focus-visible:ring-2"
                        style="background-color: var(--site-primary); color: var(--site-primary-fg)"
                    >
                        {{ submitting ? 'Sending…' : 'Send message' }}
                        <Send class="h-4 w-4" />
                    </button>

                </form>
            </div>
        </div>
    </section>
</template>

<style scoped>
/* Focus ring driven by the site palette — can't use Tailwind here because
   ring-color needs to reference a CSS custom property at runtime. */
.cf-input:focus {
    border-color: var(--site-primary);
    box-shadow: 0 0 0 3px color-mix(in srgb, var(--site-primary) 20%, transparent);
}
</style>
