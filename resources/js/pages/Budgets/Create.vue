<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import InputError from '@/components/InputError.vue';

interface Category {
    id: number;
    name: string;
    code: string;
    color: string | null;
}

const props = defineProps<{
    categories: Category[];
    hasOverall: boolean;
}>();

const form = useForm({
    category_id: '' as string | null,
    amount: '',
    name: '',
    is_overall: false,
});

watch(() => form.is_overall, (val) => {
    if (val) form.category_id = null;
    else form.category_id = '';
});

const submit = () => {
    form.transform(data => ({
        ...data,
        category_id: data.is_overall ? null : data.category_id,
    })).post('/budgets');
};
</script>

<template>
    <Head title="New Budget" />

    <div class="flex h-full flex-1 flex-col gap-6 p-6">
        <div class="mx-auto w-full max-w-lg">
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">

                <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">New Budget</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Set a monthly spending limit.</p>
                </div>

                <form @submit.prevent="submit" class="p-6 space-y-5">

                    <!-- Overall toggle -->
                    <div class="flex items-center justify-between rounded-lg border border-gray-200 dark:border-gray-600 p-4">
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">Overall monthly budget</p>
                            <p class="text-xs text-gray-400 mt-0.5">Limit your total spending across all categories</p>
                        </div>
                        <button
                            type="button"
                            class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200"
                            :class="form.is_overall ? 'bg-indigo-600' : 'bg-gray-200 dark:bg-gray-600'"
                            :disabled="hasOverall && !form.is_overall"
                            @click="form.is_overall = !form.is_overall"
                        >
                            <span
                                class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition duration-200"
                                :class="form.is_overall ? 'translate-x-5' : 'translate-x-0'"
                            />
                        </button>
                    </div>
                    <p v-if="hasOverall && !form.is_overall" class="text-xs text-amber-600 dark:text-amber-400">
                        You already have an overall budget. Edit it instead.
                    </p>

                    <!-- Category (only when not overall) -->
                    <div v-if="!form.is_overall">
                        <label for="category_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            Category <span class="text-red-500">*</span>
                        </label>
                        <select
                            id="category_id"
                            v-model="form.category_id"
                            class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        >
                            <option value="" disabled>Select a category</option>
                            <option v-for="cat in categories" :key="cat.id" :value="String(cat.id)">
                                {{ cat.name }}
                            </option>
                        </select>
                        <p class="mt-1 text-xs text-gray-400">Only categories without an existing budget are shown.</p>
                        <InputError :message="form.errors.category_id" class="mt-1" />
                    </div>

                    <!-- Amount -->
                    <div>
                        <label for="amount" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            Monthly Limit (MYR) <span class="text-red-500">*</span>
                        </label>
                        <input
                            id="amount"
                            v-model="form.amount"
                            type="number"
                            step="0.01"
                            min="1"
                            placeholder="e.g. 500.00"
                            class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400"
                        />
                        <InputError :message="form.errors.amount" class="mt-1" />
                    </div>

                    <!-- Custom name -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            Custom Label <span class="text-xs font-normal text-gray-400">(optional)</span>
                        </label>
                        <input
                            id="name"
                            v-model="form.name"
                            type="text"
                            placeholder="e.g. Dining out limit"
                            class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400"
                        />
                        <InputError :message="form.errors.name" class="mt-1" />
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <a
                            href="/budgets"
                            class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                        >Cancel</a>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                        >
                            {{ form.processing ? 'Saving...' : 'Create Budget' }}
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</template>