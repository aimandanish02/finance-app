<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

interface Receipt {
    id: number;
    original_name: string;
    mime_type: string;
    url: string;
}

interface Expense {
    id: number;
    title: string;
    amount: string;
    type: 'income' | 'expense';
    expense_date: string;
    description: string | null;
    notes: string | null;
    is_active: boolean;
    category: { id: number; name: string; code: string; color: string | null } | null;
    receipts: Receipt[];
    created_at: string;
}

interface Props {
    expense: Expense;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Expenses', href: '/expenses' },
    { title: props.expense.title, href: `/expenses/${props.expense.id}` },
];

const formatCurrency = (value: string | number) =>
    parseFloat(String(value)).toLocaleString('en-MY', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });

const formatDate = (dateString: string) =>
    new Date(dateString + 'T00:00:00').toLocaleDateString('en-MY', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });

const deleteExpense = () => {
    if (confirm('Are you sure you want to delete this expense? This cannot be undone.')) {
        router.delete(`/expenses/${props.expense.id}`);
    }
};

const isImage = (mimeType: string) => mimeType.startsWith('image/');
</script>

<template>
        <Head :title="expense.title" />

        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <div class="mx-auto w-full max-w-2xl space-y-5">

                <!-- Main Detail Card -->
                <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">

                    <!-- Header -->
                    <div class="flex items-start justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ expense.title }}</h2>
                            <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">{{ formatDate(expense.expense_date) }}</p>
                        </div>
                        <span
                            class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold"
                            :class="expense.type === 'income'
                                ? 'bg-green-100 text-green-800 dark:bg-green-800/30 dark:text-green-400'
                                : 'bg-red-100 text-red-800 dark:bg-red-800/30 dark:text-red-400'"
                        >
                            {{ expense.type }}
                        </span>
                    </div>

                    <!-- Amount -->
                    <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Amount</p>
                        <p
                            class="mt-1 text-3xl font-bold"
                            :class="expense.type === 'income'
                                ? 'text-green-600 dark:text-green-400'
                                : 'text-red-600 dark:text-red-400'"
                        >
                            {{ expense.type === 'income' ? '+' : '-' }} MYR {{ formatCurrency(expense.amount) }}
                        </p>
                    </div>

                    <!-- Details -->
                    <dl class="divide-y divide-gray-200 dark:divide-gray-700">
                        <div class="flex justify-between px-6 py-3">
                            <dt class="text-sm text-gray-500 dark:text-gray-400">Category</dt>
                            <dd>
                                <span
                                    v-if="expense.category"
                                    class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium text-white"
                                    :style="{ backgroundColor: expense.category.color ?? '#94a3b8' }"
                                >
                                    {{ expense.category.name }}
                                </span>
                                <span v-else class="text-sm text-gray-400">—</span>
                            </dd>
                        </div>
                        <div v-if="expense.description" class="flex justify-between px-6 py-3">
                            <dt class="text-sm text-gray-500 dark:text-gray-400">Description</dt>
                            <dd class="text-sm text-gray-900 dark:text-white max-w-xs text-right">{{ expense.description }}</dd>
                        </div>
                        <div v-if="expense.notes" class="px-6 py-3">
                            <dt class="text-sm text-gray-500 dark:text-gray-400">Notes</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white whitespace-pre-line">{{ expense.notes }}</dd>
                        </div>
                        <div class="flex justify-between px-6 py-3">
                            <dt class="text-sm text-gray-500 dark:text-gray-400">Created</dt>
                            <dd class="text-sm text-gray-900 dark:text-white">{{ expense.created_at }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Receipts -->
                <div
                    v-if="expense.receipts.length > 0"
                    class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800"
                >
                    <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                            Receipts ({{ expense.receipts.length }})
                        </h3>
                    </div>
                    <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                        <li v-for="receipt in expense.receipts" :key="receipt.id" class="flex items-center justify-between px-6 py-3">
                            <div class="flex items-center gap-3">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                </svg>
                                <span class="text-sm text-gray-700 dark:text-gray-300">{{ receipt.original_name }}</span>
                            </div>
                            <a
                                :href="receipt.url"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400"
                            >
                                {{ isImage(receipt.mime_type) ? 'View' : 'Download' }}
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-between">
                    <Link
                        href="/expenses"
                        class="text-sm font-medium text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white"
                    >
                        ← Back to Expenses
                    </Link>
                    <div class="flex gap-3">
                        <Link
                            :href="`/expenses/${expense.id}/edit`"
                            class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                        >
                            Edit
                        </Link>
                        <button
                            type="button"
                            class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 transition-colors"
                            @click="deleteExpense"
                        >
                            Delete
                        </button>
                    </div>
                </div>

            </div>
        </div>
</template>