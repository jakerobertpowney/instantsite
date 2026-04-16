<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import { settings as dashboardSettings } from '@/routes/dashboard';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Switch } from '@/components/ui/switch';
import { Label } from '@/components/ui/label';
import { Loader2 } from 'lucide-vue-next';
import { toast } from 'vue-sonner';
import { computed, ref } from 'vue';

const page = usePage();
const site = computed(() => (page.props.site as any) ?? {});
const siteData = computed(() => site.value.data ?? {});

const form = useForm({
    google_analytics_id: siteData.value.google_analytics_id ?? '',
    allow_indexing: siteData.value.allow_indexing !== false,
});

const saving = ref(false);

const saveForm = () => {
    saving.value = true;
    form.post(dashboardSettings.url(), {
        onSuccess: () => {
            toast('Saved!');
            saving.value = false;
        },
        onError: () => {
            saving.value = false;
        },
    });
};
</script>

<template>
    <div class="flex flex-col gap-8">
        <div>
            <h1 class="text-2xl font-bold">Advanced settings</h1>
            <p class="mt-1 text-base text-muted-foreground">
                Optional extras — you don't need to change anything here to get your site live.
            </p>
        </div>

        <!-- Google Analytics -->
        <div class="flex flex-col gap-3 rounded-xl border p-5">
            <div>
                <Label for="ga-id" class="text-base font-semibold">Google Analytics</Label>
                <p class="text-sm text-muted-foreground mt-1">
                    Google Analytics lets you see how many people are visiting your site and where they're coming from.
                    If you have a Google Analytics account, paste your Measurement ID below (it looks like <code class="bg-muted rounded px-1 py-0.5 text-xs">G-XXXXXXXXXX</code>).
                </p>
            </div>
            <Input
                id="ga-id"
                v-model="form.google_analytics_id"
                placeholder="G-XXXXXXXXXX"
                class="h-11 text-base max-w-sm"
            />
            <p v-if="form.errors.google_analytics_id" class="text-sm text-destructive">{{ form.errors.google_analytics_id }}</p>
            <p class="text-xs text-muted-foreground">Leave blank if you don't use Google Analytics — it won't affect your site.</p>
        </div>

        <!-- Search engine indexing -->
        <div class="flex items-start justify-between gap-4 rounded-xl border p-5">
            <div class="flex flex-col gap-1 max-w-lg">
                <Label for="allow-indexing" class="text-base font-semibold">Show my site on Google</Label>
                <p class="text-sm text-muted-foreground">
                    When this is turned on, Google and other search engines can find and list your website.
                    Turn it off if you want to keep your site private or you're still setting it up.
                </p>
            </div>
            <Switch id="allow-indexing" v-model="form.allow_indexing" class="mt-1 shrink-0" />
        </div>

        <!-- Save -->
        <div>
            <Button @click.prevent="saveForm" size="lg" class="w-full sm:w-auto text-base font-semibold px-8 h-12" :disabled="saving">
                <Loader2 v-show="saving" class="mr-2 h-5 w-5 animate-spin" />
                Save changes
            </Button>
        </div>
    </div>
</template>
