<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import billing from '@/routes/billing';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { provide } from 'vue';
import Overview from '@/components/dashboard/Overview.vue';
import PremiumBanner from '@/components/dashboard/PremiumBanner.vue';
import PremiumStatus from '@/components/dashboard/PremiumStatus.vue';
import Site from '@/components/dashboard/Site.vue';
import Components from '@/components/dashboard/Components.vue';
import Settings from '@/components/dashboard/Settings.vue';
import { Tabs, TabsList, TabsTrigger, TabsContent } from '@/components/ui/tabs';

const props = defineProps({
    site: Object,
    appDomain: String,
    isPremium: Boolean,
});

provide('site', props.site);
provide('appDomain', props.appDomain);
provide('isPremium', props.isPremium);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl bg-white dark:bg-card p-4">
            <Tabs default-value="overview">
                <TabsList class="mb-4 h-auto">
                    <TabsTrigger value="overview" class="py-2.5 px-4 text-sm font-medium">Overview</TabsTrigger>
                    <TabsTrigger value="site" class="py-2.5 px-4 text-sm font-medium">My Address</TabsTrigger>
                    <TabsTrigger value="components" class="py-2.5 px-4 text-sm font-medium">Edit My Site</TabsTrigger>
                    <TabsTrigger value="settings" class="py-2.5 px-4 text-sm font-medium">Advanced</TabsTrigger>
                </TabsList>
                <TabsContent value="overview">
                    <Overview />
                </TabsContent>
                <TabsContent value="site">
                    <Site />
                </TabsContent>
                <TabsContent value="components">
                    <Components />
                </TabsContent>
                <TabsContent value="settings">
                    <Settings />
                </TabsContent>
            </Tabs>

            <!-- Premium banner — shown at the bottom of every tab -->
            <PremiumStatus v-if="isPremium" />
            <PremiumBanner v-else :checkout-url="billing.checkout.url()" :portal-url="billing.portal.url()" />
        </div>
    </AppLayout>
</template>
