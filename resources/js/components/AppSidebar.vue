<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { LayoutGrid, FileText, Users, Clock, CheckCircle } from 'lucide-vue-next';
import { useTranslations } from '@/composables/useTranslations';
import AppLogo from './AppLogo.vue';

const { t } = useTranslations();

const mainNavItems: NavItem[] = [
    {
        title: t('messages.home'),
        href: '/dashboard',
        icon: LayoutGrid,
    },
    {
        title: t('messages.files'),
        href: '/files',
        icon: FileText,
    },
    {
        title: t('messages.pending_files'),
        href: '/approval/pending',
        icon: Clock,
        roles: ['checker', 'registrator', 'ceo'],
    },
    {
        title: t('messages.waiting_files'),
        href: '/approval/waiting',
        icon: CheckCircle,
        roles: ['registrator', 'ceo'],
    },
    {
        title: t('messages.user_management'),
        href: '/users',
        icon: Users,
        roles: ['ceo'],
    },
];
</script>

<template>
  <Sidebar collapsible="icon" variant="inset">
    <SidebarHeader>
      <SidebarMenu>
        <SidebarMenuItem>
          <SidebarMenuButton size="lg" as-child>
            <Link :href="route('dashboard')">
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
      <NavUser />
    </SidebarFooter>
  </Sidebar>
  <slot />
</template>
