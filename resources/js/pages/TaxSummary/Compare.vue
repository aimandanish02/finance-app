<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

interface YearData {
    claimable: number;
    total_spent: number;
    annual_limit: number | null;
}

interface CompareRow {
    deduction_type: string;
    deduction_label: string;
    years: Record<number, YearData | null>;
}

interface YearSummary {
    year: number;
    breakdown: Record<string, YearData>;
    totalClaimable: number;
    totalSpent: number;
}

const props = defineProps<{
    years: number[];
    rows: CompareRow[];
    byYear: Record<number, YearSummary>;
    availableYears: number[];
}>();

const fmt = (v: number) =>
    v.toLocaleString('en-MY', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

// Bar chart max across all years for proportional bars
const maxClaimable = computed(() =>
    Math.max(...props.years.map(y => props.byYear[y]?.totalClaimable ?? 0), 1)
);
const barWidth = (v: number) => Math.max(4, Math.round((v / maxClaimable.value) * 100));

// Year-on-year change
const yoyChange = (current: number, previous: number) => {
    if (previous <= 0) return null;
    return Math.round(((current - previous) / previous) * 100);
};

const yearColors = ['#6366f1', '#0f6e56', '#ba7517'];
</script>

<template>
    <Head title="Tax Comparison" />

    <div class="flex h-full flex-1 flex-col gap-6 p-6">

        <!-- Header -->
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-xl font-semibold text-gray-900 dark:text-white">Multi-Year Comparison</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Deductions across {{ years.join(', ') }}
                </p>
            </div>
            <Link href="/tax-summary"
                class="text-sm font-medium text-indigo-600 hover:underline dark:text-indigo-400">
                ← Back to summary
            </Link>
        </div>

        <!-- Year totals + bar chart -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div
                v-for="(year, i) in years" :key="year"
                class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800"
            >
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">YA{{ year }}</p>
                <p class="mt-2 text-2xl font-bold" :style="{ color: yearColors[i] }">
                    MYR {{ fmt(byYear[year]?.totalClaimable ?? 0) }}
                </p>
                <div class="mt-2 h-2 w-full rounded-full bg-gray-200 dark:bg-gray-700">
                    <div class="h-2 rounded-full transition-all duration-700"
                        :style="{ width: `${barWidth(byYear[year]?.totalClaimable ?? 0)}%`, backgroundColor: yearColors[i] }"/>
                </div>
                <div class="mt-2 flex items-center gap-2 text-xs text-gray-400">
                    <span>Total spent: MYR {{ fmt(byYear[year]?.totalSpent ?? 0) }}</span>
                    <span v-if="i > 0 && byYear[years[i - 1]]">
                        <span
                            :class="(byYear[year]?.totalClaimable ?? 0) >= (byYear[years[i-1]]?.totalClaimable ?? 0)
                                ? 'text-green-500' : 'text-red-500'"
                        >
                            {{ (byYear[year]?.totalClaimable ?? 0) >= (byYear[years[i-1]]?.totalClaimable ?? 0) ? '▲' : '▼' }}
                            {{ Math.abs(yoyChange(byYear[year]?.totalClaimable ?? 0, byYear[years[i-1]]?.totalClaimable ?? 0) ?? 0) }}%
                            vs YA{{ years[i-1] }}
                        </span>
                    </span>
                </div>
            </div>
        </div>

        <!-- Comparison table -->
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Deduction breakdown by year</h2>
                <p class="mt-0.5 text-xs text-gray-400">Claimable amount per LHDN category per year</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-700/30">
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 w-1/3">
                                Deduction type
                            </th>
                            <th
                                v-for="(year, i) in years" :key="year"
                                class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider"
                                :style="{ color: yearColors[i] }"
                            >YA{{ year }}</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                Trend
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-for="row in rows" :key="row.deduction_type"
                            class="hover:bg-gray-50 dark:hover:bg-gray-700/20 transition-colors">
                            <td class="px-6 py-3">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ row.deduction_label }}</p>
                            </td>
                            <td
                                v-for="(year, i) in years" :key="year"
                                class="px-6 py-3 text-right"
                            >
                                <span v-if="row.years[year]" class="text-sm font-medium text-gray-900 dark:text-white">
                                    MYR {{ fmt(row.years[year]!.claimable) }}
                                </span>
                                <span v-else class="text-sm text-gray-300 dark:text-gray-600">—</span>
                            </td>
                            <!-- Trend: compare most recent to oldest present -->
                            <td class="px-6 py-3 text-right">
                                <template v-if="years.length >= 2">
                                    <template
                                        v-if="row.years[years[0]] && row.years[years[years.length - 1]]"
                                    >
                                        <span
                                            class="text-xs font-medium"
                                            :class="row.years[years[0]]!.claimable >= row.years[years[years.length-1]]!.claimable
                                                ? 'text-green-600 dark:text-green-400'
                                                : 'text-red-600 dark:text-red-400'"
                                        >
                                            {{ row.years[years[0]]!.claimable >= row.years[years[years.length-1]]!.claimable ? '▲' : '▼' }}
                                            {{ Math.abs(yoyChange(
                                                row.years[years[0]]!.claimable,
                                                row.years[years[years.length-1]]!.claimable
                                            ) ?? 0) }}%
                                        </span>
                                    </template>
                                    <span v-else class="text-xs text-gray-300 dark:text-gray-600">—</span>
                                </template>
                            </td>
                        </tr>
                    </tbody>
                    <!-- Totals -->
                    <tfoot>
                        <tr class="border-t-2 border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/30">
                            <td class="px-6 py-3 text-sm font-semibold text-gray-900 dark:text-white">Total claimable</td>
                            <td
                                v-for="(year, i) in years" :key="year"
                                class="px-6 py-3 text-right text-base font-bold"
                                :style="{ color: yearColors[i] }"
                            >
                                MYR {{ fmt(byYear[year]?.totalClaimable ?? 0) }}
                            </td>
                            <td class="px-6 py-3 text-right">
                                <span v-if="years.length >= 2 && byYear[years[years.length-1]]?.totalClaimable"
                                    class="text-sm font-semibold"
                                    :class="(byYear[years[0]]?.totalClaimable ?? 0) >= (byYear[years[years.length-1]]?.totalClaimable ?? 0)
                                        ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'"
                                >
                                    {{ (byYear[years[0]]?.totalClaimable ?? 0) >= (byYear[years[years.length-1]]?.totalClaimable ?? 0) ? '▲' : '▼' }}
                                    {{ Math.abs(yoyChange(
                                        byYear[years[0]]?.totalClaimable ?? 0,
                                        byYear[years[years.length-1]]?.totalClaimable ?? 0
                                    ) ?? 0) }}%
                                </span>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Note if only one year of data -->
        <div v-if="years.length < 2"
            class="rounded-xl border border-amber-200 bg-amber-50 px-5 py-4 dark:border-amber-800 dark:bg-amber-900/20">
            <p class="text-sm text-amber-800 dark:text-amber-300">
                Only one year of data available. Add expenses for more years to see comparisons.
            </p>
        </div>

    </div>
</template>