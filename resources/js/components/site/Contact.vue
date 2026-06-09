<script setup lang="ts">
import { computed, ref } from 'vue';
import { MapPin, Phone, Instagram, Facebook, Twitter, Linkedin, Send } from 'lucide-vue-next';

const props = defineProps({
    formattedAddress: {
        type: String,
        default: ''
    },
    phoneNumber: String,
    openingHours: {
        type: Array as () => Array<{day: string, open: string, close: string, closed: boolean}>,
        default: () => []
    },
    socials: {
        type: Object,
        default: () => ({})
    },
    contact: {
        type: String,
        default: ''
    },
    showForm: {
        type: Boolean,
        default: false
    },
    showPremiumTeaser: {
        type: Boolean,
        default: false
    },
    businessType: {
        type: String,
        default: ''
    },
    preview: {
        type: Boolean,
        default: false
    },
});

// Contact form state
const formEmail   = ref('');
const formSubject = ref('');
const formMessage = ref('');
const preferredContactTime = ref('');
const honeypot = ref('');
const submitting  = ref(false);
const submitted   = ref(false);
const submitError = ref('');

const submitForm = async () => {
    if (props.preview) {
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
                preferred_contact_time: preferredContactTime.value.trim(),
                website: honeypot.value,
            }),
        });

        if (!response.ok) {
            const data = await response.json();
            submitError.value = data.error ?? 'Something went wrong. Please try again.';
            return;
        }

        submitted.value = true;
        formEmail.value = '';
        formSubject.value = '';
        formMessage.value = '';
        preferredContactTime.value = '';
        honeypot.value = '';
    } catch {
        submitError.value = 'Failed to send your message. Please try again.';
    } finally {
        submitting.value = false;
    }
};

const weekdayDescriptions = computed(() => {
    return (props.openingHours ?? [])
        .filter((h: any) => !h.closed && h.open && h.close)
        .map((h: any) => {
            const fmt = (t: string) => {
                const [hStr, mStr] = (t || '00:00').split(':');
                const hr24 = parseInt(hStr);
                const min = parseInt(mStr);
                const ampm = hr24 >= 12 ? 'pm' : 'am';
                const hr12 = hr24 % 12 === 0 ? 12 : hr24 % 12;
                return `${hr12}${min > 0 ? `:${String(min).padStart(2, '0')}` : ''}${ampm}`;
            };
            return { day: h.day, hours: `${fmt(h.open)} – ${fmt(h.close)}` };
        });
});

const hasSocials = computed(() =>
    !!(props.socials?.instagram || props.socials?.facebook || props.socials?.x || props.socials?.linkedin)
);

const sectionLabel = computed(() => props.formattedAddress ? 'Find us' : 'Contact');

const appointmentBusinessKeywords = [
    'barber',
    'hair',
    'salon',
    'beauty',
    'nail',
    'spa',
    'massage',
    'tattoo',
    'physio',
    'therapy',
    'trainer',
    'fitness',
    'gym',
    'clinic',
    'dental',
];

const prefersAppointmentRequests = computed(() => {
    const businessType = props.businessType?.toLowerCase() ?? '';

    return appointmentBusinessKeywords.some((keyword) => businessType.includes(keyword));
});

const preferredTimePlaceholder = computed(() =>
    prefersAppointmentRequests.value
        ? 'e.g. Tuesday afternoon'
        : 'Optional — e.g. weekday mornings'
);
</script>

<template>
    <section class="py-14 text-white" style="background-color: var(--site-primary-dark)">
        <div class="mx-auto max-w-5xl px-6 md:px-14">

            <p class="mb-8 text-xs font-semibold uppercase tracking-widest" style="color: var(--site-secondary)">
                {{ sectionLabel }}
            </p>

            <div class="grid grid-cols-1 gap-10 md:grid-cols-3 md:gap-10">

                <!-- Address & Phone -->
                <div class="flex flex-col gap-5">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-white/60">Contact</h3>

                    <!-- Phone -->
                    <a
                        v-if="phoneNumber"
                        :href="'tel:' + phoneNumber"
                        class="inline-flex items-center gap-3 rounded-xl px-4 py-3.5 transition-colors hover:bg-white/10 -mx-4"
                    >
                        <span
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full"
                            style="background-color: var(--site-primary)"
                        >
                            <Phone class="h-5 w-5" style="color: var(--site-primary-fg)" />
                        </span>
                        <span class="pt-2 text-sm" style="color: rgba(255,255,255,0.65)">{{ phoneNumber }}</span>
                    </a>

                    <!-- Address -->
                    <div v-if="formattedAddress" class="flex items-start gap-3">
                        <span
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full"
                            style="background-color: var(--site-primary)"
                        >
                            <MapPin class="h-5 w-5" style="color: var(--site-primary-fg)" />
                        </span>
                        <a
                            :href="`https://www.google.com/maps/place/${encodeURIComponent(formattedAddress)}`"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="pt-2 text-sm leading-relaxed transition-colors"
                            style="color: rgba(255,255,255,0.65)"
                            @mouseover="($event.currentTarget as HTMLElement).style.color = 'white'"
                            @mouseleave="($event.currentTarget as HTMLElement).style.color = 'rgba(255,255,255,0.65)'"
                        >
                            {{ formattedAddress }}
                        </a>
                    </div>

                    <!-- Social links -->
                    <div v-if="hasSocials" class="mt-1 flex gap-2">
                        <a
                            v-if="socials?.instagram"
                            :href="socials.instagram"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="flex h-10 w-10 items-center justify-center rounded-full transition-opacity hover:opacity-80"
                            style="background-color: var(--site-primary)"
                            aria-label="Instagram"
                        >
                            <Instagram class="h-5 w-5" style="color: var(--site-primary-fg)" />
                        </a>
                        <a
                            v-if="socials?.facebook"
                            :href="socials.facebook"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="flex h-10 w-10 items-center justify-center rounded-full transition-opacity hover:opacity-80"
                            style="background-color: var(--site-primary)"
                            aria-label="Facebook"
                        >
                            <Facebook class="h-5 w-5" style="color: var(--site-primary-fg)" />
                        </a>
                        <a
                            v-if="socials?.x"
                            :href="socials.x"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="flex h-10 w-10 items-center justify-center rounded-full transition-opacity hover:opacity-80"
                            style="background-color: var(--site-primary)"
                            aria-label="X / Twitter"
                        >
                            <Twitter class="h-5 w-5" style="color: var(--site-primary-fg)" />
                        </a>
                        <a
                            v-if="socials?.linkedin"
                            :href="socials.linkedin"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="flex h-10 w-10 items-center justify-center rounded-full transition-opacity hover:opacity-80"
                            style="background-color: var(--site-primary)"
                            aria-label="LinkedIn"
                        >
                            <Linkedin class="h-5 w-5" style="color: var(--site-primary-fg)" />
                        </a>
                    </div>
                </div>

                <!-- Opening hours -->
                <div v-if="weekdayDescriptions.length" class="md:col-span-2">
                    <h3 class="mb-4 text-sm font-semibold uppercase tracking-wide text-white/60">
                        Opening hours
                    </h3>
                    <div class="flex flex-col gap-2">
                        <div
                            v-for="(entry, index) in weekdayDescriptions"
                            :key="index"
                            class="flex items-center justify-between gap-4"
                        >
                            <span class="w-28 shrink-0 text-sm" style="color: rgba(255,255,255,0.55)">{{ entry.day }}</span>
                            <span class="text-sm font-medium" style="color: rgba(255,255,255,0.9)">{{ entry.hours }}</span>
                        </div>
                    </div>
                </div>

            </div>

            <!-- ── Inline contact form ────────────────────────────────────── -->
            <template v-if="showForm && contact">
                <div class="mt-12 border-t border-white/12 pt-10">
                    <p class="mb-6 text-xs font-semibold uppercase tracking-widest" style="color: var(--site-secondary)">
                        Get in touch
                    </p>

                    <!-- Success state -->
                    <div
                        v-if="submitted"
                        id="contact-form"
                        class="rounded-2xl p-8 text-center"
                        style="background-color: rgba(255,255,255,0.08)"
                    >
                        <div class="mb-3 text-3xl" aria-hidden="true">✓</div>
                        <p class="mb-1 font-semibold text-white">Message sent!</p>
                        <p class="text-sm" style="color: rgba(255,255,255,0.65)">We'll get back to you soon.</p>
                    </div>

                    <form v-else id="contact-form" @submit.prevent="submitForm" novalidate class="space-y-5">
                        <div class="sr-only" aria-hidden="true">
                            <label for="contact-website">Website</label>
                            <input
                                id="contact-website"
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
                            class="rounded-lg border p-3 text-sm"
                            style="border-color: rgba(255,100,100,0.4); background-color: rgba(255,100,100,0.15); color: rgba(255,200,200,0.95)"
                        >
                            {{ submitError }}
                        </div>

                        <div class="grid gap-5 md:grid-cols-2">
                            <div>
                                <label for="cf-email" class="mb-1.5 block text-sm font-medium" style="color: rgba(255,255,255,0.75)">
                                    Your email
                                </label>
                                <input
                                    id="cf-email"
                                    v-model="formEmail"
                                    type="email"
                                    autocomplete="email"
                                    placeholder="yourname@example.com"
                                    required
                                    class="cf-input w-full rounded-lg px-3 py-2.5 text-sm text-white transition focus:outline-none"
                                    style="background-color: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2)"
                                />
                            </div>

                            <div>
                                <label for="cf-subject" class="mb-1.5 block text-sm font-medium" style="color: rgba(255,255,255,0.75)">
                                    Subject
                                </label>
                                <input
                                    id="cf-subject"
                                    v-model="formSubject"
                                    type="text"
                                    placeholder="How can we help?"
                                    required
                                    class="cf-input w-full rounded-lg px-3 py-2.5 text-sm text-white transition focus:outline-none"
                                    style="background-color: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2)"
                                />
                            </div>
                        </div>

                        <div>
                            <label
                                for="contact-preferred-time"
                                class="mb-1.5 block text-sm font-medium text-white"
                            >
                                Preferred date &amp; time
                                <span class="text-white/55">(optional)</span>
                            </label>
                            <input
                                id="contact-preferred-time"
                                v-model="preferredContactTime"
                                type="text"
                                :placeholder="preferredTimePlaceholder"
                                class="cf-input w-full rounded-xl border px-4 py-3 text-sm transition focus:outline-none"
                                style="border-color: rgba(255,255,255,0.14); background-color: rgba(255,255,255,0.08); color: white"
                            />
                        </div>

                        <div>
                            <label for="cf-message" class="mb-1.5 block text-sm font-medium" style="color: rgba(255,255,255,0.75)">
                                Your message
                            </label>
                            <textarea
                                id="cf-message"
                                v-model="formMessage"
                                rows="5"
                                placeholder="Tell us what you need…"
                                required
                                class="cf-input w-full rounded-lg px-3 py-2.5 text-sm text-white transition focus:outline-none"
                                style="background-color: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2)"
                            />
                        </div>

                        <div>
                            <button
                                type="submit"
                                :disabled="submitting"
                                class="inline-flex items-center gap-2 rounded-full px-6 py-3 text-sm font-semibold shadow-sm transition-opacity hover:opacity-90 disabled:opacity-60 focus:outline-none focus-visible:ring-2"
                                style="background-color: var(--site-primary); color: var(--site-primary-fg)"
                            >
                                {{ submitting ? 'Sending…' : 'Send message' }}
                                <Send class="h-4 w-4" />
                            </button>
                        </div>

                    </form>
                </div>
            </template>

            <!-- ── Premium contact form teaser (preview only) ─────────────── -->
            <template v-if="showPremiumTeaser && !showForm && contact">
                <div class="mt-12 border-t border-white/12 pt-10">
                    <p class="mb-6 text-xs font-semibold uppercase tracking-widest" style="color: var(--site-secondary)">
                        Get in touch
                    </p>

                    <!-- Premium badge + unlock message -->
                    <div class="flex items-start gap-3 rounded-xl px-4 py-3 mb-6" style="background-color: rgba(255,255,255,0.08)">
                        <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-bold bg-amber-400 text-amber-900 shrink-0 mt-0.5">
                            ✦ Premium
                        </span>
                        <p class="text-sm" style="color: rgba(255,255,255,0.75)">
                            Create your account and upgrade to Premium to let customers message you directly from your website.
                        </p>
                    </div>

                    <!-- Form preview — non-interactive (pointer-events disabled) -->
                    <div class="pointer-events-none opacity-60">
                        <div class="flex flex-col gap-4">
                            <div class="flex flex-col gap-1.5">
                                <label class="text-sm font-medium text-white/80">Your email</label>
                                <div class="h-11 rounded-xl px-4 flex items-center text-sm" style="background-color: rgba(255,255,255,0.08); color: rgba(255,255,255,0.35)">e.g. jane@example.com</div>
                            </div>
                            <div class="flex flex-col gap-1.5">
                                <label class="text-sm font-medium text-white/80">Subject</label>
                                <div class="h-11 rounded-xl px-4 flex items-center text-sm" style="background-color: rgba(255,255,255,0.08); color: rgba(255,255,255,0.35)">e.g. Appointment enquiry</div>
                            </div>
                            <div class="flex flex-col gap-1.5">
                                <label class="text-sm font-medium text-white/80">Message</label>
                                <div class="h-28 rounded-xl px-4 py-3 text-sm" style="background-color: rgba(255,255,255,0.08); color: rgba(255,255,255,0.35)">Your message…</div>
                            </div>
                            <div class="h-12 rounded-xl flex items-center justify-center text-sm font-semibold" style="background-color: var(--site-primary); color: var(--site-primary-fg)">
                                Send message
                            </div>
                        </div>
                    </div>
                </div>
            </template>

        </div>
    </section>
</template>

<style scoped>
.cf-input::placeholder {
    color: rgba(255, 255, 255, 0.35);
}
.cf-input:focus {
    border-color: var(--site-primary) !important;
    box-shadow: 0 0 0 3px color-mix(in srgb, var(--site-primary) 30%, transparent);
}
</style>
