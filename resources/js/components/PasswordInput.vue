<script setup lang="ts">
import { ref } from 'vue';

defineProps<{
    id: string;
    label: string;
    modelValue: string;
    error?: string;
    required?: boolean;
    autocomplete?: string;
}>();

defineEmits<{
    'update:modelValue': [value: string];
}>();

const showPassword = ref(false);
</script>

<template>
    <div class="flex flex-col gap-1.5">
        <label :for="id" class="text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">
            {{ label }}
            <span v-if="required" class="ml-0.5 text-red-500">*</span>
        </label>
        <div class="relative">
            <input
                :id="id"
                :value="modelValue"
                :type="showPassword ? 'text' : 'password'"
                :required="required"
                :autocomplete="autocomplete"
                placeholder="••••••••"
                class="w-full rounded-sm border border-[#e3e3e0] bg-[#FDFDFC] px-3 py-2 pr-10 text-sm text-[#1b1b18] outline-none transition placeholder:text-[#b5b3ad] focus:border-[#1b1b18] focus:ring-1 focus:ring-[#1b1b18] disabled:opacity-50 dark:border-[#3E3E3A] dark:bg-[#1a1a18] dark:text-[#EDEDEC] dark:placeholder:text-[#55544f] dark:focus:border-[#EDEDEC] dark:focus:ring-[#EDEDEC]"
                :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500': error }"
                @input="$emit('update:modelValue', ($event.target as HTMLInputElement).value)"
            />
            <button
                type="button"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-[#706f6c] hover:text-[#1b1b18] dark:text-[#A1A09A] dark:hover:text-[#EDEDEC]"
                @click="showPassword = !showPassword"
            >
                <svg v-if="showPassword" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                </svg>
                <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
            </button>
        </div>
        <p v-if="error" class="text-xs text-red-500">{{ error }}</p>
    </div>
</template>
