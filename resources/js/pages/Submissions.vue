<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { submissions as submissionsRoute } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Inbox, Mail, MailOpen, ChevronDown, ChevronUp } from 'lucide-vue-next';

interface Submission {
    id: number;
    email: string;
    subject: string;
    message: string;
    preferred_contact_time: string | null;
    read_at: string | null;
    created_at: string;
}

interface Pagination<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    next_page_url: string | null;
    prev_page_url: string | null;
}

defineProps<{
    submissions: Pagination<Submission>;
    unreadCount: number;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Customer Enquiries',
        href: submissionsRoute().url,
    },
];

const expandedId = ref<number | null>(null);

function toggle(submission: Submission) {
    const opening = expandedId.value !== submission.id;
    expandedId.value = opening ? submission.id : null;

    // Mark as read when first opened
    if (opening && submission.read_at === null) {
        router.post(`/submissions/${submission.id}/read`, {}, {
            preserveScroll: true,
            preserveState: true,
        });
    }
}

function formatDate(iso: string): string {
    return new Date(iso).toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}
</script>

<template>
    <Head title="Customer Enquiries" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl bg-white dark:bg-card p-6">

            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-bold">Customer Enquiries</h1>
                    <Badge v-if="unreadCount > 0" variant="destructive" class="rounded-full px-2 py-0.5 text-xs">
                        {{ unreadCount }} new
                    </Badge>
                </div>
            </div>

            <!-- Empty state -->
            <div
                v-if="submissions.data.length === 0"
                class="flex flex-col items-center justify-center gap-3 py-20 text-center"
            >
                <Inbox class="h-12 w-12 text-muted-foreground/40" />
                <p class="text-base font-medium text-muted-foreground">No messages yet</p>
                <p class="text-sm text-muted-foreground/70 max-w-xs">
                    When customers contact you through your site, their messages will appear here.
                </p>
            </div>

            <!-- Submission list -->
            <div v-else class="flex flex-col divide-y divide-border rounded-xl border overflow-hidden">
                <div
                    v-for="submission in submissions.data"
                    :key="submission.id"
                    class="transition-colors"
                    :class="submission.read_at === null ? 'bg-blue-50/60 dark:bg-blue-950/20' : 'bg-white dark:bg-card'"
                >
                    <!-- Row header — click to expand -->
                    <button
                        type="button"
                        class="w-full flex items-start gap-4 px-5 py-4 text-left hover:bg-muted/40 transition-colors"
                        @click="toggle(submission)"
                    >
                        <!-- Read / unread icon -->
                        <div class="shrink-0 mt-0.5">
                            <Mail
                                v-if="submission.read_at === null"
                                class="h-4 w-4 text-blue-500"
                            />
                            <MailOpen
                                v-else
                                class="h-4 w-4 text-muted-foreground/40"
                            />
                        </div>

                        <!-- Content summary -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-baseline gap-2 flex-wrap">
                                <span
                                    class="text-sm truncate max-w-[200px]"
                                    :class="submission.read_at === null ? 'font-semibold' : 'font-medium'"
                                >
                                    {{ submission.email }}
                                </span>
                                <span class="text-sm text-muted-foreground truncate flex-1">
                                    {{ submission.subject }}
                                </span>
                            </div>
                            <!-- Message preview when collapsed -->
                            <p
                                v-if="expandedId !== submission.id"
                                class="text-xs text-muted-foreground mt-0.5 truncate"
                            >
                                {{ submission.message }}
                            </p>
                            <p
                                v-if="submission.preferred_contact_time"
                                class="mt-1 text-xs font-medium text-emerald-700 dark:text-emerald-400"
                            >
                                Preferred time: {{ submission.preferred_contact_time }}
                            </p>
                        </div>

                        <!-- Date + expand icon -->
                        <div class="shrink-0 flex items-center gap-2 ml-2">
                            <span class="text-xs text-muted-foreground whitespace-nowrap hidden sm:block">
                                {{ formatDate(submission.created_at) }}
                            </span>
                            <ChevronUp v-if="expandedId === submission.id" class="h-4 w-4 text-muted-foreground" />
                            <ChevronDown v-else class="h-4 w-4 text-muted-foreground" />
                        </div>
                    </button>

                    <!-- Expanded message body -->
                    <div
                        v-if="expandedId === submission.id"
                        class="px-5 pb-5 pt-1 border-t border-border/50"
                    >
                        <!-- Mobile date -->
                        <p class="text-xs text-muted-foreground mb-3 sm:hidden">
                            {{ formatDate(submission.created_at) }}
                        </p>

                        <div class="rounded-lg bg-muted/40 px-4 py-3">
                            <p class="text-sm whitespace-pre-wrap leading-relaxed">{{ submission.message }}</p>
                        </div>

                        <div
                            v-if="submission.preferred_contact_time"
                            class="mt-3 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-900/50 dark:bg-emerald-950/30 dark:text-emerald-100"
                        >
                            <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-300">
                                Preferred date &amp; time
                            </p>
                            <p class="mt-1">{{ submission.preferred_contact_time }}</p>
                        </div>

                        <div class="mt-3 flex items-center gap-3">
                            <a
                                :href="`mailto:${submission.email}?subject=Re: ${encodeURIComponent(submission.subject)}`"
                                class="inline-flex items-center gap-1.5 text-sm font-medium text-primary hover:underline"
                            >
                                <Mail class="h-3.5 w-3.5" />
                                Reply to {{ submission.email }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div
                v-if="submissions.last_page > 1"
                class="flex items-center justify-between pt-2"
            >
                <p class="text-sm text-muted-foreground">
                    Page {{ submissions.current_page }} of {{ submissions.last_page }}
                    &middot; {{ submissions.total }} total
                </p>
                <div class="flex gap-2">
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="!submissions.prev_page_url"
                        @click="router.get(submissions.prev_page_url!)"
                    >
                        Previous
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="!submissions.next_page_url"
                        @click="router.get(submissions.next_page_url!)"
                    >
                        Next
                    </Button>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
