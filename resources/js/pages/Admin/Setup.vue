<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import PasswordInput from '@/components/PasswordInput.vue';

const form = useForm({
    nickname: '',
    validation_type: 'cpf' as 'cpf' | 'celular' | 'personalizado',
    custom_pattern: '',
    username: '',
    password: '',
    password_confirmation: '',
});

function submit() {
    form.post('/setup');
}

function isValidCpf(value: string): boolean {
    const cpf = value.replace(/[^0-9]/g, '');

    if (cpf.length !== 11 || new Set(cpf.split('')).size === 1) {
        return false;
    }

    for (let t = 9; t < 11; t++) {
        let sum = 0;

        for (let i = 0; i < t; i++) {
            sum += Number(cpf[i]) * (t + 1 - i);
        }

        const digit = ((sum * 10) % 11) % 10;

        if (Number(cpf[t]) !== digit) {
            return false;
        }
    }

    return true;
}

function isValidCelular(value: string): boolean {
    return /^\(?\d{2}\)?\s?9\d{4}[\s-]?\d{4}$/.test(value);
}

const usernamePlaceholder = computed<string>(() => {
    if (form.validation_type === 'cpf') {
        return '000.000.000-00';
    }

    if (form.validation_type === 'celular') {
        return '(00) 90000-0000';
    }

    return 'Seu usuário';
});

const clientUsernameError = computed<string | null>(() => {
    if (!form.username) {
        return null;
    }

    if (form.validation_type === 'cpf' && !isValidCpf(form.username)) {
        return 'CPF inválido.';
    }

    if (form.validation_type === 'celular' && !isValidCelular(form.username)) {
        return 'Número de celular inválido. Ex: (00) 90000-0000';
    }

    if (form.validation_type === 'personalizado' && form.custom_pattern) {
        try {
            if (!new RegExp(form.custom_pattern).test(form.username)) {
                return 'O usuário não corresponde ao padrão personalizado.';
            }
        } catch {
            // regex inválido, pular validação no frontend
        }
    }

    return null;
});

const customPatternError = computed<string | null>(() => {
    if (form.validation_type !== 'personalizado' || !form.custom_pattern) {
        return null;
    }

    try {
        new RegExp(form.custom_pattern);

        return null;
    } catch {
        return 'Expressão regular inválida.';
    }
});
</script>

<template>
    <Head title="Configuração Inicial" />

    <div
        class="flex min-h-screen flex-col items-center justify-center bg-[#FDFDFC] p-6 text-[#1b1b18] dark:bg-[#0a0a0a]"
    >
        <div class="w-full max-w-md">
            <!-- Logo -->
            <div class="mb-8 flex justify-center">
                <AppLogo class="h-8" />
            </div>

            <!-- Banner de aviso -->
            <div
                class="mb-6 flex items-start gap-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 dark:border-amber-800 dark:bg-amber-950"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="mt-0.5 h-4 w-4 flex-shrink-0 text-amber-600 dark:text-amber-400"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="2"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                    />
                </svg>
                <div>
                    <p
                        class="text-sm font-medium text-amber-800 dark:text-amber-300"
                    >
                        Nenhuma conta encontrada
                    </p>
                    <p
                        class="mt-0.5 text-xs text-amber-700 dark:text-amber-400"
                    >
                        Configure o usuário administrador para começar a usar o
                        sistema.
                    </p>
                </div>
            </div>

            <!-- Card -->
            <div
                class="rounded-lg bg-white px-8 py-10 shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0px_0px_0px_1px_#fffaed2d]"
            >
                <h1
                    class="mb-1 text-center text-xl font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                >
                    Configuração Inicial
                </h1>
                <p
                    class="mb-6 text-center text-sm text-[#706f6c] dark:text-[#A1A09A]"
                >
                    Defina as credenciais do administrador
                </p>

                <form @submit.prevent="submit" class="flex flex-col gap-5">
                    <!-- Apelido (opcional) -->
                    <div class="flex flex-col gap-1.5">
                        <label
                            for="nickname"
                            class="text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            Apelido
                            <span class="text-[#706f6c] dark:text-[#A1A09A]"
                                >(Opcional)</span
                            >
                        </label>
                        <input
                            id="nickname"
                            v-model="form.nickname"
                            type="text"
                            autocomplete="nickname"
                            placeholder="Geraldo José"
                            class="w-full rounded-sm border border-[#e3e3e0] bg-[#FDFDFC] px-3 py-2 text-sm text-[#1b1b18] transition outline-none placeholder:text-[#b5b3ad] focus:border-[#1b1b18] focus:ring-1 focus:ring-[#1b1b18] disabled:opacity-50 dark:border-[#3E3E3A] dark:bg-[#1a1a18] dark:text-[#EDEDEC] dark:placeholder:text-[#55544f] dark:focus:border-[#EDEDEC] dark:focus:ring-[#EDEDEC]"
                            :class="{
                                'border-red-500 focus:border-red-500 focus:ring-red-500':
                                    form.errors.nickname,
                            }"
                        />
                        <p
                            v-if="form.errors.nickname"
                            class="text-xs text-red-500"
                        >
                            {{ form.errors.nickname }}
                        </p>
                    </div>

                    <!-- Tipo de validação -->
                    <div class="flex flex-col gap-1.5">
                        <label
                            for="validation_type"
                            class="text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            Tipo de validação do usuário
                            <span class="ml-0.5 text-red-500">*</span>
                        </label>
                        <select
                            id="validation_type"
                            v-model="form.validation_type"
                            class="w-full rounded-sm border border-[#e3e3e0] bg-[#FDFDFC] px-3 py-2 text-sm text-[#1b1b18] transition outline-none focus:border-[#1b1b18] focus:ring-1 focus:ring-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#1a1a18] dark:text-[#EDEDEC] dark:focus:border-[#EDEDEC] dark:focus:ring-[#EDEDEC]"
                            :class="{
                                'border-red-500 focus:border-red-500 focus:ring-red-500':
                                    form.errors.validation_type,
                            }"
                        >
                            <option value="cpf">CPF</option>
                            <option value="celular">Celular</option>
                            <option value="personalizado">Personalizado</option>
                        </select>
                        <p
                            v-if="form.errors.validation_type"
                            class="text-xs text-red-500"
                        >
                            {{ form.errors.validation_type }}
                        </p>
                    </div>

                    <!-- Padrão personalizado (apenas quando "personalizado" está selecionado) -->
                    <div
                        v-if="form.validation_type === 'personalizado'"
                        class="flex flex-col gap-1.5"
                    >
                        <label
                            for="custom_pattern"
                            class="text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            Expressão regular
                            <span class="ml-0.5 text-red-500">*</span>
                        </label>
                        <input
                            id="custom_pattern"
                            v-model="form.custom_pattern"
                            type="text"
                            placeholder="Ex: ^\d{5}$"
                            class="w-full rounded-sm border border-[#e3e3e0] bg-[#FDFDFC] px-3 py-2 font-mono text-sm text-[#1b1b18] transition outline-none placeholder:text-[#b5b3ad] focus:border-[#1b1b18] focus:ring-1 focus:ring-[#1b1b18] disabled:opacity-50 dark:border-[#3E3E3A] dark:bg-[#1a1a18] dark:text-[#EDEDEC] dark:placeholder:text-[#55544f] dark:focus:border-[#EDEDEC] dark:focus:ring-[#EDEDEC]"
                            :class="{
                                'border-red-500 focus:border-red-500 focus:ring-red-500':
                                    form.errors.custom_pattern ||
                                    customPatternError,
                            }"
                        />
                        <p
                            v-if="form.errors.custom_pattern"
                            class="text-xs text-red-500"
                        >
                            {{ form.errors.custom_pattern }}
                        </p>
                        <p
                            v-else-if="customPatternError"
                            class="text-xs text-red-500"
                        >
                            {{ customPatternError }}
                        </p>
                        <p
                            v-else
                            class="text-xs text-[#706f6c] dark:text-[#A1A09A]"
                        >
                            Insira uma expressão regular para validar o campo
                            usuário.
                        </p>
                    </div>

                    <!-- Usuário -->
                    <div class="flex flex-col gap-1.5">
                        <label
                            for="username"
                            class="text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            Usuário
                            <span class="ml-0.5 text-red-500">*</span>
                        </label>
                        <input
                            id="username"
                            v-model="form.username"
                            type="text"
                            autocomplete="username"
                            required
                            :placeholder="usernamePlaceholder"
                            class="w-full rounded-sm border border-[#e3e3e0] bg-[#FDFDFC] px-3 py-2 text-sm text-[#1b1b18] transition outline-none placeholder:text-[#b5b3ad] focus:border-[#1b1b18] focus:ring-1 focus:ring-[#1b1b18] disabled:opacity-50 dark:border-[#3E3E3A] dark:bg-[#1a1a18] dark:text-[#EDEDEC] dark:placeholder:text-[#55544f] dark:focus:border-[#EDEDEC] dark:focus:ring-[#EDEDEC]"
                            :class="{
                                'border-red-500 focus:border-red-500 focus:ring-red-500':
                                    form.errors.username ||
                                    (!form.errors.username &&
                                        !!clientUsernameError),
                            }"
                        />
                        <p
                            v-if="form.errors.username"
                            class="text-xs text-red-500"
                        >
                            {{ form.errors.username }}
                        </p>
                        <p
                            v-else-if="clientUsernameError"
                            class="text-xs text-red-500"
                        >
                            {{ clientUsernameError }}
                        </p>
                    </div>

                    <PasswordInput
                        id="password"
                        label="Senha"
                        :model-value="form.password"
                        :error="form.errors.password"
                        :required="true"
                        autocomplete="new-password"
                        @update:model-value="form.password = $event"
                    />

                    <PasswordInput
                        id="password_confirmation"
                        label="Confirmar senha"
                        :model-value="form.password_confirmation"
                        :error="form.errors.password_confirmation"
                        :required="true"
                        autocomplete="new-password"
                        @update:model-value="
                            form.password_confirmation = $event
                        "
                    />

                    <!-- Botão -->
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center justify-center gap-2 rounded-sm border border-black bg-[#1b1b18] px-5 py-2 text-sm leading-normal font-medium text-white transition hover:bg-black disabled:cursor-not-allowed disabled:opacity-60 dark:border-[#eeeeec] dark:bg-[#eeeeec] dark:text-[#1C1C1A] dark:hover:border-white dark:hover:bg-white"
                    >
                        <svg
                            v-if="!form.processing"
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-4 w-4"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
                            />
                        </svg>
                        <span v-if="form.processing">Configurando...</span>
                        <span v-else>Configurar administrador</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</template>
