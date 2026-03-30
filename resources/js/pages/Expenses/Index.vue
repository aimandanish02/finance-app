<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

interface Category {
    id: number;
    name: string;
    code: string;
    color: string | null;
}

interface Expense {
    id: number;
    title: string;
    amount: string;
    type: 'income' | 'expense';
    expense_date: string;
    description: string | null;
    notes: string | null;
    category: Category | null;
    receipts_count: number;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedExpenses {
    data: Expense[];
    links: PaginationLink[];
    from: number;
    to: number;
    total: number;
    current_page: number;
    last_page: number;
}

interface Props {
    expenses: PaginatedExpenses;
    totalExpenses: number;
    totalIncome: number;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Expenses', href: '/expenses' },
];

const formatCurrency = (value: number | string): string => {
    return parseFloat(String(value)).toLocaleString('en-MY', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
};

const formatDate = (dateString: string): string => {
    return new Date(dateString + 'T00:00:00').toLocaleDateString('en-MY', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const deleteExpense = (id: number) => {
    if (confirm('Are you sure you want to delete this expense?')) {
        router.delete(`/expenses/${id}`);
    }
};
</script>

<template>
    <!-- <AppLayout :breadcrumbs="breadcrumbs"> -->
        <Head title="Expenses" />

        <div class="flex h-full flex-1 flex-col gap-6 p-6">

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Expenses</p>
                    <p class="mt-1 text-2xl font-bold text-red-600 dark:text-red-400">
                        MYR {{ formatCurrency(totalExpenses) }}
                    </p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Income</p>
                    <p class="mt-1 text-2xl font-bold text-green-600 dark:text-green-400">
                        MYR {{ formatCurrency(totalIncome) }}
                    </p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Net Balance</p>
                    <p
                        class="mt-1 text-2xl font-bold"
                        :class="totalIncome - totalExpenses >= 0
                            ? 'text-green-600 dark:text-green-400'
                            : 'text-red-600 dark:text-red-400'"
                    >
                        MYR {{ formatCurrency(totalIncome - totalExpenses) }}
                    </p>
                </div>
            </div>

            <!-- Table Card -->
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">

                <!-- Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                        Your Expenses
                        <span class="ml-2 text-sm font-normal text-gray-500 dark:text-gray-400">
                            ({{ expenses.total }} total)
                        </span>
                    </h3>
                    <Link
                        href="/expenses/create"
                        class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 transition-colors"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        New Expense
                    </Link>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Title</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Category</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Date</th>
                                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Receipts</th>
                                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <template v-if="expenses.data.length > 0">
                                <tr
                                    v-for="expense in expenses.data"
                                    :key="expense.id"
                                    class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors"
                                >
                                    <td class="px-6 py-4">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ expense.title }}</p>
                                        <p v-if="expense.description" class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-xs">
                                            {{ expense.description }}
                                        </p>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            v-if="expense.category"
                                            class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium text-white"
                                            :style="{ backgroundColor: expense.category.color ?? '#94a3b8' }"
                                        >
                                            {{ expense.category.name }}
                                        </span>
                                        <span v-else class="text-xs text-gray-400">—</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ formatDate(expense.expense_date) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <span
                                            class="text-sm font-semibold"
                                            :class="expense.type === 'income'
                                                ? 'text-green-600 dark:text-green-400'
                                                : 'text-red-600 dark:text-red-400'"
                                        >
                                            {{ expense.type === 'income' ? '+' : '-' }} MYR {{ formatCurrency(expense.amount) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium"
                                            :class="expense.type === 'income'
                                                ? 'bg-green-100 text-green-800 dark:bg-green-800/30 dark:text-green-400'
                                                : 'bg-red-100 text-red-800 dark:bg-red-800/30 dark:text-red-400'"
                                        >
                                            {{ expense.type }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ expense.receipts_count }} file{{ expense.receipts_count !== 1 ? 's' : '' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <div class="flex items-center justify-end gap-3">
                                            <Link
                                                :href="`/expenses/${expense.id}`"
                                                class="text-sm font-medium text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                                            >
                                                View
                                            </Link>
                                            <Link
                                                :href="`/expenses/${expense.id}/edit`"
                                                class="text-sm font-medium text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-300"
                                            >
                                                Edit
                                            </Link>
                                            <button
                                                type="button"
                                                class="text-sm font-medium text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                                                @click="deleteExpense(expense.id)"
                                            >
                                                Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </template>

                            <!-- Empty state -->
                            <tr v-else>
                                <td colspan="7" class="px-6 py-16 text-center">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 48 48">
                                        <path
                                            d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02"
                                            stroke-width="2"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                        />
                                    </svg>
                                    <p class="mt-4 text-base font-medium text-gray-900 dark:text-white">No expenses yet</p>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Get started by adding your first expense.</p>
                                    <Link
                                        href="/expenses/create"
                                        class="mt-4 inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 transition-colors"
                                    >
                                        Add Expense
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div
                    v-if="expenses.last_page > 1"
                    class="flex items-center justify-between border-t border-gray-200 px-6 py-4 dark:border-gray-700"
                >
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Showing {{ expenses.from }}–{{ expenses.to }} of {{ expenses.total }} results
                    </p>
                    <div class="flex gap-1">
                        <template v-for="link in expenses.links" :key="link.label">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                preserve-scroll
                                class="inline-flex h-8 min-w-[2rem] items-center justify-center rounded px-2 text-sm transition-colors"
                                :class="link.active
                                    ? 'bg-indigo-600 text-white font-medium'
                                    : 'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700'"
                                v-html="link.label"
                            />
                            <span
                                v-else
                                class="inline-flex h-8 min-w-[2rem] items-center justify-center rounded px-2 text-sm text-gray-400 cursor-not-allowed"
                                v-html="link.label"
                            />
                        </template>
                    </div>
                </div>
            </div>
        </div>
    <!-- </AppLayout> -->
</template>