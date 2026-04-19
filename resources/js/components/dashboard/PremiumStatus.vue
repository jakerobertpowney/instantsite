<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Sparkles, ExternalLink } from 'lucide-vue-next';
import billing from '@/routes/billing';

const portalUrl = billing.portal.url();
</script>

<template>
    <div class="rounded-xl border px-5 py-4 flex items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <Sparkles class="h-5 w-5 text-yellow-500 shrink-0" />
            <div>
                <div class="flex items-center gap-2 mb-0.5">
                    <span class="text-sm font-semibold">321Sites Premium</span>
                    <Badge variant="default" class="text-xs">Active</Badge>
                </div>
                <p class="text-sm text-muted-foreground">All premium features are enabled on your site.</p>
            </div>
        </div>
        <Button
            as="a"
            :href="portalUrl"
            variant="outline"
            size="sm"
            class="shrink-0"
        >
            Manage <ExternalLink class="h-3 w-3 ml-1.5" />
        </Button>
    </div>
</template>
