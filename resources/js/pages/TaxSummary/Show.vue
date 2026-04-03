<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

interface Expense {
    id: number;
    title: string;
    amount: number;
    expense_date: string;
    description: string | null;
    receipts_count: number;
    category: {
        id: number;
        name: string;
        color: string | null;
    };
}

interface Props {
    year: number;
    deductionType: string;
    deductionLabel: string;
    expenses: Expense[];
    totalSpent: number;
    annualLimit: number | null;
    claimable: number;
    overLimit: boolean;
}

const props = defineProps<Props>();

const fmt = (v: number) =>
    v.toLocaleString('en-MY', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const fmtDate = (d: string) =>
    new Date(d + 'T00:00:00').toLocaleDateString('en-MY', {
        year: 'numeric', month: 'short', day: 'numeric',
    });

const usagePct = props.annualLimit
    ? Math.min(100, Math.round((props.totalSpent / props.annualLimit) * 100))
    : null;

const progressColor = () => {
    if (!usagePct) return 'bg-indigo-500';
    if (usagePct >= 100) return 'bg-red-500';
    if (usagePct >= 80) return 'bg-amber-500';
    return 'bg-indigo-500';
};
</script>

<template>
    <Head :title="`${deductionLabel} — ${year}`" />

    <div class="flex h-full flex-1 flex-col gap-6 p-6">
        <div class="mx-auto w-full max-w-3xl space-y-5">

            <!-- Summary card -->
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">

                <!-- Header -->
                <div class="flex items-start justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Year of Assessment {{ year }}</p>
                        <h2 class="mt-0.5 text-lg font-semibold text-gray-900 dark:text-white">{{ deductionLabel }}</h2>
                        <code class="text-xs text-gray-400">{{ deductionType }}</code>
                    </div>
                    <span
                        class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium"
                        :class="overLimit
                            ? 'bg-red-100 text-red-800 dark:bg-red-800/30 dark:text-red-400'
                            : annualLimit === null
                                ? 'bg-blue-100 text-blue-800 dark:bg-blue-800/30 dark:text-blue-400'
                                : 'bg-green-100 text-green-800 dark:bg-green-800/30 dark:text-green-400'"
                    >
                        {{ overLimit ? 'Over Limit' : annualLimit === null ? 'No Limit' : 'Within Limit' }}
                    </span>
                </div>

                <!-- Amount summary -->
                <div class="grid grid-cols-3 divide-x divide-gray-200 dark:divide-gray-700 border-b border-gray-200 dark:border-gray-700">
                    <div class="px-6 py-4">
                        <p class="text-xs text-gray-500 dark:text-gray-400">Total Spent</p>
                        <p class="mt-1 text-xl font-bold text-gray-900 dark:text-white">MYR {{ fmt(totalSpent) }}</p>
                    </div>
                    <div class="px-6 py-4">
                        <p class="text-xs text-gray-500 dark:text-gray-400">Annual Limit</p>
                        <p class="mt-1 text-xl font-bold text-gray-900 dark:text-white">
                            {{ annualLimit !== null ? `MYR ${fmt(annualLimit)}` : 'Unlimited' }}
                        </p>
                    </div>
                    <div class="px-6 py-4">
                        <p class="text-xs text-gray-500 dark:text-gray-400">Claimable</p>
                        <p class="mt-1 text-xl font-bold text-indigo-600 dark:text-indigo-400">MYR {{ fmt(claimable) }}</p>
                    </div>
                </div>

                <!-- Progress bar -->
                <div v-if="annualLimit !== null" class="px-6 py-4">
                    <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mb-2">
                        <span>{{ usagePct }}% of annual limit used</span>
                        <span v-if="overLimit" class="text-red-500 font-medium">
                            MYR {{ fmt(totalSpent - annualLimit) }} over limit (not claimable)
                        </span>
                        <span v-else class="text-gray-400">
                            MYR {{ fmt(annualLimit - totalSpent) }} remaining
                        </span>
                    </div>
                    <div class="h-2.5 w-full rounded-full bg-gray-200 dark:bg-gray-700">
                        <div
                            class="h-2.5 rounded-full transition-all duration-700"
                            :class="progressColor()"
                            :style="{ width: `${usagePct}%` }"
                        />
                    </div>
                </div>
            </div>

            <!-- Expenses list -->
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                        Expenses
                        <span class="ml-1 text-xs font-normal text-gray-400">({{ expenses.length }} entries)</span>
                    </h3>
                </div>

                <ul v-if="expenses.length > 0" class="divide-y divide-gray-200 dark:divide-gray-700">
                    <li
                        v-for="expense in expenses"
                        :key="expense.id"
                        class="flex items-center justify-between px-6 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors"
                    >
                        <div class="flex items-center gap-3 min-w-0">
                            <span
                                class="h-2.5 w-2.5 flex-shrink-0 rounded-full"
                                :style="{ backgroundColor: expense.category.color ?? '#94a3b8' }"
                            />
                            <div class="min-w-0">
                                <Link
                                    :href="`/expenses/${expense.id}`"
                                    class="truncate text-sm font-medium text-gray-900 hover:text-indigo-600 dark:text-white dark:hover:text-indigo-400"
                                >
                                    {{ expense.title }}
                                </Link>
                                <div class="flex items-center gap-2 text-xs text-gray-400">
                                    <span>{{ fmtDate(expense.expense_date) }}</span>
                                    <span>·</span>
                                    <span>{{ expense.category.name }}</span>
                                    <span v-if="expense.receipts_count > 0">·</span>
                                    <span v-if="expense.receipts_count > 0">
                                        {{ expense.receipts_count }} receipt{{ expense.receipts_count !== 1 ? 's' : '' }}
                                    </span>
                                </div>
                                <p v-if="expense.description" class="truncate text-xs text-gray-400 max-w-sm">
                                    {{ expense.description }}
                                </p>
                            </div>
                        </div>
                        <span class="ml-4 flex-shrink-0 text-sm font-semibold text-red-600 dark:text-red-400">
                            - MYR {{ fmt(expense.amount) }}
                        </span>
                    </li>
                </ul>

                <div v-else class="px-6 py-12 text-center text-sm text-gray-400">
                    No expenses recorded under this deduction type for {{ year }}.
                </div>
            </div>

            <!-- Navigation -->
            <div class="flex items-center justify-between">
                <Link
                    :href="`/tax-summary?year=${year}`"
                    class="text-sm font-medium text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white"
                >
                    ← Back to Tax Summary
                </Link>
                <Link
                    href="/expenses/create"
                    class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                >
                    Add Expense
                </Link>
            </div>

        </div>
    </div>
</template>