<script setup lang="ts">
interface Category {
    id: number;
    name: string;
    code: string;
    color: string | null;
}

interface Receipt {
    id: number;
    original_name: string;
}

interface Expense {
    id: number;
    title: string;
    amount: string;
    type: 'income' | 'expense';
    expense_date: string;
    description: string | null;
    category: Category | null;
    receipts: Receipt[];
}

const props = defineProps<{ expense: Expense }>();

const formatMoney = (value: string | number): string =>
    parseFloat(String(value)).toLocaleString('en-MY', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });

const formatDate = (value: string): string =>
    new Date(value + 'T00:00:00').toLocaleDateString('en-MY');
</script>

<template>
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="flex items-start justify-between">
            <div class="flex items-center gap-3">
                <!-- Category colour dot -->
                <div
                    class="h-9 w-9 rounded-full flex-shrink-0"
                    :style="{ backgroundColor: expense.category?.color ?? '#94a3b8' }"
                />
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ formatDate(expense.expense_date) }}</p>
                    <h3 class="font-medium text-gray-900 dark:text-white">{{ expense.title }}</h3>
                    <p v-if="expense.description" class="text-xs text-gray-400 truncate max-w-[180px]">
                        {{ expense.description }}
                    </p>
                </div>
            </div>

            <div class="text-right">
                <p class="text-xs text-gray-400">MYR</p>
                <p
                    class="text-lg font-bold"
                    :class="expense.type === 'income'
                        ? 'text-green-600 dark:text-green-400'
                        : 'text-red-600 dark:text-red-400'"
                >
                    {{ expense.type === 'income' ? '+' : '-' }}{{ formatMoney(expense.amount) }}
                </p>
            </div>
        </div>

        <div class="mt-3 flex items-center justify-between text-xs text-gray-400">
            <span
                v-if="expense.category"
                class="rounded-full px-2 py-0.5 text-white text-[11px] font-medium"
                :style="{ backgroundColor: expense.category.color ?? '#94a3b8' }"
            >
                {{ expense.category.name }}
            </span>
            <span v-else>Uncategorised</span>

            <span>{{ expense.receipts?.length ?? 0 }} receipt{{ (expense.receipts?.length ?? 0) !== 1 ? 's' : '' }}</span>
        </div>
    </div>
</template>