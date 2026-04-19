<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Sparkles, Globe, Mail, BarChart3 } from 'lucide-vue-next';

defineProps<{ checkoutUrl: string; portalUrl: string }>();
</script>

<template>
    <div class="rounded-xl border bg-gradient-to-br from-neutral-950 to-neutral-800 text-white p-6 flex flex-col gap-5">
        <div class="flex items-center gap-2">
            <Sparkles class="h-5 w-5 text-yellow-400" />
            <span class="text-sm font-semibold tracking-wide uppercase text-yellow-400">321Sites Premium</span>
        </div>

        <div>
            <h2 class="text-xl font-semibold mb-1">You're on the free plan</h2>
            <p class="text-sm text-neutral-400">Upgrade to unlock more features for your site.</p>
        </div>

        <ul class="flex flex-col gap-2.5">
            <li class="flex items-center gap-2.5 text-sm">
                <Globe class="h-4 w-4 text-neutral-400 shrink-0" />
                <span>Custom domain (e.g. <span class="text-white font-medium">www.yourbusiness.com</span>)</span>
            </li>
            <li class="flex items-center gap-2.5 text-sm">
                <Mail class="h-4 w-4 text-neutral-400 shrink-0" />
                <span>Contact form — let customers message you directly</span>
            </li>
            <li class="flex items-center gap-2.5 text-sm">
                <BarChart3 class="h-4 w-4 text-neutral-400 shrink-0" />
                <span>Google Analytics integration</span>
            </li>
        </ul>

        <div class="flex flex-col sm:flex-row gap-3 pt-1">
            <Button
                as="a"
                :href="checkoutUrl"
                size="lg"
                class="bg-white text-black hover:bg-neutral-100 font-semibold w-full sm:w-auto"
            >
                <Sparkles class="h-4 w-4 mr-2 text-yellow-500" />
                Upgrade to Premium
            </Button>
        </div>
    </div>
</template>
