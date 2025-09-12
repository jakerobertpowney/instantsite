<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { shallowRef } from 'vue';
import Overview from '@/components/dashboard/Overview.vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

const tabs = [
    {
        label: "Overview",
        component: Overview
    },
    {
        label: "Site"
    },
    {
        label: "Components"
    },
    {
        label: "Settings"
    }
]

const activeTab = shallowRef(Overview)

</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4 bg-white">
            <div class="grid grid-cols-6 gap-4">

                <div class="col-span-2">
                    <div
                        v-for="(tab, index) in tabs"
                        :key="index"
                        class="py-5 px-5 text-left rounded-xl cursor-pointer"
                        :class="[ tab.component === activeTab ? 'bg-gray-50 font-semibold' : '' ]"
                    >
                        {{ tab.label }}
                    </div>
                </div>

                <div class="col-span-4">
                    <component
                        :is="activeTab"
                    />
                </div>

            </div>
        </div>
    </AppLayout>
</template>
