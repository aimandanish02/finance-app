<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';

interface HistoryPoint {
    label: string;
    month: string;
    spent: number;
    budget: number;
    pct: number;
}

interface BudgetDetail {
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

const props = defineProps<{
    budget: BudgetDetail;
    history: HistoryPoint[];
    currentMonth: string;
}>();

const fmt = (v: number) =>
    v.toLocaleString('en-MY', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const BAR_H = 120;
const chartMax = computed(() => Math.max(...props.history.map(h => Math.max(h.spent, h.budget)), 1));
const barHeight = (v: number) => Math.max(4, Math.round((v / chartMax.value) * BAR_H));

const progressColor = computed(() => {
    if (props.budget.status === 'exceeded') return 'bg-red-500';
    if (props.budget.status === 'warning') return 'bg-amber-500';
    return 'bg-indigo-500';
});

const deleteBudget = () => {
    if (confirm(`Delete budget "${props.budget.label}"?`)) {
        router.delete(`/budgets/${props.budget.id}`);
    }
};
</script>

<template>
    <Head :title="budget.label" />

    <div class="flex h-full flex-1 flex-col gap-6 p-6">
        <div class="mx-auto w-full max-w-2xl space-y-5">

            <!-- Main card -->
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">

                <!-- Header -->
                <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                    <div class="flex items-center gap-3">
                        <span
                            class="h-4 w-4 rounded-full"
                            :style="{ backgroundColor: budget.category?.color ?? '#6366f1' }"
                        />
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ budget.label }}</h2>
                    </div>
                    <span
                        class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium"
                        :class="{
                            'bg-red-100 text-red-800 dark:bg-red-800/30 dark:text-red-400': budget.status === 'exceeded',
                            'bg-amber-100 text-amber-800 dark:bg-amber-800/30 dark:text-amber-400': budget.status === 'warning',
                            'bg-green-100 text-green-800 dark:bg-green-800/30 dark:text-green-400': budget.status === 'ok',
                        }"
                    >
                        {{ budget.status === 'exceeded' ? 'Over budget' : budget.status === 'warning' ? 'Approaching limit' : 'On track' }}
                    </span>
                </div>

                <!-- Totals -->
                <div class="grid grid-cols-3 divide-x divide-gray-200 dark:divide-gray-700 border-b border-gray-200 dark:border-gray-700">
                    <div class="px-6 py-4">
                        <p class="text-xs text-gray-500 dark:text-gray-400">Spent</p>
                        <p class="mt-1 text-xl font-bold text-red-600 dark:text-red-400">MYR {{ fmt(budget.spent) }}</p>
                    </div>
                    <div class="px-6 py-4">
                        <p class="text-xs text-gray-500 dark:text-gray-400">Budget</p>
                        <p class="mt-1 text-xl font-bold text-gray-900 dark:text-white">MYR {{ fmt(budget.amount) }}</p>
                    </div>
                    <div class="px-6 py-4">
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ budget.status === 'exceeded' ? 'Over by' : 'Remaining' }}
                        </p>
                        <p
                            class="mt-1 text-xl font-bold"
                            :class="budget.status === 'exceeded' ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400'"
                        >
                            MYR {{ fmt(budget.status === 'exceeded' ? budget.spent - budget.amount : budget.remaining) }}
                        </p>
                    </div>
                </div>

                <!-- Progress -->
                <div class="px-6 py-4">
                    <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mb-2">
                        <span>{{ budget.pct }}% of monthly budget used</span>
                        <span>{{ currentMonth }}</span>
                    </div>
                    <div class="h-3 w-full rounded-full bg-gray-200 dark:bg-gray-700">
                        <div
                            class="h-3 rounded-full transition-all duration-700"
                            :class="progressColor"
                            :style="{ width: `${budget.pct}%` }"
                        />
                    </div>
                </div>
            </div>

            <!-- 6-month history chart -->
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-6">6-Month History</h3>

                <div class="flex items-end justify-between gap-3" :style="`height: ${BAR_H + 4}px`">
                    <div
                        v-for="point in history"
                        :key="point.month"
                        class="flex flex-1 flex-col items-center"
                    >
                        <div class="flex w-full items-end justify-center gap-1" :style="`height: ${BAR_H}px`">
                            <!-- Budget line (as thin bar) -->
                            <div
                                class="w-1 rounded-t-sm bg-gray-300 dark:bg-gray-600"
                                :style="`height: ${barHeight(point.budget)}px`"
                                :title="`Budget: MYR ${fmt(point.budget)}`"
                            />
                            <!-- Spent bar -->
                            <div
                                class="flex-1 rounded-t-sm transition-all duration-500"
                                :class="point.pct >= 100 ? 'bg-red-400' : point.pct >= 80 ? 'bg-amber-400' : 'bg-indigo-400'"
                                :style="`height: ${barHeight(point.spent)}px`"
                                :title="`Spent: MYR ${fmt(point.spent)}`"
                            />
                        </div>
                    </div>
                </div>
                <div class="mt-2 flex justify-between gap-3">
                    <div
                        v-for="point in history"
                        :key="point.month + '-label'"
                        class="flex-1 text-center text-[10px] text-gray-400"
                    >
                        {{ point.label.slice(0, 3) }}
                    </div>
                </div>
                <div class="mt-3 flex items-center justify-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                    <span class="flex items-center gap-1.5"><span class="inline-block h-2.5 w-2.5 rounded-sm bg-indigo-400" />Spent</span>
                    <span class="flex items-center gap-1.5"><span class="inline-block h-2.5 w-1 rounded-sm bg-gray-300 dark:bg-gray-600" />Budget</span>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-between">
                <Link href="/budgets" class="text-sm font-medium text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">
                    ← Back to Budgets
                </Link>
                <div class="flex gap-3">
                    <Link
                        :href="`/budgets/${budget.id}/edit`"
                        class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                    >Edit</Link>
                    <button
                        type="button"
                        class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 transition-colors"
                        @click="deleteBudget"
                    >Delete</button>
                </div>
            </div>

        </div>
    </div>
</template>