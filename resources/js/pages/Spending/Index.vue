<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';

interface MonthlyPoint {
    label: string; month: string; spent: number; income: number; net: number;
}
interface CategoryBreakdown {
    name: string; color: string; code: string; total: number; pct: number;
}
interface RecurringItem {
    title: string; amount: number; category: string; color: string;
}
interface NetWorthPoint {
    label: string; month: string; net: number; cumulative: number;
}
interface Props {
    monthly: MonthlyPoint[];
    categoryBreakdown: CategoryBreakdown[];
    monthTotal: number;
    momChange: number | null;
    momAbsolute: number;
    topCategory: CategoryBreakdown | null;
    recurring: RecurringItem[];
    currentMonth: string;
    lastMonth: string;
    selectedMonths: number;
    forecast: number;
    forecastDailyAvg: number;
    daysRemaining: number;
    netWorth: NetWorthPoint[];
}

const props = defineProps<Props>();

const fmt = (v: number) =>
    v.toLocaleString('en-MY', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const BAR_H = 160;
const chartMax = computed(() => {
    const max = Math.max(...props.monthly.flatMap(m => [m.spent, m.income]), 1);
    const magnitude = Math.pow(10, Math.floor(Math.log10(max)));
    return Math.ceil(max / magnitude) * magnitude;
});
const barHeight = (value: number) => Math.max(4, Math.round((value / chartMax.value) * BAR_H));

// Net worth line chart
const NW_H = 100;
const nwMax = computed(() => {
    const vals = props.netWorth.map(p => Math.abs(p.cumulative));
    return Math.max(...vals, 1);
});
const nwY = (v: number) => {
    const mid = NW_H / 2;
    return mid - (v / nwMax.value) * (mid - 4);
};
const nwPoints = computed(() =>
    props.netWorth.map((p, i) => {
        const x = 20 + (i / (props.netWorth.length - 1)) * 560;
        const y = nwY(p.cumulative);
        return `${x},${y}`;
    }).join(' ')
);

const switchMonths = (m: number) => {
    router.get('/spending', { months: m }, { preserveState: true });
};
</script>

<template>
    <Head title="Spending" />

    <div class="flex h-full flex-1 flex-col gap-6 p-6">

        <!-- Header -->
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-xl font-semibold text-gray-900 dark:text-white">Spending Intelligence</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Understand and control your spending habits</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-sm text-gray-500 dark:text-gray-400">Show:</span>
                <div class="flex rounded-lg border border-gray-300 dark:border-gray-600 overflow-hidden">
                    <button
                        v-for="m in [3, 6, 12]" :key="m" type="button"
                        class="px-3 py-1.5 text-sm font-medium transition-colors"
                        :class="m === selectedMonths
                            ? 'bg-indigo-600 text-white'
                            : 'bg-white text-gray-700 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700'"
                        @click="switchMonths(m)"
                    >{{ m }}M</button>
                </div>
            </div>
        </div>

        <!-- Summary cards -->
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ currentMonth }}</p>
                <p class="mt-2 text-2xl font-bold text-red-600 dark:text-red-400">MYR {{ fmt(monthTotal) }}</p>
                <div v-if="momChange !== null" class="mt-1 flex items-center gap-1 text-xs">
                    <span :class="momAbsolute > 0 ? 'text-red-500' : 'text-green-500'">
                        {{ momAbsolute > 0 ? '▲' : '▼' }} MYR {{ fmt(Math.abs(momAbsolute)) }}
                    </span>
                    <span class="text-gray-400">vs {{ lastMonth }}</span>
                </div>
            </div>

            <!-- Forecast -->
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Forecast</p>
                <p class="mt-2 text-2xl font-bold text-amber-600 dark:text-amber-400">MYR {{ fmt(forecast) }}</p>
                <p class="mt-1 text-xs text-gray-400">
                    MYR {{ fmt(forecastDailyAvg) }}/day · {{ daysRemaining }}d left
                </p>
            </div>

            <!-- Top category -->
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Top Category</p>
                <div v-if="topCategory" class="mt-2 flex items-center gap-2">
                    <span class="h-3 w-3 rounded-full flex-shrink-0" :style="{ backgroundColor: topCategory.color }" />
                    <p class="text-lg font-bold text-gray-900 dark:text-white truncate">{{ topCategory.name }}</p>
                </div>
                <p v-if="topCategory" class="mt-1 text-xs text-gray-400">{{ topCategory.pct }}% of spend</p>
                <p v-else class="mt-2 text-sm text-gray-400">No expenses this month</p>
            </div>

            <!-- Recurring -->
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Recurring</p>
                <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">{{ recurring.length }}</p>
                <p class="mt-1 text-xs text-gray-400">Expenses found last 2 months</p>
            </div>
        </div>

        <!-- Chart + Category Breakdown -->
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
            <div class="lg:col-span-2 rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-6">Income vs Expenses — Last {{ selectedMonths }} Months</h3>
                <div class="flex items-end justify-between gap-2" :style="`height: ${BAR_H + 4}px`">
                    <div v-for="point in monthly" :key="point.month" class="flex flex-1 flex-col items-center">
                        <div class="flex w-full items-end justify-center gap-1" :style="`height: ${BAR_H}px`">
                            <div class="flex-1 rounded-t-sm bg-green-400 dark:bg-green-500 transition-all duration-500"
                                :style="`height: ${barHeight(point.income)}px`" :title="`Income: MYR ${fmt(point.income)}`"/>
                            <div class="flex-1 rounded-t-sm bg-red-400 dark:bg-red-500 transition-all duration-500"
                                :style="`height: ${barHeight(point.spent)}px`" :title="`Spent: MYR ${fmt(point.spent)}`"/>
                        </div>
                    </div>
                </div>
                <div class="mt-2 flex justify-between gap-2">
                    <div v-for="point in monthly" :key="point.month + '-label'"
                        class="flex-1 text-center text-[10px] text-gray-400">
                        {{ point.label.slice(0, 3) }}
                    </div>
                </div>
                <div class="mt-4 flex items-center justify-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                    <span class="flex items-center gap-1.5"><span class="inline-block h-2.5 w-2.5 rounded-sm bg-green-400"/>Income</span>
                    <span class="flex items-center gap-1.5"><span class="inline-block h-2.5 w-2.5 rounded-sm bg-red-400"/>Expenses</span>
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">This Month by Category</h3>
                <p class="mt-0.5 text-xs text-gray-400">{{ currentMonth }}</p>
                <div v-if="categoryBreakdown.length > 0" class="mt-4 space-y-3">
                    <div v-for="cat in categoryBreakdown" :key="cat.code">
                        <div class="flex items-center justify-between text-sm mb-1">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="h-2.5 w-2.5 rounded-full flex-shrink-0" :style="{ backgroundColor: cat.color }"/>
                                <span class="truncate text-gray-700 dark:text-gray-300">{{ cat.name }}</span>
                            </div>
                            <span class="ml-2 flex-shrink-0 text-xs text-gray-500 dark:text-gray-400">{{ cat.pct }}%</span>
                        </div>
                        <div class="h-1.5 w-full rounded-full bg-gray-200 dark:bg-gray-700">
                            <div class="h-1.5 rounded-full transition-all duration-500"
                                :style="{ width: `${cat.pct}%`, backgroundColor: cat.color }"/>
                        </div>
                        <p class="mt-0.5 text-xs text-gray-400 text-right">MYR {{ fmt(cat.total) }}</p>
                    </div>
                </div>
                <div v-else class="mt-8 text-center text-sm text-gray-400">No expenses this month</div>
            </div>
        </div>

        <!-- Net worth snapshot -->
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Net Worth Snapshot</h3>
            <p class="mt-0.5 text-xs text-gray-400">Cumulative income minus expenses over the last 12 months</p>

            <div class="mt-4">
                <div class="flex items-baseline gap-2 mb-4">
                    <span
                        class="text-2xl font-bold"
                        :class="netWorth[netWorth.length - 1]?.cumulative >= 0
                            ? 'text-green-600 dark:text-green-400'
                            : 'text-red-600 dark:text-red-400'"
                    >
                        MYR {{ fmt(netWorth[netWorth.length - 1]?.cumulative ?? 0) }}
                    </span>
                    <span class="text-sm text-gray-400">cumulative net</span>
                </div>

                <!-- SVG line chart -->
                <svg width="100%" viewBox="0 0 600 120" preserveAspectRatio="none">
                    <!-- Zero line -->
                    <line x1="20" y1="60" x2="580" y2="60" stroke="var(--color-border-tertiary)" stroke-width="0.5" stroke-dasharray="4 4"/>
                    <!-- Net worth line -->
                    <polyline
                        :points="nwPoints"
                        fill="none"
                        stroke="#6366f1"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />
                    <!-- Data points -->
                    <circle
                        v-for="(point, i) in netWorth"
                        :key="point.month"
                        :cx="20 + (i / (netWorth.length - 1)) * 560"
                        :cy="nwY(point.cumulative)"
                        r="3"
                        :fill="point.cumulative >= 0 ? '#10b981' : '#ef4444'"
                    />
                </svg>

                <!-- X axis labels -->
                <div class="flex justify-between mt-1">
                    <span
                        v-for="(point, i) in netWorth.filter((_, i) => i % 3 === 0)"
                        :key="point.month"
                        class="text-[10px] text-gray-400"
                    >{{ point.label.slice(0, 3) }}</span>
                </div>
            </div>
        </div>

        <!-- Recurring expenses -->
        <div v-if="recurring.length > 0" class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Recurring Expenses Detected</h3>
                <p class="mt-0.5 text-xs text-gray-400">Same expense found in both {{ lastMonth }} and {{ currentMonth }}</p>
            </div>
            <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                <li v-for="item in recurring" :key="item.title"
                    class="flex items-center justify-between px-6 py-3">
                    <div class="flex items-center gap-3">
                        <span class="h-2.5 w-2.5 rounded-full flex-shrink-0" :style="{ backgroundColor: item.color }"/>
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ item.title }}</p>
                            <p class="text-xs text-gray-400">{{ item.category }}</p>
                        </div>
                    </div>
                    <span class="text-sm font-semibold text-red-600 dark:text-red-400">MYR {{ fmt(item.amount) }}/mo</span>
                </li>
            </ul>
        </div>

        <!-- CTAs -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="flex items-center justify-between rounded-xl border border-indigo-200 bg-indigo-50 px-6 py-4 dark:border-indigo-800 dark:bg-indigo-900/20">
                <div>
                    <p class="text-sm font-medium text-indigo-900 dark:text-indigo-300">Set spending budgets</p>
                    <p class="mt-0.5 text-xs text-indigo-600 dark:text-indigo-400">Get alerts when approaching limits</p>
                </div>
                <Link href="/budgets" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 transition-colors">
                    Budgets
                </Link>
            </div>
            <div class="flex items-center justify-between rounded-xl border border-green-200 bg-green-50 px-6 py-4 dark:border-green-800 dark:bg-green-900/20">
                <div>
                    <p class="text-sm font-medium text-green-900 dark:text-green-300">Set savings goals</p>
                    <p class="mt-0.5 text-xs text-green-600 dark:text-green-400">Track progress toward your targets</p>
                </div>
                <Link href="/goals" class="rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700 transition-colors">
                    Goals
                </Link>
            </div>
        </div>

    </div>
</template>