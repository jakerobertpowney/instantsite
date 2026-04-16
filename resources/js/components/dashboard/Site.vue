<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { site as dashboardSite } from '@/routes/dashboard';
import { inject, ref, computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Loader2 } from 'lucide-vue-next';
import { toast } from 'vue-sonner';
import { Input } from '@/components/ui/input';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

const site = inject('site') as any;
const isPremium = inject('isPremium') as boolean;

const saving = ref(false);

// Suggest a subdomain slug derived from the business name when none has been set
const suggestedSubdomain = computed(() => {
    const name: string = site?.data?.displayName?.text ?? '';
    return name
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-|-$/g, '');
});

const form = useForm({
    meta_title: site.meta_title,
    meta_description: site.meta_description,
    domain_type: site.domain_type,
    subdomain: site.subdomain,
    custom_domain: site.custom_domain,
});

const saveForm = () => {
    saving.value = true;

    form.post(dashboardSite.url(), {
        onSuccess: () => {
            toast('Saved!')
            saving.value = false;
        },
    });
};
</script>

<template>
    <div class="flex flex-col gap-8">
        <div>
            <h1 class="text-2xl font-bold">My Address</h1>
            <p class="mt-1 text-base text-muted-foreground">Choose where people can find your website.</p>
        </div>

        <!-- Page title -->
        <div class="flex flex-col gap-2">
            <Label for="title" class="text-base font-semibold">Page title</Label>
            <Input
                type="text"
                name="title"
                id="title"
                placeholder="e.g. Dave's Painting & Decorating"
                v-model="form.meta_title"
                class="h-11 text-base"
                required
            />
            <p class="text-sm text-muted-foreground">
                This appears in the browser tab and in Google search results. Use your business name.
            </p>
            <p v-if="form.errors.meta_title" class="text-sm text-destructive">{{ form.errors.meta_title }}</p>
        </div>

        <!-- Meta description -->
        <div class="flex flex-col gap-2">
            <Label for="description" class="text-base font-semibold">Short description</Label>
            <Textarea
                name="description"
                id="description"
                placeholder="e.g. Professional painter and decorator based in Manchester. 20 years experience. Free quotes."
                v-model="form.meta_description"
                rows="3"
                class="text-base resize-none"
                required
            />
            <p class="text-sm text-muted-foreground">
                A one or two sentence summary of your business. This shows up under your site in Google search results.
            </p>
            <p v-if="form.errors.meta_description" class="text-sm text-destructive">{{ form.errors.meta_description }}</p>
        </div>

        <!-- Domain / web address -->
        <div class="flex flex-col gap-3">
            <Label class="text-base font-semibold">Web address</Label>
            <RadioGroup default-value="subdomain" v-model="form.domain_type" class="flex flex-col gap-2">

                <!-- Free subdomain -->
                <div class="rounded-xl border px-5 py-4" :class="form.domain_type === 'subdomain' ? 'border-foreground bg-muted/30' : 'bg-muted/10'">
                    <div class="flex items-center gap-3">
                        <RadioGroupItem value="subdomain" id="domain-subdomain" />
                        <div class="flex flex-col">
                            <label for="domain-subdomain" class="text-base font-semibold cursor-pointer">Free address</label>
                            <span class="text-sm text-muted-foreground">e.g. yourname.instantsite.test — free, ready in seconds</span>
                        </div>
                    </div>

                    <div v-if="form.domain_type === 'subdomain'" class="mt-4 flex items-stretch">
                        <Input
                            type="text"
                            name="subdomain"
                            id="subdomain"
                            :placeholder="suggestedSubdomain || 'yourbusiness'"
                            v-model="form.subdomain"
                            class="rounded-r-none h-11 text-base bg-white dark:bg-background"
                            required
                        />
                        <div class="flex items-center rounded-r-lg border border-l-0 border-input bg-muted px-3 text-sm text-muted-foreground whitespace-nowrap">
                            .instantsite.test
                        </div>
                    </div>
                    <p v-if="form.errors.subdomain" class="mt-1 text-sm text-destructive">{{ form.errors.subdomain }}</p>
                </div>

                <!-- Custom domain (Premium) -->
                <div
                    class="rounded-xl border px-5 py-4"
                    :class="[
                        form.domain_type === 'custom' ? 'border-foreground bg-muted/30' : 'bg-muted/10',
                        !isPremium ? 'opacity-60' : '',
                    ]"
                >
                    <div class="flex items-center gap-3">
                        <RadioGroupItem value="custom" id="domain-custom" :disabled="!isPremium" />
                        <div class="flex flex-col">
                            <label for="domain-custom" class="text-base font-semibold cursor-pointer flex items-center gap-2">
                                My own domain
                                <span v-if="!isPremium" class="rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400 text-xs font-semibold px-2 py-0.5">Premium</span>
                            </label>
                            <span class="text-sm text-muted-foreground">e.g. www.yourbusiness.com — use a domain you already own</span>
                        </div>
                    </div>

                    <div v-if="form.domain_type === 'custom'" class="mt-4">
                        <Input
                            type="text"
                            name="custom_domain"
                            id="custom_domain"
                            placeholder="yourdomain.com"
                            v-model="form.custom_domain"
                            class="h-11 text-base bg-white dark:bg-background"
                            required
                        />
                    </div>
                    <p v-if="form.errors.custom_domain" class="mt-1 text-sm text-destructive">{{ form.errors.custom_domain }}</p>
                </div>

                <!-- Draft -->
                <div class="rounded-xl border px-5 py-4" :class="form.domain_type === 'draft' ? 'border-foreground bg-muted/30' : 'bg-muted/10'">
                    <div class="flex items-center gap-3">
                        <RadioGroupItem value="draft" id="domain-draft" />
                        <div class="flex flex-col">
                            <label for="domain-draft" class="text-base font-semibold cursor-pointer">Save as draft</label>
                            <span class="text-sm text-muted-foreground">Your site won't be visible to anyone — come back and publish it later</span>
                        </div>
                    </div>
                </div>
            </RadioGroup>
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
