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
    <div class="acc-wrap">

        <!-- ── Your details ────────────────────────────────────────────── -->
        <div class="acc-card">
            <div class="acc-card__head">
                <div class="acc-card__icon">
                    <User :size="20" />
                </div>
                <div>
                    <div class="acc-card__title">Your details</div>
                    <div class="acc-card__sub">Your name and email address</div>
                </div>
            </div>

            <form @submit.prevent="saveProfile" class="acc-form">
                <div class="acc-field">
                    <label class="acc-label" for="acc-name">Full name</label>
                    <input
                        id="acc-name"
                        v-model="profileForm.name"
                        type="text"
                        class="acc-input"
                        :class="{ 'acc-input--error': profileForm.errors.name }"
                        autocomplete="name"
                        required
                    />
                    <p v-if="profileForm.errors.name" class="acc-error">{{ profileForm.errors.name }}</p>
                </div>

                <div class="acc-field">
                    <label class="acc-label" for="acc-email">Email address</label>
                    <input
                        id="acc-email"
                        v-model="profileForm.email"
                        type="email"
                        class="acc-input"
                        :class="{ 'acc-input--error': profileForm.errors.email }"
                        autocomplete="email"
                        required
                    />
                    <p v-if="profileForm.errors.email" class="acc-error">{{ profileForm.errors.email }}</p>
                </div>

                <div class="acc-form-footer">
                    <button type="submit" class="acc-btn acc-btn--primary" :disabled="profileForm.processing">
                        <CheckCircle2 v-if="!profileForm.processing" :size="16" />
                        {{ profileForm.processing ? 'Saving…' : 'Save details' }}
                    </button>
                </div>
            </form>
        </div>

        <!-- ── Password ────────────────────────────────────────────────── -->
        <div class="acc-card">
            <div class="acc-card__head">
                <div class="acc-card__icon">
                    <Lock :size="20" />
                </div>
                <div>
                    <div class="acc-card__title">Password</div>
                    <div class="acc-card__sub">Change your sign-in password</div>
                </div>
            </div>

            <div v-if="!showPasswordForm" class="acc-card-body">
                <button class="acc-btn acc-btn--secondary" @click="showPasswordForm = true">
                    Change password
                </button>
            </div>

            <form v-else @submit.prevent="savePassword" class="acc-form">
                <div class="acc-field">
                    <label class="acc-label" for="acc-cpw">Current password</label>
                    <div class="acc-pw-wrap">
                        <input
                            id="acc-cpw"
                            v-model="passwordForm.current_password"
                            :type="showCurrentPw ? 'text' : 'password'"
                            class="acc-input acc-input--pw"
                            :class="{ 'acc-input--error': passwordForm.errors.current_password }"
                            autocomplete="current-password"
                            required
                        />
                        <button type="button" class="acc-pw-toggle" @click="showCurrentPw = !showCurrentPw" tabindex="-1">
                            <component :is="showCurrentPw ? EyeOff : Eye" :size="18" />
                        </button>
                    </div>
                    <p v-if="passwordForm.errors.current_password" class="acc-error">{{ passwordForm.errors.current_password }}</p>
                </div>

                <div class="acc-field">
                    <label class="acc-label" for="acc-npw">New password</label>
                    <div class="acc-pw-wrap">
                        <input
                            id="acc-npw"
                            v-model="passwordForm.password"
                            :type="showNewPw ? 'text' : 'password'"
                            class="acc-input acc-input--pw"
                            :class="{ 'acc-input--error': passwordForm.errors.password }"
                            autocomplete="new-password"
                            required
                        />
                        <button type="button" class="acc-pw-toggle" @click="showNewPw = !showNewPw" tabindex="-1">
                            <component :is="showNewPw ? EyeOff : Eye" :size="18" />
                        </button>
                    </div>
                    <p v-if="passwordForm.errors.password" class="acc-error">{{ passwordForm.errors.password }}</p>
                </div>

                <div class="acc-field">
                    <label class="acc-label" for="acc-confpw">Confirm new password</label>
                    <div class="acc-pw-wrap">
                        <input
                            id="acc-confpw"
                            v-model="passwordForm.password_confirmation"
                            :type="showConfirmPw ? 'text' : 'password'"
                            class="acc-input acc-input--pw"
                            :class="{ 'acc-input--error': passwordForm.errors.password_confirmation }"
                            autocomplete="new-password"
                            required
                        />
                        <button type="button" class="acc-pw-toggle" @click="showConfirmPw = !showConfirmPw" tabindex="-1">
                            <component :is="showConfirmPw ? EyeOff : Eye" :size="18" />
                        </button>
                    </div>
                    <p v-if="passwordForm.errors.password_confirmation" class="acc-error">{{ passwordForm.errors.password_confirmation }}</p>
                </div>

                <div class="acc-form-footer">
                    <button type="submit" class="acc-btn acc-btn--primary" :disabled="passwordForm.processing">
                        {{ passwordForm.processing ? 'Saving…' : 'Change password' }}
                    </button>
                    <button type="button" class="acc-btn acc-btn--ghost" @click="showPasswordForm = false; passwordForm.reset()">
                        Cancel
                    </button>
                </div>
            </form>
        </div>

        <!-- ── Your plan ───────────────────────────────────────────────── -->
        <div class="acc-card">
            <div class="acc-card__head">
                <div class="acc-card__icon" :class="isPremium ? 'acc-card__icon--pro' : ''">
                    <Sparkles :size="20" />
                </div>
                <div>
                    <div class="acc-card__title">Your plan</div>
                    <div class="acc-card__sub">What's included in your subscription</div>
                </div>
            </div>

            <!-- ── Pro / active ─────────────────────────────────────── -->
            <div v-if="isPremium" class="acc-card-body">
                <div class="acc-pro-active">
                    <div class="acc-pro-active__left">
                        <div class="acc-pro-active__badge">
                            <Sparkles :size="13" />
                            321Sites Pro
                        </div>
                        <p class="acc-pro-active__copy">All premium features are active on your site.</p>
                        <ul class="acc-pro-active__list">
                            <li><Check :size="14" />Custom domain</li>
                            <li><Check :size="14" />Contact form</li>
                            <li><Check :size="14" />Google Analytics</li>
                            <li><Check :size="14" />Priority support</li>
                        </ul>
                    </div>
                    <a :href="portalUrl" class="acc-btn acc-btn--secondary acc-btn--manage">
                        Manage <ExternalLink :size="14" />
                    </a>
                </div>
            </div>

            <!-- ── Free / upsell ────────────────────────────────────── -->
            <div v-else class="acc-card-body acc-card-body--upsell">
                <div class="acc-plan-row">
                    <span class="acc-plan-badge">Free plan</span>
                    <p class="acc-plan-desc">Your site is live with everything you need to get started.</p>
                </div>

                <div class="acc-upsell">
                    <div class="acc-upsell__header">
                        <div class="acc-upsell__badge">
                            <Sparkles :size="12" />
                            Upgrade to Pro
                        </div>
                        <p class="acc-upsell__headline">Take your site to the next level</p>
                        <p class="acc-upsell__sub">Unlock powerful features that help you stand out and convert more visitors.</p>
                    </div>
                    <ul class="acc-upsell__features">
                        <li>
                            <span class="acc-upsell__feat-icon"><Globe :size="15" /></span>
                            <div>
                                <strong>Custom domain</strong>
                                <span>Use your own domain (e.g. yourname.com) instead of a subdomain</span>
                            </div>
                        </li>
                        <li>
                            <span class="acc-upsell__feat-icon"><MailCheck :size="15" /></span>
                            <div>
                                <strong>Contact form</strong>
                                <span>Let customers reach you directly — no third-party tools needed</span>
                            </div>
                        </li>
                        <li>
                            <span class="acc-upsell__feat-icon"><BarChart2 :size="15" /></span>
                            <div>
                                <strong>Google Analytics</strong>
                                <span>See who's visiting your site and where they're coming from</span>
                            </div>
                        </li>
                    </ul>
                    <a :href="checkoutUrl" class="acc-btn acc-btn--upgrade">
                        Upgrade to Pro <ArrowRight :size="16" />
                    </a>
                </div>
            </div>
        </div>

        <!-- ── Sign out ────────────────────────────────────────────────── -->
        <div class="acc-card">
            <div class="acc-card__head">
                <div class="acc-card__icon">
                    <LogOut :size="20" />
                </div>
                <div>
                    <div class="acc-card__title">Sign out</div>
                    <div class="acc-card__sub">Sign out of your account on this device</div>
                </div>
            </div>

            <div class="acc-card-body">
                <button
                    class="acc-btn acc-btn--secondary"
                    :disabled="logoutForm.processing"
                    @click="signOut"
                >
                    {{ logoutForm.processing ? 'Signing out…' : 'Sign out' }}
                </button>
            </div>
        </div>

        <!-- ── Close account ───────────────────────────────────────────── -->
        <div class="acc-card acc-card--danger">
            <div class="acc-card__head">
                <div class="acc-card__icon acc-card__icon--danger">
                    <Trash2 :size="20" />
                </div>
                <div>
                    <div class="acc-card__title acc-card__title--danger">Close account</div>
                    <div class="acc-card__sub">Permanently delete your account and your website</div>
                </div>
            </div>

            <div class="acc-card-body">
                <p class="acc-danger-note">
                    This will permanently delete your account, your website, and all your data. <strong>This cannot be undone.</strong>
                </p>

                <button
                    v-if="!showDeleteConfirm"
                    class="acc-btn acc-btn--danger"
                    @click="showDeleteConfirm = true"
                >
                    Close my account
                </button>

                <form v-else @submit.prevent="closeAccount" class="acc-form">
                    <div class="acc-field">
                        <label class="acc-label" for="acc-delpw">Enter your password to confirm</label>
                        <div class="acc-pw-wrap">
                            <input
                                id="acc-delpw"
                                v-model="deleteForm.password"
                                :type="showDeletePw ? 'text' : 'password'"
                                class="acc-input acc-input--pw"
                                :class="{ 'acc-input--error': deleteForm.errors.password }"
                                autocomplete="current-password"
                                required
                            />
                            <button type="button" class="acc-pw-toggle" @click="showDeletePw = !showDeletePw" tabindex="-1">
                                <component :is="showDeletePw ? EyeOff : Eye" :size="18" />
                            </button>
                        </div>
                        <p v-if="deleteForm.errors.password" class="acc-error">{{ deleteForm.errors.password }}</p>
                    </div>

                    <div class="acc-form-footer">
                        <button type="submit" class="acc-btn acc-btn--danger" :disabled="deleteForm.processing">
                            {{ deleteForm.processing ? 'Deleting…' : 'Yes, delete everything' }}
                        </button>
                        <button type="button" class="acc-btn acc-btn--ghost" @click="showDeleteConfirm = false; deleteForm.reset()">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</template>

<style scoped>
/* ── Wrapper ────────────────────────────────────────────────────────────── */
.acc-wrap {
    display: flex;
    flex-direction: column;
    gap: 16px;
    max-width: 680px;
}

/* ── Card ────────────────────────────────────────────────────────────────── */
.acc-card {
    background: var(--db-surface);
    border: 1.5px solid var(--db-line);
    border-radius: 14px;
    overflow: hidden;
}

.acc-card--danger {
    border-color: #F5C6C4;
}

.acc-card__head {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 20px 24px;
    border-bottom: 1px solid var(--db-line-soft);
}

.acc-card__icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: var(--db-panel);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--db-ink-mid);
    flex-shrink: 0;
}

.acc-card__icon--danger {
    background: #FDECEA;
    color: var(--db-danger);
}

.acc-card__title {
    font-size: 17px;
    font-weight: 700;
    color: var(--db-ink);
    letter-spacing: -0.2px;
    line-height: 1.2;
}

.acc-card__title--danger {
    color: var(--db-danger);
}

.acc-card__sub {
    font-size: 13px;
    color: var(--db-ink-soft);
    margin-top: 2px;
}

.acc-card-body {
    padding: 20px 24px;
}

/* ── Form ────────────────────────────────────────────────────────────────── */
.acc-form {
    padding: 20px 24px;
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.acc-field {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.acc-label {
    font-size: 14px;
    font-weight: 600;
    color: var(--db-ink-mid);
}

.acc-input {
    height: 46px;
    padding: 0 14px;
    border: 1.5px solid var(--db-line);
    border-radius: 10px;
    font-family: inherit;
    font-size: 15px;
    color: var(--db-ink);
    background: var(--db-surface);
    outline: none;
    transition: border-color 0.15s ease;
    width: 100%;
}

.acc-input:focus {
    border-color: var(--db-accent);
}

.acc-input--error {
    border-color: var(--db-danger);
}

.acc-input--pw {
    padding-right: 48px;
}

.acc-pw-wrap {
    position: relative;
}

.acc-pw-toggle {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    background: transparent;
    border: none;
    cursor: pointer;
    color: var(--db-ink-soft);
    padding: 4px;
    display: flex;
    align-items: center;
}

.acc-error {
    font-size: 13px;
    color: var(--db-danger);
    margin: 0;
}

.acc-form-footer {
    display: flex;
    align-items: center;
    gap: 12px;
}

/* ── Buttons ─────────────────────────────────────────────────────────────── */
.acc-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    height: 44px;
    padding: 0 20px;
    border-radius: 10px;
    font-family: inherit;
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
    border: none;
    transition: opacity 0.1s ease, background 0.1s ease;
}

.acc-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.acc-btn--primary {
    background: var(--db-accent);
    color: var(--db-accent-fg);
}

.acc-btn--primary:hover:not(:disabled) {
    opacity: 0.9;
}

.acc-btn--secondary {
    background: var(--db-panel);
    color: var(--db-ink);
    border: 1.5px solid var(--db-line);
}

.acc-btn--secondary:hover:not(:disabled) {
    background: var(--db-line-soft);
}

.acc-btn--ghost {
    background: transparent;
    color: var(--db-ink-mid);
}

.acc-btn--ghost:hover:not(:disabled) {
    background: var(--db-panel);
}

.acc-btn--danger {
    background: var(--db-danger);
    color: #FFFFFF;
}

.acc-btn--danger:hover:not(:disabled) {
    opacity: 0.9;
}

/* ── Plan section ────────────────────────────────────────────────────────── */
.acc-card__icon--pro {
    background: #FFF8E6;
    color: #B07D00;
}

/* Free plan row */
.acc-plan-row {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-bottom: 16px;
}

.acc-plan-badge {
    display: inline-flex;
    align-items: center;
    padding: 4px 12px;
    border-radius: 100px;
    background: var(--db-panel);
    color: var(--db-ink-mid);
    font-size: 13px;
    font-weight: 700;
    align-self: flex-start;
}

.acc-plan-desc {
    font-size: 15px;
    color: var(--db-ink-soft);
    line-height: 1.6;
    margin: 0;
}

.acc-card-body--upsell {
    padding-bottom: 0;
}

/* Pro active state */
.acc-pro-active {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
}

.acc-pro-active__left {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.acc-pro-active__badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px;
    border-radius: 100px;
    background: #FFF8E6;
    color: #B07D00;
    font-size: 13px;
    font-weight: 700;
    align-self: flex-start;
}

.acc-pro-active__copy {
    font-size: 15px;
    color: var(--db-ink-soft);
    margin: 0;
}

.acc-pro-active__list {
    list-style: none;
    padding: 0;
    margin: 4px 0 0;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.acc-pro-active__list li {
    display: flex;
    align-items: center;
    gap: 7px;
    font-size: 14px;
    color: var(--db-ink-mid);
}

.acc-pro-active__list li svg {
    color: #22C55E;
    flex-shrink: 0;
}

.acc-btn--manage {
    flex-shrink: 0;
    gap: 6px;
    font-size: 14px;
    height: 38px;
    padding: 0 16px;
}

/* Upsell card */
.acc-upsell {
    border: 1.5px solid #F0E6C8;
    border-radius: 12px;
    background: linear-gradient(160deg, #FFFDF5 0%, #FFF8E6 100%);
    padding: 20px;
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.acc-upsell__header {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.acc-upsell__badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3px 10px;
    border-radius: 100px;
    background: #111418;
    color: #F6D860;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    align-self: flex-start;
}

.acc-upsell__headline {
    font-size: 17px;
    font-weight: 700;
    color: var(--db-ink);
    margin: 4px 0 0;
    letter-spacing: -0.2px;
}

.acc-upsell__sub {
    font-size: 14px;
    color: var(--db-ink-soft);
    margin: 0;
    line-height: 1.5;
}

.acc-upsell__features {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.acc-upsell__features li {
    display: flex;
    align-items: flex-start;
    gap: 12px;
}

.acc-upsell__feat-icon {
    width: 30px;
    height: 30px;
    border-radius: 8px;
    background: #FFF0C0;
    color: #B07D00;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    margin-top: 1px;
}

.acc-upsell__features li div {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.acc-upsell__features li strong {
    font-size: 14px;
    font-weight: 700;
    color: var(--db-ink);
}

.acc-upsell__features li span {
    font-size: 13px;
    color: var(--db-ink-soft);
    line-height: 1.4;
}

.acc-btn--upgrade {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: 100%;
    height: 48px;
    border-radius: 12px;
    background: #111418;
    color: #F6D860;
    font-family: inherit;
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
    border: none;
    text-decoration: none;
    transition: opacity 0.15s ease;
    margin-top: 4px;
}

.acc-btn--upgrade:hover {
    opacity: 0.88;
}

/* ── Danger note ─────────────────────────────────────────────────────────── */
.acc-danger-note {
    font-size: 14px;
    color: var(--db-ink-mid);
    line-height: 1.6;
    margin: 0 0 16px;
}
</style>
