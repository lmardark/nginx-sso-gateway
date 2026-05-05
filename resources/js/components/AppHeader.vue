<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import AppLogo from '@/components/AppLogo.vue';

defineProps<{
    user: {
        username: string;
        nickname?: string;
    };
}>();

function logout() {
    router.post('/logout');
}
</script>

<template>
    <header
        class="flex items-center justify-between border-b border-[#e3e3e0] bg-white px-6 py-4 shadow-sm dark:border-[#3E3E3A] dark:bg-[#161615]"
    >
        <a href="/home" class="flex items-center gap-3">
            <AppLogo class="h-6" />
            <span class="text-sm font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">
                Sistema de Autenticação
            </span>
        </a>

        <div class="flex items-center gap-4">
            <div class="h-4 w-px bg-[#e3e3e0] dark:bg-[#3E3E3A]"></div>

            <span class="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                {{ user.nickname ? `${user.nickname} (${user.username})` : user.username }}
            </span>

            <div class="h-4 w-px bg-[#e3e3e0] dark:bg-[#3E3E3A]"></div>

            <button
                type="button"
                class="rounded-sm border border-[#e3e3e0] bg-white px-4 py-1.5 text-sm font-medium text-[#1b1b18] transition hover:bg-[#f5f5f3] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] dark:hover:bg-[#1e1e1c]"
                @click="logout"
            >
                Deslogar
            </button>
        </div>
    </header>
</template>
