<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';

interface Budget {
    id: number;
    label: string;
    is_overall: boolean;
    category: { name: string; color: string; code: string } | null;
    amount: number;
    spent: number;
    remaining: number;
    pct: number;
    status: 'ok' | 'warning' | 'exceeded';
}

defineProps<{
    budgets: Budget[];
    currentMonth: string;
}>();

const fmt = (v: number) =>
    v.toLocaleString('en-MY', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const progressColor = (status: string) => {
    if (status === 'exceeded') return 'bg-red-500';
    if (status === 'warning') return 'bg-amber-500';
    return 'bg-indigo-500';
};

const statusClass = (status: string) => {
    if (status === 'exceeded') return 'bg-red-100 text-red-800 dark:bg-red-800/30 dark:text-red-400';
    if (status === 'warning') return 'bg-amber-100 text-amber-800 dark:bg-amber-800/30 dark:text-amber-400';
    return 'bg-green-100 text-green-800 dark:bg-green-800/30 dark:text-green-400';
};

const deleteBudget = (id: number, label: string) => {
    if (confirm(`Delete budget "${label}"?`)) {
        router.delete(`/budgets/${id}`);
    }
};
</script>

<template>
    <Head title="Budgets" />

    <div class="flex h-full flex-1 flex-col gap-6 p-6">

        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold text-gray-900 dark:text-white">Budgets</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Monthly spending limits — {{ currentMonth }}</p>
            </div>
            <Link
                href="/budgets/create"
                class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 transition-colors"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                New Budget
            </Link>
        </div>

        <!-- Budget cards -->
        <div v-if="budgets.length > 0" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div
                v-for="budget in budgets"
                :key="budget.id"
                class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800"
            >
                <!-- Header -->
                <div class="flex items-start justify-between mb-3">
                    <div class="flex items-center gap-2 min-w-0">
                        <span
                            class="h-3 w-3 rounded-full flex-shrink-0"
                            :style="{ backgroundColor: budget.category?.color ?? '#6366f1' }"
                        />
                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ budget.label }}</p>
                    </div>
                    <span :class="['inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium flex-shrink-0 ml-2', statusClass(budget.status)]">
                        {{ budget.status === 'exceeded' ? 'Over' : budget.status === 'warning' ? 'Alert' : 'OK' }}
                    </span>
                </div>

                <!-- Amounts -->
                <div class="mb-3">
                    <div class="flex items-baseline justify-between">
                        <span class="text-2xl font-bold text-gray-900 dark:text-white">MYR {{ fmt(budget.spent) }}</span>
                        <span class="text-sm text-gray-400">of MYR {{ fmt(budget.amount) }}</span>
                    </div>
                    <p class="mt-0.5 text-xs" :class="budget.status === 'exceeded' ? 'text-red-500' : 'text-gray-400'">
                        {{ budget.status === 'exceeded'
                            ? `MYR ${fmt(budget.spent - budget.amount)} over budget`
                            : `MYR ${fmt(budget.remaining)} remaining` }}
                    </p>
                </div>

                <!-- Progress bar -->
                <div class="mb-4">
                    <div class="flex justify-between text-xs text-gray-400 mb-1">
                        <span>{{ budget.pct }}% used</span>
                    </div>
                    <div class="h-2 w-full rounded-full bg-gray-200 dark:bg-gray-700">
                        <div
                            class="h-2 rounded-full transition-all duration-500"
                            :class="progressColor(budget.status)"
                            :style="{ width: `${budget.pct}%` }"
                        />
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-3">
                    <Link
                        :href="`/budgets/${budget.id}`"
                        class="text-sm font-medium text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                    >View</Link>
                    <Link
                        :href="`/budgets/${budget.id}/edit`"
                        class="text-sm font-medium text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-300"
                    >Edit</Link>
                    <button
                        type="button"
                        class="text-sm font-medium text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                        @click="deleteBudget(budget.id, budget.label)"
                    >Delete</button>
                </div>
            </div>
        </div>

        <!-- Empty state -->
        <div v-else class="rounded-xl border border-gray-200 bg-white p-16 text-center shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 11h.01M12 11h.01M15 11h.01M4 5h16a1 1 0 011 1v12a1 1 0 01-1 1H4a1 1 0 01-1-1V6a1 1 0 011-1z" />
            </svg>
            <p class="mt-4 text-base font-medium text-gray-900 dark:text-white">No budgets set</p>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Set monthly spending limits to stay on track.</p>
            <Link
                href="/budgets/create"
                class="mt-4 inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 transition-colors"
            >
                Create Budget
            </Link>
        </div>

    </div>
</template>