<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';

interface Category {
    id: number;
    name: string;
    code: string;
    color: string | null;
    is_tax_deductible: boolean;
    is_active: boolean;
    deduction_type: string;
    deduction_label: string;
    annual_limit: string | null;
    description: string | null;
    expenses_count: number;
    created_at: string;
}

interface Expense {
    id: number;
    title: string;
    amount: number;
    type: 'income' | 'expense';
    expense_date: string;
    description: string | null;
}

const props = defineProps<{
    category: Category;
    expenses: Expense[];
    totalExpenses: number;
    totalIncome: number;
}>();

const fmt = (v: number) =>
    v.toLocaleString('en-MY', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const fmtLimit = (v: string | null) =>
    v ? `MYR ${parseFloat(v).toLocaleString('en-MY', { minimumFractionDigits: 2 })}` : 'Unlimited / Not Applicable';

const fmtDate = (d: string) =>
    new Date(d + 'T00:00:00').toLocaleDateString('en-MY', {
        year: 'numeric', month: 'short', day: 'numeric',
    });

const deleteCategory = () => {
    if (props.category.expenses_count > 0) {
        alert(`Cannot delete "${props.category.name}" — it has ${props.category.expenses_count} expense(s) linked to it.`);
        return;
    }
    if (confirm(`Delete "${props.category.name}"? This cannot be undone.`)) {
        router.delete(`/categories/${props.category.id}`);
    }
};
</script>

<template>
    <Head :title="category.name" />

    <div class="flex h-full flex-1 flex-col gap-6 p-6">
        <div class="mx-auto w-full max-w-2xl space-y-5">

            <!-- Main card -->
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">

                <!-- Header -->
                <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                    <div class="flex items-center gap-3">
                        <span
                            class="h-4 w-4 rounded-full flex-shrink-0"
                            :style="{ backgroundColor: category.color ?? '#94a3b8' }"
                        />
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ category.name }}</h2>
                    </div>
                    <div class="flex items-center gap-2">
                        <span
                            class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium"
                            :class="category.is_tax_deductible
                                ? 'bg-green-100 text-green-800 dark:bg-green-800/30 dark:text-green-400'
                                : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400'"
                        >
                            {{ category.is_tax_deductible ? 'Tax Deductible' : 'Not Deductible' }}
                        </span>
                        <span
                            class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium"
                            :class="category.is_active
                                ? 'bg-green-100 text-green-800 dark:bg-green-800/30 dark:text-green-400'
                                : 'bg-red-100 text-red-800 dark:bg-red-800/30 dark:text-red-400'"
                        >
                            {{ category.is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                </div>

                <!-- Totals -->
                <div class="grid grid-cols-3 divide-x divide-gray-200 dark:divide-gray-700 border-b border-gray-200 dark:border-gray-700">
                    <div class="px-6 py-4">
                        <p class="text-xs text-gray-500 dark:text-gray-400">Total Expenses</p>
                        <p class="mt-1 text-xl font-bold text-red-600 dark:text-red-400">MYR {{ fmt(totalExpenses) }}</p>
                    </div>
                    <div class="px-6 py-4">
                        <p class="text-xs text-gray-500 dark:text-gray-400">Total Income</p>
                        <p class="mt-1 text-xl font-bold text-green-600 dark:text-green-400">MYR {{ fmt(totalIncome) }}</p>
                    </div>
                    <div class="px-6 py-4">
                        <p class="text-xs text-gray-500 dark:text-gray-400">Entries</p>
                        <p class="mt-1 text-xl font-bold text-gray-900 dark:text-white">{{ category.expenses_count }}</p>
                    </div>
                </div>

                <!-- Details -->
                <dl class="divide-y divide-gray-200 dark:divide-gray-700">
                    <div class="flex justify-between px-6 py-3">
                        <dt class="text-sm text-gray-500 dark:text-gray-400">Code</dt>
                        <dd>
                            <code class="rounded bg-gray-100 px-1.5 py-0.5 text-xs text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                {{ category.code }}
                            </code>
                        </dd>
                    </div>
                    <div class="flex justify-between px-6 py-3">
                        <dt class="text-sm text-gray-500 dark:text-gray-400">LHDN Deduction Type</dt>
                        <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ category.deduction_label }}</dd>
                    </div>
                    <div class="flex justify-between px-6 py-3">
                        <dt class="text-sm text-gray-500 dark:text-gray-400">Annual Limit</dt>
                        <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ fmtLimit(category.annual_limit) }}</dd>
                    </div>
                    <div v-if="category.description" class="px-6 py-3">
                        <dt class="text-sm text-gray-500 dark:text-gray-400 mb-1">What qualifies</dt>
                        <dd class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed">{{ category.description }}</dd>
                    </div>
                    <div class="flex justify-between px-6 py-3">
                        <dt class="text-sm text-gray-500 dark:text-gray-400">Created</dt>
                        <dd class="text-sm text-gray-900 dark:text-white">{{ category.created_at }}</dd>
                    </div>
                </dl>
            </div>

            <!-- Recent expenses -->
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                        Recent Expenses
                        <span class="ml-1 text-xs font-normal text-gray-400">(last 10)</span>
                    </h3>
                    <Link href="/expenses" class="text-xs font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">
                        View all →
                    </Link>
                </div>

                <ul v-if="expenses.length > 0" class="divide-y divide-gray-200 dark:divide-gray-700">
                    <li
                        v-for="expense in expenses"
                        :key="expense.id"
                        class="flex items-center justify-between px-6 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors"
                    >
                        <div class="min-w-0">
                            <Link
                                :href="`/expenses/${expense.id}`"
                                class="truncate text-sm font-medium text-gray-900 hover:text-indigo-600 dark:text-white dark:hover:text-indigo-400"
                            >
                                {{ expense.title }}
                            </Link>
                            <p v-if="expense.description" class="truncate text-xs text-gray-400">{{ expense.description }}</p>
                            <p class="text-xs text-gray-400">{{ fmtDate(expense.expense_date) }}</p>
                        </div>
                        <span
                            class="ml-4 flex-shrink-0 text-sm font-semibold"
                            :class="expense.type === 'income'
                                ? 'text-green-600 dark:text-green-400'
                                : 'text-red-600 dark:text-red-400'"
                        >
                            {{ expense.type === 'income' ? '+' : '-' }} MYR {{ fmt(expense.amount) }}
                        </span>
                    </li>
                </ul>

                <div v-else class="px-6 py-10 text-center text-sm text-gray-400">
                    No expenses recorded under this category yet.
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-between">
                <Link
                    href="/categories"
                    class="text-sm font-medium text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white"
                >
                    ← Back to Categories
                </Link>
                <div class="flex gap-3">
                    <Link
                        :href="`/categories/${category.id}/edit`"
                        class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                    >Edit</Link>
                    <button
                        type="button"
                        class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 transition-colors"
                        @click="deleteCategory"
                    >Delete</button>
                </div>
            </div>

        </div>
    </div>
</template>