<script setup lang="ts">
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { Inbox, Mail, MailOpen, Clock, ChevronRight, ArrowLeft, User } from 'lucide-vue-next';
import { read as submissionRead } from '@/routes/submissions';

interface Submission {
    id: number;
    email: string;
    subject: string | null;
    message: string;
    preferred_contact_time: string | null;
    read_at: string | null;
    created_at: string;
}

const props = defineProps<{
    submissions: Submission[] | null | undefined;
}>();

const selectedId = ref<number | null>(null);

const list = computed<Submission[]>(() => (props.submissions as Submission[]) ?? []);

const selected = computed<Submission | null>(() =>
    selectedId.value !== null
        ? (list.value.find((s) => s.id === selectedId.value) ?? null)
        : null,
);

function openMessage(sub: Submission) {
    selectedId.value = sub.id;
    if (!sub.read_at) {
        router.post(submissionRead(sub.id).url, {}, {
            preserveScroll: true,
            preserveState: true,
        });
    }
}

function formatDate(iso: string): string {
    const d = new Date(iso);
    const now = new Date();
    const diffMs = now.getTime() - d.getTime();
    const diffDays = Math.floor(diffMs / 86400000);
    if (diffDays === 0) {
        return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }
    if (diffDays === 1) return 'Yesterday';
    if (diffDays < 7) return d.toLocaleDateString([], { weekday: 'short' });
    return d.toLocaleDateString([], { day: 'numeric', month: 'short' });
}

function formatDateFull(iso: string): string {
    return new Date(iso).toLocaleDateString([], { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}

const unreadCount = computed(() => list.value.filter((s) => !s.read_at).length);
</script>

<template>
    <div class="msg-wrap">
        <!-- Empty state -->
        <div v-if="list.length === 0" class="msg-empty">
            <div class="msg-empty__icon">
                <Inbox :size="40" />
            </div>
            <h2 class="msg-empty__title">No messages yet</h2>
            <p class="msg-empty__sub">When someone fills in the contact form on your site, their message will appear here.</p>
        </div>

        <!-- Inbox layout -->
        <template v-else>
            <!-- Header bar -->
            <div class="msg-header">
                <div class="msg-header__left">
                    <h2 class="msg-header__title">Inbox</h2>
                    <span v-if="unreadCount > 0" class="msg-unread-pill">{{ unreadCount }} unread</span>
                </div>
            </div>

            <!-- Two-panel -->
            <div class="msg-panels">
                <!-- List panel -->
                <div class="msg-list-panel" :class="{ 'msg-list-panel--hidden-mobile': selectedId !== null }">
                    <div
                        v-for="sub in list"
                        :key="sub.id"
                        class="msg-row"
                        :class="{
                            'msg-row--active': selectedId === sub.id,
                            'msg-row--unread': !sub.read_at,
                        }"
                        @click="openMessage(sub)"
                    >
                        <!-- Avatar -->
                        <div class="msg-row__avatar">
                            <User :size="18" />
                        </div>
                        <!-- Content -->
                        <div class="msg-row__body">
                            <div class="msg-row__top">
                                <span class="msg-row__email">{{ sub.email }}</span>
                                <span class="msg-row__date">{{ formatDate(sub.created_at) }}</span>
                            </div>
                            <div class="msg-row__subject">{{ sub.subject || '(No subject)' }}</div>
                            <div class="msg-row__preview">{{ sub.message }}</div>
                        </div>
                        <!-- Unread dot -->
                        <div v-if="!sub.read_at" class="msg-row__dot" />
                        <!-- Chevron -->
                        <ChevronRight :size="16" class="msg-row__chevron" />
                    </div>
                </div>

                <!-- Detail panel -->
                <div
                    class="msg-detail-panel"
                    :class="{ 'msg-detail-panel--visible-mobile': selectedId !== null }"
                >
                    <!-- No message selected -->
                    <div v-if="!selected" class="msg-detail-empty">
                        <MailOpen :size="36" />
                        <p>Select a message to read it</p>
                    </div>

                    <!-- Message detail -->
                    <template v-else>
                        <button class="msg-back-btn" @click="selectedId = null">
                            <ArrowLeft :size="16" />
                            Back to inbox
                        </button>

                        <div class="msg-detail-card">
                            <!-- From row -->
                            <div class="msg-detail-from">
                                <div class="msg-detail-avatar">
                                    <User :size="22" />
                                </div>
                                <div class="msg-detail-meta">
                                    <div class="msg-detail-email">{{ selected.email }}</div>
                                    <div class="msg-detail-ts">{{ formatDateFull(selected.created_at) }}</div>
                                </div>
                                <span v-if="selected.read_at" class="msg-read-badge">
                                    <MailOpen :size="13" />
                                    Read
                                </span>
                                <span v-else class="msg-unread-badge">
                                    <Mail :size="13" />
                                    Unread
                                </span>
                            </div>

                            <!-- Subject -->
                            <h3 class="msg-detail-subject">{{ selected.subject || '(No subject)' }}</h3>

                            <!-- Message body -->
                            <div class="msg-detail-body">{{ selected.message }}</div>

                            <!-- Preferred contact time -->
                            <div v-if="selected.preferred_contact_time" class="msg-detail-pref">
                                <Clock :size="14" />
                                <span>Best time to contact: <strong>{{ selected.preferred_contact_time }}</strong></span>
                            </div>

                            <!-- Reply link -->
                            <div class="msg-detail-actions">
                                <a :href="`mailto:${selected.email}`" class="msg-reply-btn">
                                    Reply by email
                                </a>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </template>
    </div>
</template>

<style scoped>
/* ── Wrapper ────────────────────────────────────────────────────────────── */
.msg-wrap {
    max-width: 1000px;
}

/* ── Empty state ─────────────────────────────────────────────────────────── */
.msg-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    padding: 64px 32px;
    background: var(--db-surface);
    border: 1.5px solid var(--db-line);
    border-radius: 14px;
    max-width: 480px;
    margin: 0 auto;
}

.msg-empty__icon {
    width: 72px;
    height: 72px;
    border-radius: 18px;
    background: var(--db-panel);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--db-ink-soft);
    margin-bottom: 20px;
}

.msg-empty__title {
    font-size: 22px;
    font-weight: 700;
    color: var(--db-ink);
    margin: 0 0 8px;
    letter-spacing: -0.3px;
}

.msg-empty__sub {
    font-size: 15px;
    color: var(--db-ink-soft);
    line-height: 1.6;
    margin: 0;
}

/* ── Header bar ──────────────────────────────────────────────────────────── */
.msg-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
}

.msg-header__left {
    display: flex;
    align-items: center;
    gap: 12px;
}

.msg-header__title {
    font-size: 20px;
    font-weight: 700;
    color: var(--db-ink);
    margin: 0;
    letter-spacing: -0.2px;
}

.msg-unread-pill {
    display: inline-flex;
    align-items: center;
    padding: 3px 10px;
    border-radius: 100px;
    background: var(--db-accent);
    color: var(--db-accent-fg);
    font-size: 12px;
    font-weight: 700;
}

/* ── Two-panel layout ────────────────────────────────────────────────────── */
.msg-panels {
    display: grid;
    grid-template-columns: 340px 1fr;
    gap: 16px;
    align-items: start;
}

/* ── List panel ──────────────────────────────────────────────────────────── */
.msg-list-panel {
    background: var(--db-surface);
    border: 1.5px solid var(--db-line);
    border-radius: 14px;
    overflow: hidden;
}

/* ── Row ─────────────────────────────────────────────────────────────────── */
.msg-row {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 16px;
    cursor: pointer;
    position: relative;
    border-bottom: 1px solid var(--db-line-soft);
    transition: background 0.1s ease;
}

.msg-row:last-child {
    border-bottom: none;
}

.msg-row:hover:not(.msg-row--active) {
    background: var(--db-panel);
}

.msg-row--active {
    background: var(--db-accent-soft);
}

.msg-row__avatar {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: var(--db-panel);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--db-ink-soft);
    flex-shrink: 0;
}

.msg-row--unread .msg-row__avatar {
    background: var(--db-accent-soft);
    color: var(--db-accent);
}

.msg-row__body {
    flex: 1;
    min-width: 0;
}

.msg-row__top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    margin-bottom: 3px;
}

.msg-row__email {
    font-size: 14px;
    font-weight: 700;
    color: var(--db-ink);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.msg-row__date {
    font-size: 12px;
    color: var(--db-ink-soft);
    flex-shrink: 0;
}

.msg-row__subject {
    font-size: 13px;
    font-weight: 600;
    color: var(--db-ink-mid);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-bottom: 2px;
}

.msg-row__preview {
    font-size: 12px;
    color: var(--db-ink-soft);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.msg-row__dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--db-accent);
    flex-shrink: 0;
    margin-top: 6px;
}

.msg-row__chevron {
    color: var(--db-ink-soft);
    flex-shrink: 0;
    margin-top: 4px;
}

/* ── Detail panel ────────────────────────────────────────────────────────── */
.msg-detail-panel {
    background: var(--db-surface);
    border: 1.5px solid var(--db-line);
    border-radius: 14px;
    min-height: 400px;
}

.msg-detail-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 12px;
    height: 400px;
    color: var(--db-ink-soft);
    font-size: 15px;
}

.msg-back-btn {
    display: none;
    align-items: center;
    gap: 6px;
    padding: 14px 20px;
    background: transparent;
    border: none;
    border-bottom: 1px solid var(--db-line-soft);
    color: var(--db-accent);
    font-family: inherit;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    width: 100%;
    text-align: left;
}

.msg-detail-card {
    padding: 24px;
}

/* From row */
.msg-detail-from {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 20px;
}

.msg-detail-avatar {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: var(--db-panel);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--db-ink-soft);
    flex-shrink: 0;
}

.msg-detail-meta {
    flex: 1;
    min-width: 0;
}

.msg-detail-email {
    font-size: 15px;
    font-weight: 700;
    color: var(--db-ink);
}

.msg-detail-ts {
    font-size: 13px;
    color: var(--db-ink-soft);
    margin-top: 2px;
}

.msg-read-badge,
.msg-unread-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    border-radius: 100px;
    font-size: 12px;
    font-weight: 600;
    flex-shrink: 0;
}

.msg-read-badge {
    background: var(--db-panel);
    color: var(--db-ink-soft);
}

.msg-unread-badge {
    background: var(--db-accent-soft);
    color: var(--db-accent);
}

.msg-detail-subject {
    font-size: 20px;
    font-weight: 700;
    color: var(--db-ink);
    letter-spacing: -0.3px;
    margin: 0 0 16px;
}

.msg-detail-body {
    font-size: 15px;
    color: var(--db-ink-mid);
    line-height: 1.7;
    white-space: pre-wrap;
    margin-bottom: 20px;
}

.msg-detail-pref {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 16px;
    background: var(--db-panel);
    border-radius: 10px;
    font-size: 14px;
    color: var(--db-ink-mid);
    margin-bottom: 20px;
}

.msg-detail-actions {
    display: flex;
    gap: 12px;
}

.msg-reply-btn {
    display: inline-flex;
    align-items: center;
    height: 44px;
    padding: 0 20px;
    border-radius: 10px;
    background: var(--db-accent);
    color: var(--db-accent-fg);
    font-family: inherit;
    font-size: 15px;
    font-weight: 700;
    text-decoration: none;
    cursor: pointer;
    transition: opacity 0.1s ease;
}

.msg-reply-btn:hover {
    opacity: 0.9;
}

/* ── Responsive ──────────────────────────────────────────────────────────── */
@media (max-width: 820px) {
    .msg-panels {
        grid-template-columns: 1fr;
    }

    .msg-list-panel--hidden-mobile {
        display: none;
    }

    .msg-detail-panel {
        display: none;
    }

    .msg-detail-panel--visible-mobile {
        display: block;
    }

    .msg-back-btn {
        display: flex;
    }
}
</style>
