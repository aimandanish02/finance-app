<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Overview {
    net_balance: number; month_spent: number; month_income: number;
    forecast: number; daily_avg: number; days_remaining: number;
    total_claimable: number; est_tax_saved: number; current_month: string;
}
interface BudgetAlert {
    id: number; label: string; is_overall: boolean; color: string;
    amount: number; spent: number; pct: number; status: 'warning' | 'exceeded';
}
interface GoalItem {
    id: number; name: string; type: string;
    target_amount: number | null; target_pct: number | null;
    current_savings: number; color: string; pct: number;
    months_to_goal: number | null;
}
interface TaxLimitAlert {
    deduction_type: string; deduction_label: string;
    total_spent: number; annual_limit: number; pct: number;
    status: 'warning' | 'exceeded';
}
interface SpendingPoint { label: string; month: string; spent: number; income: number; }
interface TopCategory { name: string; color: string; total: number; pct: number; }
interface RecentEntry {
    id: number; title: string; amount: number; type: string;
    expense_date: string; category: { name: string; color: string } | null;
}
interface ReliefItem { label: string; amount: number; }
interface NetWorthPoint { label: string; month: string; cumulative: number; }
interface BestMonth { label: string; net: number; }
interface Receipts { total: number; indexed: number; missing: number; }

const props = defineProps<{
    overview: Overview;
    budgetAlerts: BudgetAlert[];
    goals: GoalItem[];
    taxLimitAlerts: TaxLimitAlert[];
    spendingTrend: SpendingPoint[];
    topCategory: TopCategory | null;
    recurringCount: number;
    momPct: number | null;
    recent: RecentEntry[];
    reliefs: { items: ReliefItem[]; total: number };
    netWorthHistory: NetWorthPoint[];
    netWorthCurrent: number;
    avgMonthlySavings: number;
    bestMonth: BestMonth | null;
    receipts: Receipts;
    year: number;
}>();

const fmt = (v: number) =>
    v.toLocaleString('en-MY', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const fmtDate = (d: string) =>
    new Date(d + 'T00:00:00').toLocaleDateString('en-MY', { month: 'short', day: 'numeric' });

// ── Spending bar chart ─────────────────────────────────────────────────────
const BAR_H = 48;
const barMax = computed(() =>
    Math.max(...props.spendingTrend.map(p => p.spent), 1)
);
const barH = (v: number) => Math.max(3, Math.round((v / barMax.value) * BAR_H));

// ── Net worth line chart ───────────────────────────────────────────────────
const NW_H = 48;
const nwMax = computed(() =>
    Math.max(...props.netWorthHistory.map(p => Math.abs(p.cumulative)), 1)
);
const nwY = (v: number) => {
    const mid = NW_H / 2;
    return mid - (v / nwMax.value) * (mid - 3);
};
const nwPoints = computed(() =>
    props.netWorthHistory.map((p, i) => {
        const x = 10 + (i / (props.netWorthHistory.length - 1)) * 280;
        return `${x},${nwY(p.cumulative)}`;
    }).join(' ')
);

const progressColor = (pct: number, status: string) => {
    if (status === 'exceeded') return '#e24b4a';
    if (status === 'warning') return '#ba7517';
    return '#6366f1';
};

const statusBadge = (status: string) => ({
    exceeded: 'bg-red-100 text-red-800 dark:bg-red-800/30 dark:text-red-400',
    warning:  'bg-amber-100 text-amber-800 dark:bg-amber-800/30 dark:text-amber-400',
    ok:       'bg-green-100 text-green-800 dark:bg-green-800/30 dark:text-green-400',
}[status] ?? '');
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex flex-col gap-5 p-6">

        <!-- ── Row 1: Overview ──────────────────────────────────────────── -->
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Net Balance</p>
                <p class="mt-2 text-2xl font-bold"
                    :class="overview.net_balance >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
                    {{ overview.net_balance >= 0 ? '+' : '' }}MYR {{ fmt(overview.net_balance) }}
                </p>
                <p class="mt-1 text-xs text-gray-400">All time</p>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ overview.current_month }}</p>
                <p class="mt-2 text-2xl font-bold text-red-600 dark:text-red-400">MYR {{ fmt(overview.month_spent) }}</p>
                <p class="mt-1 text-xs text-gray-400">
                    <span class="text-green-600 dark:text-green-400">+MYR {{ fmt(overview.month_income) }}</span> income
                    <span v-if="momPct !== null" class="ml-1"
                        :class="momPct > 0 ? 'text-red-500' : 'text-green-500'">
                        · {{ momPct > 0 ? '▲' : '▼' }}{{ Math.abs(momPct) }}% vs last month
                    </span>
                </p>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Forecast (end of month)</p>
                <p class="mt-2 text-2xl font-bold text-amber-600 dark:text-amber-400">MYR {{ fmt(overview.forecast) }}</p>
                <p class="mt-1 text-xs text-gray-400">
                    MYR {{ fmt(overview.daily_avg) }}/day · {{ overview.days_remaining }}d left
                </p>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Tax Claimable (YA{{ year }})</p>
                <p class="mt-2 text-2xl font-bold text-indigo-600 dark:text-indigo-400">MYR {{ fmt(overview.total_claimable) }}</p>
                <p class="mt-1 text-xs text-gray-400">
                    Est. tax saved: <span class="text-indigo-500 font-medium">MYR {{ fmt(overview.est_tax_saved) }}</span>
                </p>
            </div>

        </div>

        <!-- ── Row 2: Alerts + Goals + Tax Limits ──────────────────────── -->
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">

            <!-- Budget alerts -->
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="flex items-center justify-between border-b border-gray-200 px-5 py-3 dark:border-gray-700">
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">Budget alerts</p>
                    <Link href="/budgets" class="text-xs text-indigo-600 hover:underline dark:text-indigo-400">Manage →</Link>
                </div>
                <div v-if="budgetAlerts.length > 0" class="divide-y divide-gray-200 dark:divide-gray-700">
                    <div v-for="alert in budgetAlerts" :key="alert.id" class="px-5 py-3">
                        <div class="flex items-center justify-between mb-1.5">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="h-2.5 w-2.5 rounded-full flex-shrink-0" :style="{ backgroundColor: alert.color }"/>
                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ alert.label }}</p>
                            </div>
                            <span :class="['ml-2 flex-shrink-0 text-xs rounded-full px-2 py-0.5 font-medium', statusBadge(alert.status)]">
                                {{ alert.status === 'exceeded' ? 'Over' : 'Alert' }}
                            </span>
                        </div>
                        <div class="h-1.5 w-full rounded-full bg-gray-200 dark:bg-gray-700">
                            <div class="h-1.5 rounded-full" :style="{ width: `${alert.pct}%`, backgroundColor: progressColor(alert.pct, alert.status) }"/>
                        </div>
                        <p class="mt-1 text-xs text-gray-400">MYR {{ fmt(alert.spent) }} of MYR {{ fmt(alert.amount) }}</p>
                    </div>
                </div>
                <div v-else class="px-5 py-8 text-center text-sm text-gray-400">
                    All budgets on track
                    <div class="mt-2">
                        <Link href="/budgets/create" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">Set a budget →</Link>
                    </div>
                </div>
            </div>

            <!-- Savings goals -->
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="flex items-center justify-between border-b border-gray-200 px-5 py-3 dark:border-gray-700">
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">Savings goals</p>
                    <Link href="/goals" class="text-xs text-indigo-600 hover:underline dark:text-indigo-400">All goals →</Link>
                </div>
                <div v-if="goals.length > 0" class="divide-y divide-gray-200 dark:divide-gray-700">
                    <div v-for="goal in goals" :key="goal.id" class="px-5 py-3">
                        <div class="flex items-center justify-between mb-1.5">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="h-2.5 w-2.5 rounded-full flex-shrink-0" :style="{ backgroundColor: goal.color }"/>
                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ goal.name }}</p>
                            </div>
                            <span class="ml-2 flex-shrink-0 text-sm font-bold text-indigo-600 dark:text-indigo-400">{{ goal.pct }}%</span>
                        </div>
                        <div class="h-1.5 w-full rounded-full bg-gray-200 dark:bg-gray-700">
                            <div class="h-1.5 rounded-full transition-all duration-500" :style="{ width: `${goal.pct}%`, backgroundColor: goal.color }"/>
                        </div>
                        <p class="mt-1 text-xs text-gray-400">
                            <span v-if="goal.type === 'target_amount'">MYR {{ fmt(goal.current_savings) }} of MYR {{ fmt(goal.target_amount!) }}</span>
                            <span v-else>{{ goal.target_pct }}% of income</span>
                            <span v-if="goal.months_to_goal" class="ml-1">· ~{{ goal.months_to_goal }}mo</span>
                        </p>
                    </div>
                </div>
                <div v-else class="px-5 py-8 text-center text-sm text-gray-400">
                    No goals yet
                    <div class="mt-2">
                        <Link href="/goals/create" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">Create a goal →</Link>
                    </div>
                </div>
            </div>

            <!-- Tax deduction limits -->
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="flex items-center justify-between border-b border-gray-200 px-5 py-3 dark:border-gray-700">
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">Tax deduction limits</p>
                    <Link href="/tax-summary" class="text-xs text-indigo-600 hover:underline dark:text-indigo-400">Full summary →</Link>
                </div>
                <div v-if="taxLimitAlerts.length > 0" class="divide-y divide-gray-200 dark:divide-gray-700">
                    <div v-for="alert in taxLimitAlerts" :key="alert.deduction_type" class="px-5 py-3">
                        <div class="flex items-center justify-between mb-1.5">
                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ alert.deduction_label }}</p>
                            <span :class="['ml-2 flex-shrink-0 text-xs rounded-full px-2 py-0.5 font-medium', statusBadge(alert.status)]">
                                {{ alert.pct }}%
                            </span>
                        </div>
                        <div class="h-1.5 w-full rounded-full bg-gray-200 dark:bg-gray-700">
                            <div class="h-1.5 rounded-full" :style="{ width: `${alert.pct}%`, backgroundColor: progressColor(alert.pct, alert.status) }"/>
                        </div>
                        <p class="mt-1 text-xs text-gray-400">MYR {{ fmt(alert.total_spent) }} of MYR {{ fmt(alert.annual_limit) }}</p>
                    </div>
                </div>
                <div v-else class="px-5 py-8 text-center text-sm text-gray-400">
                    All deduction limits OK
                    <div class="mt-1 text-xs text-gray-400">No limits approaching</div>
                </div>
            </div>

        </div>

        <!-- ── Row 3: Spending Trend + Recent ──────────────────────────── -->
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">

            <!-- Spending trend -->
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="flex items-center justify-between mb-4">
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">Spending trend</p>
                    <Link href="/spending" class="text-xs text-indigo-600 hover:underline dark:text-indigo-400">Full analysis →</Link>
                </div>
                <div class="flex items-end justify-between gap-1" :style="`height: ${BAR_H + 4}px`">
                    <div v-for="point in spendingTrend" :key="point.month" class="flex flex-1 flex-col items-center gap-0.5">
                        <div class="w-full rounded-t-sm bg-indigo-400 dark:bg-indigo-500 transition-all duration-500"
                            :style="`height: ${barH(point.spent)}px`"
                            :title="`MYR ${fmt(point.spent)}`"/>
                    </div>
                </div>
                <div class="mt-1.5 flex justify-between">
                    <span v-for="point in spendingTrend" :key="point.month + '-l'"
                        class="flex-1 text-center text-[10px] text-gray-400">{{ point.label }}</span>
                </div>
                <div class="mt-4 grid grid-cols-3 gap-3 border-t border-gray-200 dark:border-gray-700 pt-3">
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Top category</p>
                        <p v-if="topCategory" class="text-sm font-medium text-gray-900 dark:text-white mt-0.5">
                            {{ topCategory.name }}
                            <span class="text-xs text-gray-400 ml-1">{{ topCategory.pct }}%</span>
                        </p>
                        <p v-else class="text-sm text-gray-400 mt-0.5">—</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Recurring</p>
                        <p class="text-sm font-medium text-gray-900 dark:text-white mt-0.5">{{ recurringCount }} items</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">vs last month</p>
                        <p v-if="momPct !== null" class="text-sm font-medium mt-0.5"
                            :class="momPct > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400'">
                            {{ momPct > 0 ? '▲' : '▼' }}{{ Math.abs(momPct) }}%
                        </p>
                        <p v-else class="text-sm text-gray-400 mt-0.5">—</p>
                    </div>
                </div>
            </div>

            <!-- Recent transactions -->
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="flex items-center justify-between border-b border-gray-200 px-5 py-3 dark:border-gray-700">
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">Recent transactions</p>
                    <Link href="/expenses" class="text-xs text-indigo-600 hover:underline dark:text-indigo-400">View all →</Link>
                </div>
                <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                    <li v-for="entry in recent" :key="entry.id"
                        class="flex items-center justify-between px-5 py-3">
                        <div class="flex items-center gap-3 min-w-0">
                            <span class="h-7 w-7 flex-shrink-0 rounded-full"
                                :style="{ backgroundColor: entry.category?.color ?? '#94a3b8' }"/>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-gray-900 dark:text-white">{{ entry.title }}</p>
                                <p class="text-xs text-gray-400">{{ entry.category?.name ?? 'Uncategorised' }} · {{ fmtDate(entry.expense_date) }}</p>
                            </div>
                        </div>
                        <span class="ml-3 flex-shrink-0 text-sm font-semibold"
                            :class="entry.type === 'income' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
                            {{ entry.type === 'income' ? '+' : '-' }}MYR {{ fmt(entry.amount) }}
                        </span>
                    </li>
                </ul>
                <div v-if="recent.length === 0" class="px-5 py-8 text-center text-sm text-gray-400">
                    No transactions yet.
                    <Link href="/expenses/create" class="ml-1 text-indigo-600 dark:text-indigo-400 hover:underline">Add one →</Link>
                </div>
            </div>

        </div>

        <!-- ── Row 4: Reliefs + Net Worth + Receipts ───────────────────── -->
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">

            <!-- Personal tax reliefs -->
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="flex items-center justify-between mb-1">
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Personal reliefs</p>
                    <Link href="/settings/tax-profile" class="text-xs text-indigo-600 hover:underline dark:text-indigo-400">Edit →</Link>
                </div>
                <p class="text-2xl font-bold text-indigo-600 dark:text-indigo-400 mb-1">MYR {{ fmt(reliefs.total) }}</p>
                <p class="text-xs text-gray-400 mb-3">Auto-calculated from your tax profile</p>
                <div class="space-y-1 border-t border-gray-200 dark:border-gray-700 pt-3">
                    <div v-for="item in reliefs.items" :key="item.label"
                        class="flex justify-between text-xs">
                        <span class="text-gray-500 dark:text-gray-400">{{ item.label }}</span>
                        <span class="font-medium text-gray-900 dark:text-white tabular-nums">RM {{ fmt(item.amount) }}</span>
                    </div>
                </div>
            </div>

            <!-- Net worth trend -->
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">Net Worth Trend</p>
                <p class="text-2xl font-bold mb-1"
                    :class="netWorthCurrent >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
                    {{ netWorthCurrent >= 0 ? '+' : '' }}MYR {{ fmt(netWorthCurrent) }}
                </p>
                <p class="text-xs text-gray-400 mb-3">Cumulative over 12 months</p>
                <svg width="100%" viewBox="0 0 300 52" preserveAspectRatio="none">
                    <line x1="10" y1="26" x2="290" y2="26" stroke="var(--color-border-tertiary)" stroke-width="0.5" stroke-dasharray="3 3"/>
                    <polyline :points="nwPoints" fill="none" stroke="#6366f1" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <div class="mt-3 space-y-1 border-t border-gray-200 dark:border-gray-700 pt-3">
                    <div class="flex justify-between text-xs">
                        <span class="text-gray-500 dark:text-gray-400">Avg monthly savings</span>
                        <span class="font-medium text-gray-900 dark:text-white">MYR {{ fmt(avgMonthlySavings) }}</span>
                    </div>
                    <div v-if="bestMonth" class="flex justify-between text-xs">
                        <span class="text-gray-500 dark:text-gray-400">Best month</span>
                        <span class="font-medium text-gray-900 dark:text-white">{{ bestMonth.label }} · MYR {{ fmt(bestMonth.net) }}</span>
                    </div>
                </div>
            </div>

            <!-- Receipts & audit -->
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">Receipts & Audit</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white mb-1">{{ receipts.total }}</p>
                <p class="text-xs text-gray-400 mb-3">Receipts uploaded for YA{{ year }}</p>
                <div class="space-y-2 border-t border-gray-200 dark:border-gray-700 pt-3">
                    <div class="flex justify-between text-xs">
                        <span class="text-gray-500 dark:text-gray-400">OCR indexed</span>
                        <span class="font-medium text-green-600 dark:text-green-400">{{ receipts.indexed }}</span>
                    </div>
                    <div class="flex justify-between text-xs">
                        <span class="text-gray-500 dark:text-gray-400">Missing receipts</span>
                        <span class="font-medium"
                            :class="receipts.missing > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-green-600 dark:text-green-400'">
                            {{ receipts.missing > 0 ? `${receipts.missing} deductible expenses` : 'All covered ✓' }}
                        </span>
                    </div>
                    <div v-if="receipts.missing > 0" class="mt-2">
                        <Link href="/expenses" class="text-xs text-indigo-600 hover:underline dark:text-indigo-400">
                            Upload missing receipts →
                        </Link>
                    </div>
                </div>
            </div>

        </div>

    </div>
</template>