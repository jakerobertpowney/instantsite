<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { dashboard, submissions } from '@/routes';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { Globe, Inbox, LayoutGrid, LifeBuoy } from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from './AppLogo.vue';

const page = usePage();
const unread = computed(() => (page.props.unreadSubmissionsCount as number) || 0);

const mainNavItems = computed<NavItem[]>(() => [
    {
        title: 'My Site',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Messages',
        href: submissions(),
        icon: Inbox,
        badge: unread.value || undefined,
    },
]);

const footerNavItems: NavItem[] = [
    {
        title: 'Website',
        href: '/',
        icon: Globe,
    },
    {
        title: 'Help',
        href: '/help',
        icon: LifeBuoy,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
