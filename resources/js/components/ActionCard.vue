<script setup lang="ts">
defineProps<{
    href: string;
    title: string;
    description: string;
}>();
</script>

<template>
    <a
        :href="href"
        class="group rounded-lg bg-white p-6 shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] transition hover:bg-[#f9f9f8] dark:bg-[#161615] dark:shadow-[inset_0px_0px_0px_1px_#fffaed2d] dark:hover:bg-[#1a1a18]"
    >
        <div class="mb-4 flex items-center justify-between">
            <div class="flex h-9 w-9 items-center justify-center rounded-full bg-[#f5f5f3] dark:bg-[#1e1e1c]">
                <slot name="icon" />
            </div>
            <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-4 w-4 text-[#b5b3ad] transition group-hover:translate-x-0.5 dark:text-[#55544f]"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2"
            >
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
            </svg>
        </div>
        <p class="text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">{{ title }}</p>
        <p class="mt-0.5 text-xs text-[#706f6c] dark:text-[#A1A09A]">{{ description }}</p>
    </a>
</template>
