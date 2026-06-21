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
    <div class="max-w-[1000px]">
        <!-- Empty state -->
        <div v-if="list.length === 0" class="flex flex-col items-center text-center p-16 bg-white dark:bg-slate-900 border-[1.5px] border-[#dde1e8] dark:border-slate-700 rounded-lg max-w-md mx-auto">
            <div class="w-18 h-18 rounded-lg bg-[#edf1f8] dark:bg-slate-800 flex items-center justify-center text-slate-400 mb-5">
                <Inbox :size="40" />
            </div>
            <h2 class="text-xl font-bold text-slate-950 dark:text-white m-0 tracking-[-0.3px]">No messages yet</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400 leading-[1.6] m-0">When someone fills in the contact form on your site, their message will appear here.</p>
        </div>

        <!-- Inbox layout -->
        <template v-else>
            <!-- Header bar -->
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                    <h2 class="text-xl font-bold text-slate-950 dark:text-white m-0 tracking-[-0.2px]">Inbox</h2>
                    <span v-if="unreadCount > 0" class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-blue-500 text-white text-xs font-bold">{{ unreadCount }} unread</span>
                </div>
            </div>

            <!-- Two-panel -->
            <div class="grid grid-cols-[340px_1fr] gap-4 items-start lg:grid-cols-[340px_1fr] sm:grid-cols-1">
                <!-- List panel -->
                <div class="bg-white dark:bg-slate-900 border-[1.5px] border-[#dde1e8] dark:border-slate-700 rounded-lg overflow-hidden" :class="{ 'hidden sm:block': selectedId !== null }">
                    <div
                        v-for="sub in list"
                        :key="sub.id"
                        class="flex items-start gap-3 p-4 cursor-pointer relative border-b border-[#e8ecf1] dark:border-slate-700 transition-colors duration-100"
                        :class="{
                            'bg-[#e6eefe] dark:bg-blue-900/30': selectedId === sub.id,
                            'hover:bg-[#edf1f8] dark:hover:bg-slate-800': selectedId !== sub.id,
                            'bg-white dark:bg-slate-900': selectedId !== sub.id,
                        }"
                        @click="openMessage(sub)"
                    >
                        <!-- Avatar -->
                        <div class="w-9.5 h-9.5 rounded-full bg-[#edf1f8] dark:bg-slate-800 flex items-center justify-center text-slate-500 dark:text-slate-400 flex-shrink-0" :class="{ 'bg-[#e6eefe] dark:bg-blue-900/30 text-blue-600': !sub.read_at }">
                            <User :size="18" />
                        </div>
                        <!-- Content -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-2 mb-0.5">
                                <span class="text-sm font-bold text-slate-950 dark:text-white whitespace-nowrap overflow-hidden text-ellipsis">{{ sub.email }}</span>
                                <span class="text-xs text-slate-500 dark:text-slate-400 flex-shrink-0">{{ formatDate(sub.created_at) }}</span>
                            </div>
                            <div class="text-xs font-semibold text-slate-700 dark:text-slate-300 whitespace-nowrap overflow-hidden text-ellipsis mb-0.5">{{ sub.subject || '(No subject)' }}</div>
                            <div class="text-xs text-slate-500 dark:text-slate-400 whitespace-nowrap overflow-hidden text-ellipsis">{{ sub.message }}</div>
                        </div>
                        <!-- Unread dot -->
                        <div v-if="!sub.read_at" class="w-2 h-2 rounded-full bg-blue-500 flex-shrink-0 mt-1.5" />
                        <!-- Chevron -->
                        <ChevronRight :size="16" class="text-slate-400 dark:text-slate-500 flex-shrink-0 mt-1" />
                    </div>
                </div>

                <!-- Detail panel -->
                <div
                    class="bg-white dark:bg-slate-900 border-[1.5px] border-[#dde1e8] dark:border-slate-700 rounded-lg min-h-96"
                    :class="selectedId !== null ? 'block' : 'hidden sm:block'"
                >
                    <!-- No message selected -->
                    <div v-if="!selected" class="flex flex-col items-center justify-center gap-3 h-96 text-slate-500 dark:text-slate-400 text-sm">
                        <MailOpen :size="36" />
                        <p>Select a message to read it</p>
                    </div>

                    <!-- Message detail -->
                    <template v-else>
                        <button class="hidden sm:flex items-center gap-1.5 px-5 py-3.5 bg-transparent border-b border-[#e8ecf1] dark:border-slate-700 text-blue-600 dark:text-blue-400 font-semibold text-sm cursor-pointer w-full text-left hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors"
                            @click="selectedId = null">
                            <ArrowLeft :size="16" />
                            Back to inbox
                        </button>

                        <div class="p-6">
                            <!-- From row -->
                            <div class="flex items-start gap-3 mb-5">
                                <div class="w-11 h-11 rounded-full bg-[#edf1f8] dark:bg-slate-800 flex items-center justify-center text-slate-500 dark:text-slate-400 flex-shrink-0">
                                    <User :size="22" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm font-bold text-slate-950 dark:text-white">{{ selected.email }}</div>
                                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ formatDateFull(selected.created_at) }}</div>
                                </div>
                                <span v-if="selected.read_at" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-[#edf1f8] dark:bg-slate-800 text-slate-500 dark:text-slate-400 text-xs font-semibold flex-shrink-0">
                                    <MailOpen :size="13" />
                                    Read
                                </span>
                                <span v-else class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-[#e6eefe] dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 text-xs font-semibold flex-shrink-0">
                                    <Mail :size="13" />
                                    Unread
                                </span>
                            </div>

                            <!-- Subject -->
                            <h3 class="text-xl font-bold text-slate-950 dark:text-white tracking-[-0.3px] m-0 mb-4">{{ selected.subject || '(No subject)' }}</h3>

                            <!-- Message body -->
                            <div class="text-sm text-slate-700 dark:text-slate-300 leading-[1.7] whitespace-pre-wrap mb-5">{{ selected.message }}</div>

                            <!-- Preferred contact time -->
                            <div v-if="selected.preferred_contact_time" class="flex items-center gap-2 px-4 py-3 bg-[#edf1f8] dark:bg-slate-800 rounded-lg text-sm text-slate-700 dark:text-slate-300 mb-5">
                                <Clock :size="14" class="flex-shrink-0" />
                                <span>Best time to contact: <strong>{{ selected.preferred_contact_time }}</strong></span>
                            </div>

                            <!-- Reply link -->
                            <div class="flex gap-3">
                                <a :href="`mailto:${selected.email}`" class="inline-flex items-center h-11 px-5 rounded-lg bg-blue-600 text-white font-bold text-sm cursor-pointer transition-opacity duration-100 hover:opacity-90">
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
