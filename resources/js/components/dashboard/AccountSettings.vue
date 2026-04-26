<script setup lang="ts">
import { inject, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { User, Lock, LogOut, Trash2, CheckCircle2, Eye, EyeOff, Sparkles, ExternalLink, Globe, MailCheck, BarChart2, Check, ArrowRight } from 'lucide-vue-next';
import { toast } from 'vue-sonner';
import { update as profileUpdate, destroy as profileDestroy } from '@/routes/profile';
import { update as passwordUpdate } from '@/routes/password';
import { logout as logoutRoute } from '@/routes';
import billing from '@/routes/billing';

const isPremium = inject<boolean>('isPremium', false);

const checkoutUrl = billing.checkout.url();
const portalUrl   = billing.portal.url();

const props = defineProps<{
    userName: string;
    userEmail: string;
}>();

// ── Your details form ────────────────────────────────────────────────────────
const profileForm = useForm({
    name: props.userName,
    email: props.userEmail,
});

function saveProfile() {
    profileForm.patch(profileUpdate().url, {
        preserveScroll: true,
        onSuccess: () => toast.success('Details saved'),
    });
}

// ── Password form ────────────────────────────────────────────────────────────
const showPasswordForm = ref(false);
const showCurrentPw = ref(false);
const showNewPw = ref(false);
const showConfirmPw = ref(false);

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

function savePassword() {
    passwordForm.put(passwordUpdate().url, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Password changed');
            showPasswordForm.value = false;
            passwordForm.reset();
        },
    });
}

// ── Sign out ─────────────────────────────────────────────────────────────────
const logoutForm = useForm({});

function signOut() {
    logoutForm.post(logoutRoute().url);
}

// ── Close account ────────────────────────────────────────────────────────────
const showDeleteConfirm = ref(false);
const deleteForm = useForm({ password: '' });
const showDeletePw = ref(false);

function closeAccount() {
    deleteForm.delete(profileDestroy().url, {
        preserveScroll: true,
        onError: () => {
            toast.error('Incorrect password. Please try again.');
        },
    });
}
</script>

<template>
    <div class="flex flex-col gap-4 max-w-2xl">

        <!-- ── Your details ────────────────────────────────────────────── -->
        <div class="bg-white dark:bg-slate-900 border-[1.5px] border-[#dde1e8] dark:border-slate-700 rounded-lg overflow-hidden">
            <div class="flex items-center gap-3.5 px-6 py-5 border-b border-[#e8ecf1] dark:border-slate-700">
                <div class="w-10 h-10 rounded-lg bg-[#edf1f8] dark:bg-slate-800 flex items-center justify-center text-slate-700 dark:text-slate-300 flex-shrink-0">
                    <User :size="20" />
                </div>
                <div>
                    <div class="text-base font-bold text-slate-950 dark:text-white tracking-[-0.2px] leading-[1.2]">Your details</div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Your name and email address</div>
                </div>
            </div>

            <form @submit.prevent="saveProfile" class="px-6 py-5 flex flex-col gap-4">
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-semibold text-slate-700 dark:text-slate-300" for="acc-name">Full name</label>
                    <input
                        id="acc-name"
                        v-model="profileForm.name"
                        type="text"
                        class="h-11 px-3.5 border-[1.5px] border-[#dde1e8] dark:border-slate-600 rounded-lg font-inherit text-sm text-slate-950 dark:text-white bg-white dark:bg-slate-800 outline-none transition-all duration-150 focus:border-blue-600 dark:focus:border-blue-500 focus:ring-1 focus:ring-blue-100 dark:focus:ring-blue-900/30 w-full"
                        :class="{ 'border-red-600 dark:border-red-500': profileForm.errors.name }"
                        autocomplete="name"
                        required
                    />
                    <p v-if="profileForm.errors.name" class="text-xs text-red-600 dark:text-red-400 m-0">{{ profileForm.errors.name }}</p>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-semibold text-slate-700 dark:text-slate-300" for="acc-email">Email address</label>
                    <input
                        id="acc-email"
                        v-model="profileForm.email"
                        type="email"
                        class="h-11 px-3.5 border-[1.5px] border-[#dde1e8] dark:border-slate-600 rounded-lg font-inherit text-sm text-slate-950 dark:text-white bg-white dark:bg-slate-800 outline-none transition-all duration-150 focus:border-blue-600 dark:focus:border-blue-500 focus:ring-1 focus:ring-blue-100 dark:focus:ring-blue-900/30 w-full"
                        :class="{ 'border-red-600 dark:border-red-500': profileForm.errors.email }"
                        autocomplete="email"
                        required
                    />
                    <p v-if="profileForm.errors.email" class="text-xs text-red-600 dark:text-red-400 m-0">{{ profileForm.errors.email }}</p>
                </div>

                <div class="flex items-center gap-3">
                    <button type="submit" class="inline-flex items-center gap-2 h-11 px-5 rounded-lg bg-blue-600 text-white font-bold text-sm cursor-pointer transition-opacity duration-100 hover:opacity-90 disabled:opacity-60 disabled:cursor-not-allowed" :disabled="profileForm.processing">
                        <CheckCircle2 v-if="!profileForm.processing" :size="16" />
                        {{ profileForm.processing ? 'Saving…' : 'Save details' }}
                    </button>
                </div>
            </form>
        </div>

        <!-- ── Password ────────────────────────────────────────────────── -->
        <div class="bg-white dark:bg-slate-900 border-[1.5px] border-[#dde1e8] dark:border-slate-700 rounded-lg overflow-hidden">
            <div class="flex items-center gap-3.5 px-6 py-5 border-b border-[#e8ecf1] dark:border-slate-700">
                <div class="w-10 h-10 rounded-lg bg-[#edf1f8] dark:bg-slate-800 flex items-center justify-center text-slate-700 dark:text-slate-300 flex-shrink-0">
                    <Lock :size="20" />
                </div>
                <div>
                    <div class="text-base font-bold text-slate-950 dark:text-white tracking-[-0.2px] leading-[1.2]">Password</div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Change your sign-in password</div>
                </div>
            </div>

            <div v-if="!showPasswordForm" class="px-6 py-5">
                <button class="inline-flex items-center gap-2 h-11 px-5 rounded-lg bg-[#edf1f8] dark:bg-slate-800 text-slate-950 dark:text-white font-bold text-sm cursor-pointer border-[1.5px] border-[#dde1e8] dark:border-slate-600 transition-all duration-100 hover:bg-[#e8ecf1] dark:hover:bg-slate-700" @click="showPasswordForm = true">
                    Change password
                </button>
            </div>

            <form v-else @submit.prevent="savePassword" class="px-6 py-5 flex flex-col gap-4">
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-semibold text-slate-700 dark:text-slate-300" for="acc-cpw">Current password</label>
                    <div class="relative">
                        <input
                            id="acc-cpw"
                            v-model="passwordForm.current_password"
                            :type="showCurrentPw ? 'text' : 'password'"
                            class="h-11 px-3.5 pr-12 border-[1.5px] border-[#dde1e8] dark:border-slate-600 rounded-lg font-inherit text-sm text-slate-950 dark:text-white bg-white dark:bg-slate-800 outline-none transition-all duration-150 focus:border-blue-600 dark:focus:border-blue-500 focus:ring-1 focus:ring-blue-100 dark:focus:ring-blue-900/30 w-full"
                            :class="{ 'border-red-600 dark:border-red-500': passwordForm.errors.current_password }"
                            autocomplete="current-password"
                            required
                        />
                        <button type="button" class="absolute right-3 top-1/2 transform -translate-y-1/2 bg-transparent border-none cursor-pointer text-slate-500 dark:text-slate-400 p-1 flex items-center hover:text-slate-700 dark:hover:text-slate-300" @click="showCurrentPw = !showCurrentPw" tabindex="-1">
                            <component :is="showCurrentPw ? EyeOff : Eye" :size="18" />
                        </button>
                    </div>
                    <p v-if="passwordForm.errors.current_password" class="text-xs text-red-600 dark:text-red-400 m-0">{{ passwordForm.errors.current_password }}</p>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-semibold text-slate-700 dark:text-slate-300" for="acc-npw">New password</label>
                    <div class="relative">
                        <input
                            id="acc-npw"
                            v-model="passwordForm.password"
                            :type="showNewPw ? 'text' : 'password'"
                            class="h-11 px-3.5 pr-12 border-[1.5px] border-[#dde1e8] dark:border-slate-600 rounded-lg font-inherit text-sm text-slate-950 dark:text-white bg-white dark:bg-slate-800 outline-none transition-all duration-150 focus:border-blue-600 dark:focus:border-blue-500 focus:ring-1 focus:ring-blue-100 dark:focus:ring-blue-900/30 w-full"
                            :class="{ 'border-red-600 dark:border-red-500': passwordForm.errors.password }"
                            autocomplete="new-password"
                            required
                        />
                        <button type="button" class="absolute right-3 top-1/2 transform -translate-y-1/2 bg-transparent border-none cursor-pointer text-slate-500 dark:text-slate-400 p-1 flex items-center hover:text-slate-700 dark:hover:text-slate-300" @click="showNewPw = !showNewPw" tabindex="-1">
                            <component :is="showNewPw ? EyeOff : Eye" :size="18" />
                        </button>
                    </div>
                    <p v-if="passwordForm.errors.password" class="text-xs text-red-600 dark:text-red-400 m-0">{{ passwordForm.errors.password }}</p>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-semibold text-slate-700 dark:text-slate-300" for="acc-confpw">Confirm new password</label>
                    <div class="relative">
                        <input
                            id="acc-confpw"
                            v-model="passwordForm.password_confirmation"
                            :type="showConfirmPw ? 'text' : 'password'"
                            class="h-11 px-3.5 pr-12 border-[1.5px] border-[#dde1e8] dark:border-slate-600 rounded-lg font-inherit text-sm text-slate-950 dark:text-white bg-white dark:bg-slate-800 outline-none transition-all duration-150 focus:border-blue-600 dark:focus:border-blue-500 focus:ring-1 focus:ring-blue-100 dark:focus:ring-blue-900/30 w-full"
                            :class="{ 'border-red-600 dark:border-red-500': passwordForm.errors.password_confirmation }"
                            autocomplete="new-password"
                            required
                        />
                        <button type="button" class="absolute right-3 top-1/2 transform -translate-y-1/2 bg-transparent border-none cursor-pointer text-slate-500 dark:text-slate-400 p-1 flex items-center hover:text-slate-700 dark:hover:text-slate-300" @click="showConfirmPw = !showConfirmPw" tabindex="-1">
                            <component :is="showConfirmPw ? EyeOff : Eye" :size="18" />
                        </button>
                    </div>
                    <p v-if="passwordForm.errors.password_confirmation" class="text-xs text-red-600 dark:text-red-400 m-0">{{ passwordForm.errors.password_confirmation }}</p>
                </div>

                <div class="flex items-center gap-3">
                    <button type="submit" class="inline-flex items-center gap-2 h-11 px-5 rounded-lg bg-blue-600 text-white font-bold text-sm cursor-pointer transition-opacity duration-100 hover:opacity-90 disabled:opacity-60 disabled:cursor-not-allowed" :disabled="passwordForm.processing">
                        {{ passwordForm.processing ? 'Saving…' : 'Change password' }}
                    </button>
                    <button type="button" class="inline-flex items-center gap-2 h-11 px-5 rounded-lg bg-transparent text-slate-700 dark:text-slate-300 font-bold text-sm cursor-pointer hover:bg-[#edf1f8] dark:hover:bg-slate-800 transition-colors" @click="showPasswordForm = false; passwordForm.reset()">
                        Cancel
                    </button>
                </div>
            </form>
        </div>

        <!-- ── Your plan ───────────────────────────────────────────────── -->
        <div class="bg-white dark:bg-slate-900 border-[1.5px] border-[#dde1e8] dark:border-slate-700 rounded-lg overflow-hidden">
            <div class="flex items-center gap-3.5 px-6 py-5 border-b border-[#e8ecf1] dark:border-slate-700">
                <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0" :class="isPremium ? 'bg-[#FFF8E6] text-[#B07D00]' : 'bg-[#edf1f8] dark:bg-slate-800 text-slate-700 dark:text-slate-300'">
                    <Sparkles :size="20" />
                </div>
                <div>
                    <div class="text-base font-bold text-slate-950 dark:text-white tracking-[-0.2px] leading-[1.2]">Your plan</div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">What's included in your subscription</div>
                </div>
            </div>

            <!-- ── Pro / active ─────────────────────────────────────── -->
            <div v-if="isPremium" class="px-6 py-5">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex flex-col gap-2">
                        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-[#FFF8E6] text-[#B07D00] text-xs font-bold w-fit">
                            <Sparkles :size="13" />
                            321Sites Pro
                        </div>
                        <p class="text-sm text-slate-500 dark:text-slate-400 m-0">All premium features are active on your site.</p>
                        <ul class="list-none p-0 m-1 mt-1 flex flex-col gap-1">
                            <li class="flex items-center gap-1.75 text-sm text-slate-700 dark:text-slate-300"><Check :size="14" class="text-green-500 flex-shrink-0" />Custom domain</li>
                            <li class="flex items-center gap-1.75 text-sm text-slate-700 dark:text-slate-300"><Check :size="14" class="text-green-500 flex-shrink-0" />Contact form</li>
                            <li class="flex items-center gap-1.75 text-sm text-slate-700 dark:text-slate-300"><Check :size="14" class="text-green-500 flex-shrink-0" />Google Analytics</li>
                            <li class="flex items-center gap-1.75 text-sm text-slate-700 dark:text-slate-300"><Check :size="14" class="text-green-500 flex-shrink-0" />Priority support</li>
                        </ul>
                    </div>
                    <a :href="portalUrl" class="inline-flex items-center gap-1.5 h-9.5 px-4 rounded-lg bg-[#edf1f8] dark:bg-slate-800 text-slate-950 dark:text-white font-bold text-xs cursor-pointer border-[1.5px] border-[#dde1e8] dark:border-slate-600 transition-all duration-100 hover:bg-[#e8ecf1] dark:hover:bg-slate-700 flex-shrink-0">
                        Manage <ExternalLink :size="14" />
                    </a>
                </div>
            </div>

            <!-- ── Free / upsell ────────────────────────────────────── -->
            <div v-else class="px-6 py-5">
                <div class="flex flex-col gap-2.5 mb-4">
                    <span class="inline-flex items-center px-3 py-1 rounded-full bg-[#edf1f8] dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-bold w-fit">Free plan</span>
                    <p class="text-sm text-slate-500 dark:text-slate-400 leading-[1.6] m-0">Your site is live with everything you need to get started.</p>
                </div>

                <div class="border-[1.5px] border-[#F0E6C8] rounded-lg bg-gradient-to-br from-[#FFFDF5] to-[#FFF8E6] p-5 flex flex-col gap-4">
                    <div class="flex flex-col gap-1.5">
                        <div class="inline-flex items-center gap-1 px-2.5 py-0.75 rounded-full bg-slate-950 text-[#F6D860] text-xs font-black tracking-0.5px uppercase w-fit">
                            <Sparkles :size="12" />
                            Upgrade to Pro
                        </div>
                        <p class="text-base font-bold text-slate-950 m-0 tracking-[-0.2px]">Take your site to the next level</p>
                        <p class="text-sm text-slate-700 m-0 leading-[1.5]">Unlock powerful features that help you stand out and convert more visitors.</p>
                    </div>
                    <ul class="list-none p-0 m-0 flex flex-col gap-3">
                        <li class="flex items-start gap-3">
                            <span class="w-7.5 h-7.5 rounded-lg bg-[#FFF0C0] text-[#B07D00] flex items-center justify-center flex-shrink-0 mt-0.5"><Globe :size="15" /></span>
                            <div class="flex flex-col gap-0.5">
                                <strong class="text-sm font-bold text-slate-950">Custom domain</strong>
                                <span class="text-xs text-slate-700 leading-[1.4]">Use your own domain (e.g. yourname.com) instead of a subdomain</span>
                            </div>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="w-7.5 h-7.5 rounded-lg bg-[#FFF0C0] text-[#B07D00] flex items-center justify-center flex-shrink-0 mt-0.5"><MailCheck :size="15" /></span>
                            <div class="flex flex-col gap-0.5">
                                <strong class="text-sm font-bold text-slate-950">Contact form</strong>
                                <span class="text-xs text-slate-700 leading-[1.4]">Let customers reach you directly — no third-party tools needed</span>
                            </div>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="w-7.5 h-7.5 rounded-lg bg-[#FFF0C0] text-[#B07D00] flex items-center justify-center flex-shrink-0 mt-0.5"><BarChart2 :size="15" /></span>
                            <div class="flex flex-col gap-0.5">
                                <strong class="text-sm font-bold text-slate-950">Google Analytics</strong>
                                <span class="text-xs text-slate-700 leading-[1.4]">See who's visiting your site and where they're coming from</span>
                            </div>
                        </li>
                    </ul>
                    <a :href="checkoutUrl" class="inline-flex items-center justify-center gap-2 w-full h-12 rounded-lg bg-slate-950 text-[#F6D860] font-bold text-sm cursor-pointer border-none transition-opacity duration-150 hover:opacity-88 mt-1">
                        Upgrade to Pro <ArrowRight :size="16" />
                    </a>
                </div>
            </div>
        </div>

        <!-- ── Sign out ────────────────────────────────────────────────── -->
        <div class="bg-white dark:bg-slate-900 border-[1.5px] border-[#dde1e8] dark:border-slate-700 rounded-lg overflow-hidden">
            <div class="flex items-center gap-3.5 px-6 py-5 border-b border-[#e8ecf1] dark:border-slate-700">
                <div class="w-10 h-10 rounded-lg bg-[#edf1f8] dark:bg-slate-800 flex items-center justify-center text-slate-700 dark:text-slate-300 flex-shrink-0">
                    <LogOut :size="20" />
                </div>
                <div>
                    <div class="text-base font-bold text-slate-950 dark:text-white tracking-[-0.2px] leading-[1.2]">Sign out</div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Sign out of your account on this device</div>
                </div>
            </div>

            <div class="px-6 py-5">
                <button
                    class="inline-flex items-center gap-2 h-11 px-5 rounded-lg bg-[#edf1f8] dark:bg-slate-800 text-slate-950 dark:text-white font-bold text-sm cursor-pointer border-[1.5px] border-[#dde1e8] dark:border-slate-600 transition-all duration-100 hover:bg-[#e8ecf1] dark:hover:bg-slate-700 disabled:opacity-60 disabled:cursor-not-allowed"
                    :disabled="logoutForm.processing"
                    @click="signOut"
                >
                    {{ logoutForm.processing ? 'Signing out…' : 'Sign out' }}
                </button>
            </div>
        </div>

        <!-- ── Close account ───────────────────────────────────────────── -->
        <div class="bg-white dark:bg-slate-900 border-[1.5px] border-[#F5C6C4] dark:border-slate-700 rounded-lg overflow-hidden">
            <div class="flex items-center gap-3.5 px-6 py-5 border-b border-[#e8ecf1] dark:border-slate-700">
                <div class="w-10 h-10 rounded-lg bg-[#FDECEA] text-red-700 dark:text-red-400 flex items-center justify-center flex-shrink-0">
                    <Trash2 :size="20" />
                </div>
                <div>
                    <div class="text-base font-bold text-red-700 dark:text-red-400 tracking-[-0.2px] leading-[1.2]">Close account</div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Permanently delete your account and your website</div>
                </div>
            </div>

            <div class="px-6 py-5">
                <p class="text-sm text-slate-700 dark:text-slate-300 leading-[1.6] m-0 mb-4">
                    This will permanently delete your account, your website, and all your data. <strong>This cannot be undone.</strong>
                </p>

                <button
                    v-if="!showDeleteConfirm"
                    class="inline-flex items-center gap-2 h-11 px-5 rounded-lg bg-red-600 text-white font-bold text-sm cursor-pointer border-none transition-opacity duration-100 hover:opacity-90"
                    @click="showDeleteConfirm = true"
                >
                    Close my account
                </button>

                <form v-else @submit.prevent="closeAccount" class="flex flex-col gap-4">
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-semibold text-slate-700 dark:text-slate-300" for="acc-delpw">Enter your password to confirm</label>
                        <div class="relative">
                            <input
                                id="acc-delpw"
                                v-model="deleteForm.password"
                                :type="showDeletePw ? 'text' : 'password'"
                                class="h-11 px-3.5 pr-12 border-[1.5px] border-[#dde1e8] dark:border-slate-600 rounded-lg font-inherit text-sm text-slate-950 dark:text-white bg-white dark:bg-slate-800 outline-none transition-all duration-150 focus:border-blue-600 dark:focus:border-blue-500 focus:ring-1 focus:ring-blue-100 dark:focus:ring-blue-900/30 w-full"
                                :class="{ 'border-red-600 dark:border-red-500': deleteForm.errors.password }"
                                autocomplete="current-password"
                                required
                            />
                            <button type="button" class="absolute right-3 top-1/2 transform -translate-y-1/2 bg-transparent border-none cursor-pointer text-slate-500 dark:text-slate-400 p-1 flex items-center hover:text-slate-700 dark:hover:text-slate-300" @click="showDeletePw = !showDeletePw" tabindex="-1">
                                <component :is="showDeletePw ? EyeOff : Eye" :size="18" />
                            </button>
                        </div>
                        <p v-if="deleteForm.errors.password" class="text-xs text-red-600 dark:text-red-400 m-0">{{ deleteForm.errors.password }}</p>
                    </div>

                    <div class="flex items-center gap-3">
                        <button type="submit" class="inline-flex items-center gap-2 h-11 px-5 rounded-lg bg-red-600 text-white font-bold text-sm cursor-pointer border-none transition-opacity duration-100 hover:opacity-90 disabled:opacity-60 disabled:cursor-not-allowed" :disabled="deleteForm.processing">
                            {{ deleteForm.processing ? 'Deleting…' : 'Yes, delete everything' }}
                        </button>
                        <button type="button" class="inline-flex items-center gap-2 h-11 px-5 rounded-lg bg-transparent text-slate-700 dark:text-slate-300 font-bold text-sm cursor-pointer hover:bg-[#edf1f8] dark:hover:bg-slate-800 transition-colors" @click="showDeleteConfirm = false; deleteForm.reset()">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</template>
