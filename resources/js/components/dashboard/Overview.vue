<script setup lang="ts">
import { Copy, ExternalLink, Globe } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { inject, computed, ref } from 'vue';

const site = inject('site') as any;
const appDomain = inject('appDomain') as string;

const siteUrl = computed(() => {
    if (!site) return null;
    if (site.domain_type === 'custom' && site.custom_domain) {
        return `https://${site.custom_domain}`;
    }
    if (site.domain_type === 'subdomain' && site.subdomain) {
        return `https://${site.subdomain}.${appDomain}`;
    }
    return null;
});

const statusLabel = computed(() => {
    if (site?.domain_type === 'draft') return 'Draft — not visible yet';
    if (site?.domain_type === 'custom' && site?.custom_domain) return 'Live — Custom Domain';
    if (site?.domain_type === 'subdomain' && site?.subdomain) return 'Live';
    return 'Not published';
});

const isLive = computed(() => !!siteUrl.value && site?.domain_type !== 'draft');

const showCopied = ref(false);

const copyLink = () => {
    if (!siteUrl.value) return;
    navigator.clipboard.writeText(siteUrl.value);
    showCopied.value = true;
    setTimeout(() => { showCopied.value = false; }, 2000);
};
</script>

<template>
    <div class="flex flex-col gap-8">
        <!-- Heading -->
        <div>
            <h1 class="text-2xl font-bold">Welcome back!</h1>
            <p class="mt-1 text-base text-muted-foreground">Here's a quick look at your website.</p>
        </div>

        <!-- Status + live URL card -->
        <div class="rounded-xl border p-6 flex flex-col gap-5">
            <div class="flex items-center gap-3">
                <span class="text-base font-semibold">Website status:</span>
                <Badge
                    :variant="isLive ? 'default' : 'secondary'"
                    class="text-sm px-3 py-1"
                >
                    {{ statusLabel }}
                </Badge>
            </div>

            <!-- Live site actions -->
            <template v-if="siteUrl">
                <div class="bg-muted border rounded-xl py-3 px-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                    <a :href="siteUrl" target="_blank" class="flex items-center gap-2 text-sm hover:underline truncate min-w-0">
                        <Globe class="h-4 w-4 shrink-0 text-muted-foreground" />
                        <span class="truncate">{{ siteUrl }}</span>
                        <ExternalLink class="h-3 w-3 shrink-0 text-muted-foreground" />
                    </a>
                    <div class="flex flex-row gap-2 items-center shrink-0">
                        <span v-show="showCopied" class="text-sm text-muted-foreground">Copied!</span>
                        <Button @click="copyLink" size="sm" variant="outline" class="gap-1.5">
                            <Copy class="h-4 w-4" />
                            Copy link
                        </Button>
                    </div>
                </div>

                <Button
                    as="a"
                    :href="siteUrl"
                    target="_blank"
                    size="lg"
                    class="w-full sm:w-auto text-base font-semibold gap-2"
                >
                    <ExternalLink class="h-5 w-5" />
                    View my website
                </Button>

                <p class="text-sm text-muted-foreground">
                    Share this link with your customers. To change your web address, go to the <strong>My Address</strong> tab.
                </p>
            </template>

            <!-- Not published yet -->
            <template v-else>
                <div class="rounded-xl bg-muted/60 border border-dashed p-5 text-center flex flex-col gap-3 items-center">
                    <Globe class="h-8 w-8 text-muted-foreground" />
                    <div>
                        <p class="text-base font-semibold">Your website isn't published yet</p>
                        <p class="text-sm text-muted-foreground mt-1">
                            Go to the <strong>My Address</strong> tab to choose a web address and make your site live.
                        </p>
                    </div>
                </div>
            </template>
        </div>
    </div>
</template>
