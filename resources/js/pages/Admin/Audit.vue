<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import AppHeader from '@/components/AppHeader.vue';

type LogEntry = {
    id: number;
    actor_id: number | null;
    event: string;
    target_username: string | null;
    ip_address: string | null;
    created_at: string;
    actor: { id: number; username: string; nickname?: string } | null;
};

type PaginatedLogs = {
    data: LogEntry[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
};

const props = defineProps<{
    auth: {
        user: {
            username: string;
            nickname?: string;
            is_admin: boolean;
        };
    };
    logs: PaginatedLogs;
    filters: { event?: string; search?: string };
}>();

const search = ref(props.filters.search ?? '');
const filterEvent = ref(props.filters.event ?? '');

const allEvents = [
    'login_success',
    'login_failed',
    'logout',
    'user_created',
    'user_updated',
    'user_deleted',
    'profile_updated',
];

const eventLabels: Record<string, string> = {
    login_success: 'Login realizado',
    login_failed: 'Login falhou',
    logout: 'Logout',
    user_created: 'Usuário criado',
    user_updated: 'Usuário atualizado',
    user_deleted: 'Usuário excluído',
    profile_updated: 'Perfil atualizado',
};

const eventColors: Record<string, string> = {
    login_success:
        'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-400',
    login_failed: 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-400',
    logout: 'bg-[#f5f5f3] text-[#706f6c] dark:bg-[#1e1e1c] dark:text-[#A1A09A]',
    user_created:
        'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-400',
    user_updated:
        'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-400',
    user_deleted:
        'bg-orange-100 text-orange-700 dark:bg-orange-900 dark:text-orange-400',
    profile_updated:
        'bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-400',
};

let searchTimer: ReturnType<typeof setTimeout> | null = null;

function applyFilters(page = 1) {
    router.get(
        '/admin/audit',
        {
            event: filterEvent.value || undefined,
            search: search.value || undefined,
            page: page > 1 ? page : undefined,
        },
        { preserveState: true, replace: true },
    );
}

watch(filterEvent, () => applyFilters());

watch(search, () => {
    if (searchTimer) {
        clearTimeout(searchTimer);
    }

    searchTimer = setTimeout(() => applyFilters(), 300);
});

function goToPage(url: string | null) {
    if (!url) {
        return;
    }

    const page = parseInt(new URL(url).searchParams.get('page') ?? '1', 10);
    applyFilters(page);
}

function formatDate(dateStr: string): string {
    return new Date(dateStr).toLocaleDateString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    });
}

function decodeLabel(label: string): string {
    return label
        .replace(/&laquo;/g, '«')
        .replace(/&raquo;/g, '»')
        .replace(/&amp;/g, '&')
        .replace(/&lt;/g, '<')
        .replace(/&gt;/g, '>');
}

function actorDisplay(log: LogEntry): string {
    if (log.actor) {
        return log.actor.nickname
            ? `${log.actor.nickname} (${log.actor.username})`
            : log.actor.username;
    }

    return log.event === 'login_failed' ? '—' : 'Usuário excluído';
}
</script>

<template>
    <Head title="Auditoria" />

    <div
        class="flex min-h-screen flex-col bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a]"
    >
        <AppHeader :user="auth.user" />

        <main class="flex-1 p-6">
            <div class="mx-auto max-w-6xl">
                <div class="mb-6">
                    <div class="mb-1 flex items-center gap-2">
                        <a
                            href="/home"
                            class="flex items-center gap-1.5 text-sm text-[#706f6c] transition hover:text-[#1b1b18] dark:text-[#A1A09A] dark:hover:text-[#EDEDEC]"
                        >
                            <svg
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
                                    d="M15 19l-7-7 7-7"
                                />
                            </svg>
                            Voltar
                        </a>
                    </div>
                    <h1
                        class="text-2xl font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                    >
                        Auditoria
                    </h1>
                    <p class="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                        {{ logs.total }}
                        {{
                            logs.total === 1
                                ? 'evento registrado'
                                : 'eventos registrados'
                        }}
                        no sistema.
                    </p>
                </div>

                <div class="mb-4 flex flex-col gap-3 sm:flex-row">
                    <div class="relative flex-1">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-[#b5b3ad] dark:text-[#55544f]"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                            />
                        </svg>
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Buscar por usuário ou IP..."
                            class="w-full rounded-sm border border-[#e3e3e0] bg-[#FDFDFC] py-2 pr-3 pl-9 text-sm text-[#1b1b18] transition outline-none placeholder:text-[#b5b3ad] focus:border-[#1b1b18] focus:ring-1 focus:ring-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#1a1a18] dark:text-[#EDEDEC] dark:placeholder:text-[#55544f] dark:focus:border-[#EDEDEC] dark:focus:ring-[#EDEDEC]"
                        />
                    </div>
                    <select
                        v-model="filterEvent"
                        class="rounded-sm border border-[#e3e3e0] bg-[#FDFDFC] px-3 py-2 text-sm text-[#1b1b18] transition outline-none focus:border-[#1b1b18] focus:ring-1 focus:ring-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#1a1a18] dark:text-[#EDEDEC] dark:focus:border-[#EDEDEC] dark:focus:ring-[#EDEDEC]"
                    >
                        <option value="">Todos os eventos</option>
                        <option
                            v-for="evt in allEvents"
                            :key="evt"
                            :value="evt"
                        >
                            {{ eventLabels[evt] }}
                        </option>
                    </select>
                </div>

                <div
                    class="overflow-hidden rounded-lg shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] dark:shadow-[inset_0px_0px_0px_1px_#fffaed2d]"
                >
                    <div
                        v-if="logs.data.length === 0"
                        class="flex flex-col items-center justify-center bg-white py-16 dark:bg-[#161615]"
                    >
                        <div
                            class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-[#f5f5f3] dark:bg-[#1e1e1c]"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-6 w-6 text-[#706f6c] dark:text-[#A1A09A]"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="1.5"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                                />
                            </svg>
                        </div>
                        <p
                            class="text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            Nenhum evento encontrado
                        </p>
                        <p
                            class="mt-1 text-xs text-[#706f6c] dark:text-[#A1A09A]"
                        >
                            Ajuste os filtros ou aguarde novos registros.
                        </p>
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table
                            class="w-full bg-white text-sm dark:bg-[#161615]"
                        >
                            <thead>
                                <tr
                                    class="border-b border-[#e3e3e0] dark:border-[#3E3E3A]"
                                >
                                    <th
                                        class="px-6 py-3 text-left text-xs font-semibold tracking-wide text-[#706f6c] uppercase dark:text-[#A1A09A]"
                                    >
                                        Data
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-semibold tracking-wide text-[#706f6c] uppercase dark:text-[#A1A09A]"
                                    >
                                        Evento
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-semibold tracking-wide text-[#706f6c] uppercase dark:text-[#A1A09A]"
                                    >
                                        Autor
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-semibold tracking-wide text-[#706f6c] uppercase dark:text-[#A1A09A]"
                                    >
                                        Alvo
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-semibold tracking-wide text-[#706f6c] uppercase dark:text-[#A1A09A]"
                                    >
                                        IP
                                    </th>
                                </tr>
                            </thead>
                            <tbody
                                class="divide-y divide-[#e3e3e0] dark:divide-[#3E3E3A]"
                            >
                                <tr
                                    v-for="log in logs.data"
                                    :key="log.id"
                                    class="transition hover:bg-[#f9f9f8] dark:hover:bg-[#1a1a18]"
                                >
                                    <td
                                        class="px-6 py-3.5 text-xs whitespace-nowrap text-[#706f6c] dark:text-[#A1A09A]"
                                    >
                                        {{ formatDate(log.created_at) }}
                                    </td>
                                    <td class="px-6 py-3.5">
                                        <span
                                            class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                            :class="
                                                eventColors[log.event] ??
                                                'bg-[#f5f5f3] text-[#706f6c] dark:bg-[#1e1e1c] dark:text-[#A1A09A]'
                                            "
                                        >
                                            {{
                                                eventLabels[log.event] ??
                                                log.event
                                            }}
                                        </span>
                                    </td>
                                    <td
                                        class="px-6 py-3.5 text-sm text-[#1b1b18] dark:text-[#EDEDEC]"
                                    >
                                        {{ actorDisplay(log) }}
                                    </td>
                                    <td
                                        class="px-6 py-3.5 text-sm text-[#706f6c] dark:text-[#A1A09A]"
                                    >
                                        {{ log.target_username ?? '—' }}
                                    </td>
                                    <td
                                        class="px-6 py-3.5 font-mono text-xs whitespace-nowrap text-[#706f6c] dark:text-[#A1A09A]"
                                    >
                                        {{ log.ip_address ?? '—' }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div
                    v-if="logs.last_page > 1"
                    class="mt-4 flex items-center justify-between text-sm"
                >
                    <p class="text-[#706f6c] dark:text-[#A1A09A]">
                        Página {{ logs.current_page }} de {{ logs.last_page }}
                        <span class="ml-1">({{ logs.total }} eventos)</span>
                    </p>
                    <div class="flex items-center gap-1">
                        <template v-for="link in logs.links" :key="link.label">
                            <button
                                v-if="link.url"
                                type="button"
                                class="min-w-[32px] rounded-sm border px-2 py-1 text-xs transition"
                                :class="
                                    link.active
                                        ? 'border-[#1b1b18] bg-[#1b1b18] text-white dark:border-[#EDEDEC] dark:bg-[#EDEDEC] dark:text-[#1b1b18]'
                                        : 'border-[#e3e3e0] bg-white text-[#1b1b18] hover:bg-[#f5f5f3] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] dark:hover:bg-[#1e1e1c]'
                                "
                                @click="goToPage(link.url)"
                                v-text="decodeLabel(link.label)"
                            />
                            <span
                                v-else
                                class="min-w-[32px] rounded-sm border border-[#e3e3e0] px-2 py-1 text-center text-xs text-[#b5b3ad] dark:border-[#3E3E3A] dark:text-[#55544f]"
                                v-text="decodeLabel(link.label)"
                            />
                        </template>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>
