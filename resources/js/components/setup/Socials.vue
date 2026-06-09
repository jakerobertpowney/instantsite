<script setup lang="ts">
import { inject, computed } from 'vue';
import { Instagram, Facebook, Twitter, Linkedin, CheckCircle, AlertCircle } from 'lucide-vue-next';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';

const form = inject<any>('form');
const siteData = inject<any>('siteData', null);

type SocialKey = 'instagram' | 'facebook' | 'x' | 'linkedin';

const suggested = computed<Partial<Record<SocialKey, string>>>(() =>
    siteData?.socials ?? {}
);

const hasSuggestions = computed(() => Object.keys(suggested.value).length > 0);

const acceptSuggestion = (key: SocialKey) => {
    form.socials[key] = suggested.value[key] ?? '';
};

const isAccepted = (key: SocialKey) =>
    !!suggested.value[key] && form.socials[key] === suggested.value[key];

const socials: { key: SocialKey; label: string; hint: string; icon: any; placeholder: string }[] = [
    {
        key: 'facebook',
        label: 'Facebook',
        hint: 'Your Facebook page',
        icon: Facebook,
        placeholder: 'https://www.facebook.com/yourpagename',
    },
    {
        key: 'instagram',
        label: 'Instagram',
        hint: 'Your Instagram profile',
        icon: Instagram,
        placeholder: 'https://www.instagram.com/yourusername',
    },
    {
        key: 'x',
        label: 'Twitter / X',
        hint: 'Your Twitter or X profile',
        icon: Twitter,
        placeholder: 'https://x.com/yourusername',
    },
    {
        key: 'linkedin',
        label: 'LinkedIn',
        hint: 'Your LinkedIn page',
        icon: Linkedin,
        placeholder: 'https://www.linkedin.com/company/yourcompany',
    },
];
</script>

<template>
    <div class="flex flex-col gap-5">

        <!-- Found suggestions notice -->
        <div
            v-if="hasSuggestions"
            class="rounded-lg bg-muted/50 border px-4 py-3 text-sm text-muted-foreground"
        >
            We found some of your social pages from your website.
            Tap <strong class="text-foreground">Use this</strong> to add them.
        </div>

        <div v-for="social in socials" :key="social.key" class="flex flex-col gap-2">
            <Label :for="social.key" class="flex items-center gap-2 font-medium">
                <component :is="social.icon" class="h-4 w-4 text-muted-foreground" />
                {{ social.label }}
            </Label>

            <!-- Suggestion chip -->
            <div
                v-if="suggested[social.key] && !isAccepted(social.key)"
                class="flex items-center gap-3 rounded-lg border border-dashed bg-muted/40 px-3 py-2.5"
            >
                <span class="flex-1 truncate text-sm text-muted-foreground">
                    {{ suggested[social.key] }}
                </span>
                <Button
                    type="button"
                    size="sm"
                    variant="secondary"
                    class="shrink-0 h-7 px-3 text-xs"
                    @click="acceptSuggestion(social.key)"
                >
                    Use this
                </Button>
            </div>

            <!-- Accepted -->
            <div
                v-else-if="isAccepted(social.key)"
                class="flex items-center gap-1.5 text-sm text-green-600 dark:text-green-400"
            >
                <CheckCircle class="h-4 w-4 shrink-0" />
                Added
            </div>

            <Input
                type="url"
                :name="social.key"
                :id="social.key"
                :placeholder="social.placeholder"
                v-model="form.socials[social.key]"
                class="h-11"
                :class="{ 'border-destructive ring-destructive': form.errors[`socials.${social.key}`] }"
                inputmode="url"
            />
            <p v-if="form.errors[`socials.${social.key}`]" class="text-sm text-destructive flex items-center gap-1.5">
                <AlertCircle class="h-4 w-4 shrink-0" />
                {{ form.errors[`socials.${social.key}`] }}
            </p>
        </div>

        <p class="text-sm text-muted-foreground text-center">
            Leave any blank that you don't use — that's fine.
        </p>
    </div>
</template>
