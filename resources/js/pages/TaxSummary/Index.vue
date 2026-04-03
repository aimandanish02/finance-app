<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';

interface CategoryBreakdown {
    id: number;
    name: string;
    color: string | null;
    total: number;
}

interface DeductionRow {
    deduction_type: string;
    deduction_label: string;
    total_spent: number;
    annual_limit: number | null;
    claimable: number;
    over_limit: boolean;
    usage_pct: number | null;
    entries_count: number;
    categories: CategoryBreakdown[];
}

interface Props {
    year: number;
    availableYears: number[];
    breakdown: DeductionRow[];
    totalClaimable: number;
    totalSpent: number;
    nonDeductibleTotal: number;
    receiptsCount: number;
    categoriesOver: number;
}

const props = defineProps<Props>();

const fmt = (v: number) =>
    v.toLocaleString('en-MY', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const switchYear = (y: number) => {
    router.get('/tax-summary', { year: y }, { preserveState: true });
};

const statusClass = (row: DeductionRow) => {
    if (row.over_limit) return 'bg-red-100 text-red-800 dark:bg-red-800/30 dark:text-red-400';
    if (row.annual_limit === null) return 'bg-blue-100 text-blue-800 dark:bg-blue-800/30 dark:text-blue-400';
    return 'bg-green-100 text-green-800 dark:bg-green-800/30 dark:text-green-400';
};

const statusLabel = (row: DeductionRow) => {
    if (row.over_limit) return 'Over Limit';
    if (row.annual_limit === null) return 'No Limit';
    return 'Within Limit';
};

const progressColor = (row: DeductionRow) => {
    if (!row.usage_pct) return 'bg-indigo-500';
    if (row.usage_pct >= 100) return 'bg-red-500';
    if (row.usage_pct >= 80) return 'bg-amber-500';
    return 'bg-indigo-500';
};

const hasData = computed(() => props.breakdown.length > 0);
</script>

<template>
    <Head :title="`Tax Summary ${year}`" />

    <div class="flex h-full flex-1 flex-col gap-6 p-6">

        <!-- Header + year switcher -->
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-xl font-semibold text-gray-900 dark:text-white">Tax Summary</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    LHDN deduction breakdown for Year of Assessment {{ year }}
                </p>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-sm text-gray-500 dark:text-gray-400">Year:</span>
                <div class="flex rounded-lg border border-gray-300 dark:border-gray-600 overflow-hidden">
                    <button
                        v-for="y in availableYears"
                        :key="y"
                        type="button"
                        class="px-4 py-1.5 text-sm font-medium transition-colors"
                        :class="y === year
                            ? 'bg-indigo-600 text-white'
                            : 'bg-white text-gray-700 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700'"
                        @click="switchYear(y)"
                    >
                        {{ y }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Summary cards -->
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Total Claimable</p>
                <p class="mt-2 text-2xl font-bold text-indigo-600 dark:text-indigo-400">MYR {{ fmt(totalClaimable) }}</p>
                <p class="mt-1 text-xs text-gray-400">After applying LHDN limits</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Total Deductible Spent</p>
                <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">MYR {{ fmt(totalSpent) }}</p>
                <p class="mt-1 text-xs text-gray-400">Before limits applied</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Non-Deductible</p>
                <p class="mt-2 text-2xl font-bold text-gray-500 dark:text-gray-400">MYR {{ fmt(nonDeductibleTotal) }}</p>
                <p class="mt-1 text-xs text-gray-400">No tax benefit</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Receipts Uploaded</p>
                <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">{{ receiptsCount }}</p>
                <p class="mt-1 text-xs" :class="categoriesOver > 0 ? 'text-red-500' : 'text-gray-400'">
                    {{ categoriesOver > 0 ? `${categoriesOver} category over limit` : 'All within limits' }}
                </p>
            </div>
        </div>

        <!-- Breakdown table -->
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Deduction Breakdown</h2>
                <p class="mt-0.5 text-xs text-gray-400">Click any row to see individual expenses</p>
            </div>

            <div v-if="hasData">
                <div
                    v-for="row in breakdown"
                    :key="row.deduction_type"
                    class="border-b border-gray-200 dark:border-gray-700 last:border-0"
                >
                    <Link
                        :href="`/tax-summary/${row.deduction_type}?year=${year}`"
                        class="block px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors"
                    >
                        <div class="flex flex-wrap items-start justify-between gap-4">

                            <!-- Left: label + category dots -->
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ row.deduction_label }}
                                    </p>
                                    <span :class="['inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium', statusClass(row)]">
                                        {{ statusLabel(row) }}
                                    </span>
                                </div>
                                <!-- Category breakdown dots -->
                                <div class="mt-1.5 flex flex-wrap gap-2">
                                    <span
                                        v-for="cat in row.categories"
                                        :key="cat.id"
                                        class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs text-white"
                                        :style="{ backgroundColor: cat.color ?? '#94a3b8' }"
                                    >
                                        {{ cat.name }}: MYR {{ fmt(cat.total) }}
                                    </span>
                                </div>
                                <!-- Progress bar -->
                                <div v-if="row.annual_limit !== null" class="mt-2">
                                    <div class="flex justify-between text-xs text-gray-400 mb-1">
                                        <span>MYR {{ fmt(row.total_spent) }} spent</span>
                                        <span>Limit: MYR {{ fmt(row.annual_limit) }}</span>
                                    </div>
                                    <div class="h-1.5 w-full rounded-full bg-gray-200 dark:bg-gray-700">
                                        <div
                                            class="h-1.5 rounded-full transition-all duration-500"
                                            :class="progressColor(row)"
                                            :style="{ width: `${Math.min(row.usage_pct ?? 0, 100)}%` }"
                                        />
                                    </div>
                                    <p v-if="row.over_limit" class="mt-1 text-xs text-red-500">
                                        MYR {{ fmt(row.total_spent - row.annual_limit) }} over the annual limit — only MYR {{ fmt(row.claimable) }} is claimable
                                    </p>
                                </div>
                            </div>

                            <!-- Right: amounts + entries -->
                            <div class="flex flex-col items-end gap-1 flex-shrink-0">
                                <p class="text-base font-bold text-indigo-600 dark:text-indigo-400">
                                    MYR {{ fmt(row.claimable) }}
                                    <span class="text-xs font-normal text-gray-400 ml-1">claimable</span>
                                </p>
                                <p v-if="row.over_limit" class="text-xs text-red-500 line-through">
                                    MYR {{ fmt(row.total_spent) }}
                                </p>
                                <p class="text-xs text-gray-400">{{ row.entries_count }} expense{{ row.entries_count !== 1 ? 's' : '' }}</p>
                                <svg class="h-4 w-4 text-gray-400 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </div>

                        </div>
                    </Link>
                </div>

                <!-- Grand total row -->
                <div class="flex items-center justify-between bg-gray-50 px-6 py-4 dark:bg-gray-700/30">
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">Total Claimable Deductions</p>
                    <p class="text-lg font-bold text-indigo-600 dark:text-indigo-400">MYR {{ fmt(totalClaimable) }}</p>
                </div>
            </div>

            <!-- Empty state -->
            <div v-else class="px-6 py-20 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <p class="mt-4 text-base font-medium text-gray-900 dark:text-white">No deductible expenses for {{ year }}</p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Add expenses under tax-deductible categories to see your summary here.
                </p>
                <Link
                    href="/expenses/create"
                    class="mt-4 inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 transition-colors"
                >
                    Add Expense
                </Link>
            </div>
        </div>

    </div>
</template>