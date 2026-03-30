<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import InputError from '@/components/InputError.vue';
import { type BreadcrumbItem } from '@/types';

interface Category {
    id: number;
    name: string;
    code: string;
    color: string | null;
}

interface ExpenseData {
    id: number;
    title: string;
    amount: string;
    type: 'income' | 'expense';
    expense_date: string;
    description: string | null;
    notes: string | null;
    category_id: number;
}

interface Props {
    expense: ExpenseData;
    categories: Category[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Expenses', href: '/expenses' },
    { title: props.expense.title, href: `/expenses/${props.expense.id}` },
    { title: 'Edit', href: `/expenses/${props.expense.id}/edit` },
];

const form = useForm({
    title: props.expense.title,
    category_id: String(props.expense.category_id),
    amount: props.expense.amount,
    type: props.expense.type,
    expense_date: props.expense.expense_date,
    description: props.expense.description ?? '',
    notes: props.expense.notes ?? '',
    receipts: [] as File[],
    // Laravel method spoofing for PUT via POST with file upload
    _method: 'PUT',
});

const submit = () => {
    form.post(`/expenses/${props.expense.id}`, {
        forceFormData: true,
    });
};

const handleFileChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files) {
        form.receipts = Array.from(target.files);
    }
};
</script>

<template>
        <Head :title="`Edit: ${expense.title}`" />

        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <div class="mx-auto w-full max-w-2xl">
                <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">

                    <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Edit Expense</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Update the details below.</p>
                    </div>

                    <form @submit.prevent="submit" class="p-6 space-y-5" enctype="multipart/form-data">

                        <!-- Type Toggle -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Type</label>
                            <div class="flex rounded-lg border border-gray-300 dark:border-gray-600 overflow-hidden">
                                <button
                                    type="button"
                                    class="flex-1 py-2 text-sm font-medium transition-colors"
                                    :class="form.type === 'expense'
                                        ? 'bg-red-600 text-white'
                                        : 'bg-white text-gray-700 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700'"
                                    @click="form.type = 'expense'"
                                >
                                    Expense
                                </button>
                                <button
                                    type="button"
                                    class="flex-1 py-2 text-sm font-medium transition-colors"
                                    :class="form.type === 'income'
                                        ? 'bg-green-600 text-white'
                                        : 'bg-white text-gray-700 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700'"
                                    @click="form.type = 'income'"
                                >
                                    Income
                                </button>
                            </div>
                            <InputError :message="form.errors.type" class="mt-1" />
                        </div>

                        <!-- Title -->
                        <div>
                            <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                Title <span class="text-red-500">*</span>
                            </label>
                            <input
                                id="title"
                                v-model="form.title"
                                type="text"
                                class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            />
                            <InputError :message="form.errors.title" class="mt-1" />
                        </div>

                        <!-- Amount + Date -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="amount" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                    Amount (MYR) <span class="text-red-500">*</span>
                                </label>
                                <input
                                    id="amount"
                                    v-model="form.amount"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                />
                                <InputError :message="form.errors.amount" class="mt-1" />
                            </div>
                            <div>
                                <label for="expense_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                    Date <span class="text-red-500">*</span>
                                </label>
                                <input
                                    id="expense_date"
                                    v-model="form.expense_date"
                                    type="date"
                                    class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                />
                                <InputError :message="form.errors.expense_date" class="mt-1" />
                            </div>
                        </div>

                        <!-- Category -->
                        <div>
                            <label for="category_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                Category <span class="text-red-500">*</span>
                            </label>
                            <select
                                id="category_id"
                                v-model="form.category_id"
                                class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            >
                                <option value="" disabled>Select a category</option>
                                <option
                                    v-for="category in categories"
                                    :key="category.id"
                                    :value="String(category.id)"
                                >
                                    {{ category.name }}
                                </option>
                            </select>
                            <InputError :message="form.errors.category_id" class="mt-1" />
                        </div>

                        <!-- Description -->
                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                Description
                            </label>
                            <input
                                id="description"
                                v-model="form.description"
                                type="text"
                                class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            />
                            <InputError :message="form.errors.description" class="mt-1" />
                        </div>

                        <!-- Notes -->
                        <div>
                            <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                Notes
                            </label>
                            <textarea
                                id="notes"
                                v-model="form.notes"
                                rows="3"
                                class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            />
                            <InputError :message="form.errors.notes" class="mt-1" />
                        </div>

                        <!-- Receipt Upload (additional) -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                Add More Receipts
                            </label>
                            <div class="rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-600 px-6 py-6 text-center">
                                <label for="receipts" class="cursor-pointer text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">
                                    Click to select files
                                </label>
                                <p class="mt-1 text-xs text-gray-400">JPG, PNG, GIF, WEBP, PDF up to 10MB each</p>
                                <input
                                    id="receipts"
                                    type="file"
                                    multiple
                                    accept="image/jpeg,image/jpg,image/png,image/gif,image/webp,application/pdf"
                                    class="sr-only"
                                    @change="handleFileChange"
                                />
                            </div>
                            <ul v-if="form.receipts.length > 0" class="mt-2 space-y-1">
                                <li
                                    v-for="(file, index) in form.receipts"
                                    :key="index"
                                    class="text-sm text-gray-600 dark:text-gray-400"
                                >
                                    {{ file.name }}
                                </li>
                            </ul>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-end gap-3 pt-2">
                            <a
                                :href="`/expenses/${expense.id}`"
                                class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                            >
                                Cancel
                            </a>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                            >
                                <svg v-if="form.processing" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 12 3 12 12h4a8 8 0 01-8 8z" />
                                </svg>
                                {{ form.processing ? 'Saving...' : 'Update Expense' }}
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
</template>