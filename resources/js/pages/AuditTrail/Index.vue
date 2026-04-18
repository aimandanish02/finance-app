<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

interface Receipt { id: number; filename: string; type: string; is_indexed: boolean; }
interface ExpenseEntry {
    id: number; title: string; amount: number; expense_date: string;
    description: string | null; deduction_type: string; deduction_label: string;
    category: { name: string; color: string };
    receipts: Receipt[]; has_receipt: boolean; receipt_count: number;
}
interface TypeSummary {
    deduction_type: string; deduction_label: string;
    count: number; total: number; missing: number;
}

const props = defineProps<{
    year: number;
    availableYears: number[];
    expenses: ExpenseEntry[];
    byType: TypeSummary[];
    stats: {
        total: number; with_receipts: number;
        without: number; total_claimable: number; coverage_pct: number;
    };
}>();

const fmt = (v: number) =>
    v.toLocaleString('en-MY', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const fmtDate = (d: string) =>
    new Date(d + 'T00:00:00').toLocaleDateString('en-MY', { day: 'numeric', month: 'short', year: 'numeric' });

const switchYear = (y: number) =>
    router.get('/audit-trail', { year: y }, { preserveState: true });

// Filter
const filterType = ref<string>('all');
const filterReceipt = ref<string>('all');

const filtered = computed(() =>
    props.expenses.filter(e => {
        if (filterType.value !== 'all' && e.deduction_type !== filterType.value) return false;
        if (filterReceipt.value === 'missing' && e.has_receipt) return false;
        if (filterReceipt.value === 'has' && !e.has_receipt) return false;
        return true;
    })
);
</script>

<template>
    <Head :title="`Audit Trail ${year}`" />

    <div class="flex h-full flex-1 flex-col gap-6 p-6">

        <!-- Header -->
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-xl font-semibold text-gray-900 dark:text-white">Audit Trail</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Every deductible expense with receipt status — YA{{ year }}
                </p>
            </div>
            <div class="flex items-center gap-2">
                <div class="flex rounded-lg border border-gray-300 dark:border-gray-600 overflow-hidden">
                    <button
                        v-for="y in availableYears" :key="y" type="button"
                        class="px-4 py-1.5 text-sm font-medium transition-colors"
                        :class="y === year
                            ? 'bg-indigo-600 text-white'
                            : 'bg-white text-gray-700 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700'"
                        @click="switchYear(y)"
                    >{{ y }}</button>
                </div>
                <a :href="`/tax-summary/export/pdf?year=${year}`" target="_blank"
                    class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    PDF
                </a>
            </div>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Total Entries</p>
                <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">{{ stats.total }}</p>
                <p class="mt-1 text-xs text-gray-400">Deductible expenses</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Receipt Coverage</p>
                <p class="mt-2 text-2xl font-bold"
                    :class="stats.coverage_pct >= 80 ? 'text-green-600 dark:text-green-400' : 'text-amber-600 dark:text-amber-400'">
                    {{ stats.coverage_pct }}%
                </p>
                <div class="mt-2 h-1.5 w-full rounded-full bg-gray-200 dark:bg-gray-700">
                    <div class="h-1.5 rounded-full transition-all duration-500"
                        :class="stats.coverage_pct >= 80 ? 'bg-green-500' : 'bg-amber-500'"
                        :style="{ width: `${stats.coverage_pct}%` }"/>
                </div>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Missing Receipts</p>
                <p class="mt-2 text-2xl font-bold"
                    :class="stats.without > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-green-600 dark:text-green-400'">
                    {{ stats.without }}
                </p>
                <p class="mt-1 text-xs text-gray-400">{{ stats.without > 0 ? 'Upload to strengthen your claim' : 'All covered ✓' }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Total Amount</p>
                <p class="mt-2 text-2xl font-bold text-indigo-600 dark:text-indigo-400">MYR {{ fmt(stats.total_claimable) }}</p>
                <p class="mt-1 text-xs text-gray-400">Before LHDN limits</p>
            </div>
        </div>

        <!-- Category summary -->
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-white">By deduction type</h2>
            </div>
            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                <div v-for="row in byType" :key="row.deduction_type"
                    class="flex items-center justify-between px-6 py-3">
                    <div class="flex items-center gap-4">
                        <p class="text-sm font-medium text-gray-900 dark:text-white w-48 truncate">{{ row.deduction_label }}</p>
                        <span class="text-xs text-gray-400">{{ row.count }} entries</span>
                        <span v-if="row.missing > 0"
                            class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-800/30 dark:text-amber-400">
                            {{ row.missing }} missing
                        </span>
                        <span v-else
                            class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800/30 dark:text-green-400">
                            All receipts ✓
                        </span>
                    </div>
                    <p class="text-sm font-semibold text-indigo-600 dark:text-indigo-400">MYR {{ fmt(row.total) }}</p>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="flex flex-wrap gap-3">
            <select v-model="filterType"
                class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                <option value="all">All categories</option>
                <option v-for="row in byType" :key="row.deduction_type" :value="row.deduction_type">
                    {{ row.deduction_label }}
                </option>
            </select>
            <select v-model="filterReceipt"
                class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                <option value="all">All entries</option>
                <option value="missing">Missing receipts only</option>
                <option value="has">Has receipts</option>
            </select>
            <span class="text-sm text-gray-400 self-center">{{ filtered.length }} entries</span>
        </div>

        <!-- Expense list -->
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div v-if="filtered.length > 0" class="divide-y divide-gray-200 dark:divide-gray-700">
                <div v-for="entry in filtered" :key="entry.id"
                    class="flex items-start justify-between px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-700/20 transition-colors">
                    <div class="flex items-start gap-4 min-w-0 flex-1">
                        <!-- Receipt status indicator -->
                        <div class="mt-0.5 flex-shrink-0">
                            <div v-if="entry.has_receipt"
                                class="h-6 w-6 rounded-full bg-green-100 dark:bg-green-800/30 flex items-center justify-center"
                                :title="`${entry.receipt_count} receipt(s)`">
                                <svg class="h-3.5 w-3.5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            <div v-else
                                class="h-6 w-6 rounded-full bg-amber-100 dark:bg-amber-800/30 flex items-center justify-center"
                                title="No receipt uploaded">
                                <svg class="h-3.5 w-3.5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ entry.title }}</p>
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs text-white"
                                    :style="{ backgroundColor: entry.category.color }">
                                    {{ entry.category.name }}
                                </span>
                            </div>
                            <p class="text-xs text-gray-400 mt-0.5">
                                {{ entry.deduction_label }} · {{ fmtDate(entry.expense_date) }}
                            </p>
                            <div v-if="entry.receipts.length > 0" class="mt-1 flex flex-wrap gap-1">
                                <span v-for="r in entry.receipts" :key="r.id"
                                    class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                                    </svg>
                                    {{ r.filename }}
                                    <span v-if="r.is_indexed" class="text-green-500">✓</span>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-4 flex-shrink-0 ml-4">
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">MYR {{ fmt(entry.amount) }}</p>
                        <Link :href="`/expenses/${entry.id}/edit`"
                            class="text-xs text-indigo-600 hover:underline dark:text-indigo-400">
                            {{ entry.has_receipt ? 'View' : 'Upload receipt' }}
                        </Link>
                    </div>
                </div>
            </div>
            <div v-else class="px-6 py-16 text-center text-sm text-gray-400">
                No entries match the current filter.
            </div>
        </div>

    </div>
</template>