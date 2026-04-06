<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';

interface BudgetData {
    id: number;
    category_id: number | null;
    amount: string;
    name: string;
    label: string;
    is_overall: boolean;
}

const props = defineProps<{ budget: BudgetData }>();

const form = useForm({
    amount: props.budget.amount,
    name: props.budget.name,
    _method: 'PUT',
});

const submit = () => form.post(`/budgets/${props.budget.id}`);
</script>

<template>
    <Head :title="`Edit: ${budget.label}`" />

    <div class="flex h-full flex-1 flex-col gap-6 p-6">
        <div class="mx-auto w-full max-w-lg">
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">

                <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Edit Budget</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ budget.label }}</p>
                </div>

                <form @submit.prevent="submit" class="p-6 space-y-5">

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
                            class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        />
                        <InputError :message="form.errors.amount" class="mt-1" />
                    </div>

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
                            :href="`/budgets/${budget.id}`"
                            class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                        >Cancel</a>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                        >
                            {{ form.processing ? 'Saving...' : 'Save Changes' }}
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</template>