<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Stats {
    totalExpenses: number;
    totalIncome: number;
    netBalance: number;
    totalCount: number;
    monthExpenses: number;
    monthIncome: number;
    monthNet: number;
}

interface MonthlyPoint {
    month: string;
    label: string;
    expenses: number;
    income: number;
}

interface CategoryBreakdown {
    name: string;
    color: string;
    total: number;
}

interface RecentEntry {
    id: number;
    title: string;
    amount: number;
    type: 'income' | 'expense';
    expense_date: string;
    category: { name: string; color: string } | null;
}

interface BudgetAlert {
    id: number;
    label: string;
    is_overall: boolean;
    color: string;
    amount: number;
    spent: number;
    pct: number;
    status: 'warning' | 'exceeded';
}

interface Props {
    stats: Stats;
    monthlyChart: MonthlyPoint[];
    byCategory: CategoryBreakdown[];
    recent: RecentEntry[];
    budgetAlerts: BudgetAlert[];
}

const props = defineProps<Props>();

const fmt = (value: number) =>
    value.toLocaleString('en-MY', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const fmtDate = (d: string) =>
    new Date(d + 'T00:00:00').toLocaleDateString('en-MY', { month: 'short', day: 'numeric' });

const currentMonth = new Date().toLocaleString('en-MY', { month: 'long', year: 'numeric' });

// ── Bar chart geometry ─────────────────────────────────────────────────────
const BAR_H = 180; // chart drawing height in px

const chartMax = computed(() => {
    const max = Math.max(...props.monthlyChart.flatMap(m => [m.expenses, m.income]), 1);
    // round up to a clean number
    const magnitude = Math.pow(10, Math.floor(Math.log10(max)));
    return Math.ceil(max / magnitude) * magnitude;
});

const barHeight = (value: number) =>
    Math.max(4, Math.round((value / chartMax.value) * BAR_H));

// ── Category donut arcs ────────────────────────────────────────────────────
const categoryTotal = computed(() =>
    props.byCategory.reduce((sum, c) => sum + c.total, 0)
);

const categoryPct = (total: number) =>
    categoryTotal.value > 0 ? Math.round((total / categoryTotal.value) * 100) : 0;

// SVG donut — 36-unit radius circle, circumference ≈ 226
const CIRC = 2 * Math.PI * 15.9155; // r=15.9155 → C=100 (easy percentages)

const donutSegments = computed(() => {
    let offset = 0; // start at top (-25 rotation applied via CSS)
    return props.byCategory.map(c => {
        const pct = categoryTotal.value > 0 ? (c.total / categoryTotal.value) * 100 : 0;
        const seg = { color: c.color, dasharray: `${pct} ${100 - pct}`, dashoffset: -offset };
        offset += pct;
        return seg;
    });
});
</script>

<template>
    <Head title="Dashboard" />

        <div class="flex flex-col gap-6 p-6">

            <!-- ── Budget alerts ───────────────────────────────────────── -->
            <div v-if="budgetAlerts.length > 0" class="space-y-2">
                <div
                    v-for="alert in budgetAlerts"
                    :key="alert.id"
                    class="flex items-center justify-between rounded-xl px-5 py-3"
                    :class="alert.status === 'exceeded'
                        ? 'border border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-900/20'
                        : 'border border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-900/20'"
                >
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="h-3 w-3 rounded-full flex-shrink-0" :style="{ backgroundColor: alert.color }" />
                        <div class="min-w-0">
                            <p class="text-sm font-medium truncate" :class="alert.status === 'exceeded' ? 'text-red-900 dark:text-red-300' : 'text-amber-900 dark:text-amber-300'">
                                {{ alert.status === 'exceeded' ? '⚠ Over budget:' : '⚡ Approaching limit:' }}
                                {{ alert.label }}
                            </p>
                            <p class="text-xs mt-0.5" :class="alert.status === 'exceeded' ? 'text-red-600 dark:text-red-400' : 'text-amber-600 dark:text-amber-400'">
                                MYR {{ fmt(alert.spent) }} spent of MYR {{ fmt(alert.amount) }} budget ({{ alert.pct }}%)
                            </p>
                        </div>
                    </div>
                    <Link
                        :href="`/budgets/${alert.id}`"
                        class="ml-4 flex-shrink-0 text-xs font-medium underline"
                        :class="alert.status === 'exceeded' ? 'text-red-700 dark:text-red-400' : 'text-amber-700 dark:text-amber-400'"
                    >
                        View
                    </Link>
                </div>
            </div>

            <!-- ── Summary stat cards ──────────────────────────────────── -->
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">

                <!-- Net balance (all time) -->
                <div class="col-span-2 lg:col-span-1 rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Net Balance</p>
                    <p
                        class="mt-2 text-3xl font-bold"
                        :class="stats.netBalance >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'"
                    >
                        {{ stats.netBalance >= 0 ? '+' : '' }}MYR {{ fmt(stats.netBalance) }}
                    </p>
                    <p class="mt-1 text-xs text-gray-400">All time</p>
                </div>

                <!-- Total income -->
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Total Income</p>
                    <p class="mt-2 text-2xl font-bold text-green-600 dark:text-green-400">MYR {{ fmt(stats.totalIncome) }}</p>
                    <p class="mt-1 text-xs text-gray-400">All time</p>
                </div>

                <!-- Total expenses -->
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Total Expenses</p>
                    <p class="mt-2 text-2xl font-bold text-red-600 dark:text-red-400">MYR {{ fmt(stats.totalExpenses) }}</p>
                    <p class="mt-1 text-xs text-gray-400">All time</p>
                </div>

                <!-- This month net -->
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">This Month</p>
                    <p
                        class="mt-2 text-2xl font-bold"
                        :class="stats.monthNet >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'"
                    >
                        {{ stats.monthNet >= 0 ? '+' : '' }}MYR {{ fmt(stats.monthNet) }}
                    </p>
                    <p class="mt-1 text-xs text-gray-400">{{ currentMonth }}</p>
                </div>
            </div>

            <!-- ── This month breakdown row ───────────────────────────── -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Income this month</p>
                    <p class="mt-1 text-xl font-semibold text-green-600 dark:text-green-400">MYR {{ fmt(stats.monthIncome) }}</p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Expenses this month</p>
                    <p class="mt-1 text-xl font-semibold text-red-600 dark:text-red-400">MYR {{ fmt(stats.monthExpenses) }}</p>
                </div>
            </div>

            <!-- ── Charts row ──────────────────────────────────────────── -->
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">

                <!-- Bar chart – last 6 months -->
                <div class="lg:col-span-2 rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Income vs Expenses — Last 6 Months</h3>

                    <div v-if="monthlyChart.length > 0" class="mt-6">
                        <!-- Bar chart -->
                        <div class="flex items-end justify-between gap-3" :style="`height: ${BAR_H + 4}px`">
                            <div
                                v-for="point in monthlyChart"
                                :key="point.month"
                                class="flex flex-1 flex-col items-center gap-1"
                            >
                                <!-- Bars -->
                                <div class="flex w-full items-end justify-center gap-1" :style="`height: ${BAR_H}px`">
                                    <!-- Income bar -->
                                    <div
                                        class="flex-1 rounded-t-sm bg-green-400 dark:bg-green-500 transition-all duration-500"
                                        :style="`height: ${barHeight(point.income)}px`"
                                        :title="`Income: MYR ${fmt(point.income)}`"
                                    />
                                    <!-- Expense bar -->
                                    <div
                                        class="flex-1 rounded-t-sm bg-red-400 dark:bg-red-500 transition-all duration-500"
                                        :style="`height: ${barHeight(point.expenses)}px`"
                                        :title="`Expenses: MYR ${fmt(point.expenses)}`"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- X axis labels -->
                        <div class="mt-2 flex justify-between gap-3">
                            <div
                                v-for="point in monthlyChart"
                                :key="point.month + '-label'"
                                class="flex-1 text-center text-[10px] text-gray-400"
                            >
                                {{ point.label.slice(0, 3) }}
                            </div>
                        </div>

                        <!-- Legend -->
                        <div class="mt-4 flex items-center justify-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                            <span class="flex items-center gap-1.5">
                                <span class="inline-block h-2.5 w-2.5 rounded-sm bg-green-400" />
                                Income
                            </span>
                            <span class="flex items-center gap-1.5">
                                <span class="inline-block h-2.5 w-2.5 rounded-sm bg-red-400" />
                                Expenses
                            </span>
                        </div>
                    </div>

                    <!-- Empty state -->
                    <div v-else class="mt-8 text-center text-sm text-gray-400">
                        No data yet — add some expenses to see your chart.
                    </div>
                </div>

                <!-- Category donut — this month -->
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Spending by Category</h3>
                    <p class="text-xs text-gray-400 mt-0.5">{{ currentMonth }}</p>

                    <template v-if="byCategory.length > 0">
                        <!-- Donut -->
                        <div class="my-6 flex justify-center">
                            <svg viewBox="0 0 36 36" class="h-32 w-32 -rotate-90">
                                <!-- Background ring -->
                                <circle
                                    cx="18" cy="18" r="15.9155"
                                    fill="none"
                                    stroke-width="3.5"
                                    class="stroke-gray-100 dark:stroke-gray-700"
                                />
                                <!-- Segments -->
                                <circle
                                    v-for="(seg, i) in donutSegments"
                                    :key="i"
                                    cx="18" cy="18" r="15.9155"
                                    fill="none"
                                    stroke-width="3.5"
                                    :stroke="seg.color"
                                    :stroke-dasharray="seg.dasharray"
                                    :stroke-dashoffset="seg.dashoffset"
                                />
                            </svg>
                        </div>

                        <!-- Legend list -->
                        <ul class="space-y-2">
                            <li
                                v-for="cat in byCategory"
                                :key="cat.name"
                                class="flex items-center justify-between text-sm"
                            >
                                <div class="flex items-center gap-2 min-w-0">
                                    <span
                                        class="h-2.5 w-2.5 flex-shrink-0 rounded-full"
                                        :style="{ backgroundColor: cat.color }"
                                    />
                                    <span class="truncate text-gray-700 dark:text-gray-300">{{ cat.name }}</span>
                                </div>
                                <span class="ml-2 flex-shrink-0 text-xs font-medium text-gray-500 dark:text-gray-400">
                                    {{ categoryPct(cat.total) }}%
                                </span>
                            </li>
                        </ul>
                    </template>

                    <!-- Empty state -->
                    <div v-else class="mt-8 text-center text-sm text-gray-400">
                        No expenses recorded this month.
                    </div>
                </div>
            </div>

            <!-- ── Recent transactions ─────────────────────────────────── -->
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Recent Transactions</h3>
                    <Link
                        href="/expenses"
                        class="text-xs font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400"
                    >
                        View all →
                    </Link>
                </div>

                <ul v-if="recent.length > 0" class="divide-y divide-gray-200 dark:divide-gray-700">
                    <li
                        v-for="entry in recent"
                        :key="entry.id"
                        class="flex items-center justify-between px-6 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors"
                    >
                        <div class="flex items-center gap-3 min-w-0">
                            <span
                                class="h-8 w-8 flex-shrink-0 rounded-full"
                                :style="{ backgroundColor: entry.category?.color ?? '#94a3b8' }"
                            />
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-gray-900 dark:text-white">{{ entry.title }}</p>
                                <p class="text-xs text-gray-400">
                                    {{ entry.category?.name ?? 'Uncategorised' }} · {{ fmtDate(entry.expense_date) }}
                                </p>
                            </div>
                        </div>
                        <span
                            class="ml-4 flex-shrink-0 text-sm font-semibold"
                            :class="entry.type === 'income'
                                ? 'text-green-600 dark:text-green-400'
                                : 'text-red-600 dark:text-red-400'"
                        >
                            {{ entry.type === 'income' ? '+' : '-' }} MYR {{ fmt(entry.amount) }}
                        </span>
                    </li>
                </ul>

                <div v-else class="px-6 py-12 text-center text-sm text-gray-400">
                    No transactions yet.
                    <Link href="/expenses/create" class="ml-1 font-medium text-indigo-600 dark:text-indigo-400">Add one →</Link>
                </div>
            </div>

        </div>
</template>