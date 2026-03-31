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
    annual_limit: string | null;
    expenses_count: number;
}

defineProps<{
    categories: Category[];
    deductionTypes: Record<string, string>;
}>();

const deleteCategory = (category: Category) => {
    if (category.expenses_count > 0) {
        alert(`Cannot delete "${category.name}" — it has ${category.expenses_count} expense(s) linked to it.`);
        return;
    }
    if (confirm(`Delete category "${category.name}"?`)) {
        router.delete(`/categories/${category.id}`);
    }
};

const fmt = (v: string | null) =>
    v ? parseFloat(v).toLocaleString('en-MY', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '—';
</script>

<template>
    <Head title="Categories" />

    <div class="flex h-full flex-1 flex-col gap-6 p-6">

        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold text-gray-900 dark:text-white">Categories</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    LHDN-mapped deduction categories for Malaysian tax filing.
                </p>
            </div>
            <Link
                href="/categories/create"
                class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 transition-colors"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                New Category
            </Link>
        </div>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">LHDN Deduction Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Annual Limit</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Tax Deductible</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Expenses</th>
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <template v-if="categories.length > 0">
                        <tr
                            v-for="category in categories"
                            :key="category.id"
                            class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors"
                        >
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <span
                                        class="h-3 w-3 rounded-full flex-shrink-0"
                                        :style="{ backgroundColor: category.color ?? '#94a3b8' }"
                                    />
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ category.name }}</p>
                                        <code class="text-[10px] text-gray-400">{{ category.code }}</code>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-gray-700 dark:text-gray-300">
                                    {{ deductionTypes[category.deduction_type] ?? category.deduction_type }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                <span v-if="category.annual_limit">MYR {{ fmt(category.annual_limit) }}</span>
                                <span v-else class="text-gray-400">—</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium"
                                    :class="category.is_tax_deductible
                                        ? 'bg-green-100 text-green-800 dark:bg-green-800/30 dark:text-green-400'
                                        : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400'"
                                >
                                    {{ category.is_tax_deductible ? 'Yes' : 'No' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium"
                                    :class="category.is_active
                                        ? 'bg-green-100 text-green-800 dark:bg-green-800/30 dark:text-green-400'
                                        : 'bg-red-100 text-red-800 dark:bg-red-800/30 dark:text-red-400'"
                                >
                                    {{ category.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ category.expenses_count }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <div class="flex items-center justify-end gap-3">
                                    <Link
                                        :href="`/categories/${category.id}`"
                                        class="text-sm font-medium text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                                    >View</Link>
                                    <Link
                                        :href="`/categories/${category.id}/edit`"
                                        class="text-sm font-medium text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-300"
                                    >Edit</Link>
                                    <button
                                        type="button"
                                        class="text-sm font-medium text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                                        @click="deleteCategory(category)"
                                    >Delete</button>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <tr v-else>
                        <td colspan="7" class="px-6 py-16 text-center">
                            <p class="mt-4 text-base font-medium text-gray-900 dark:text-white">No categories yet</p>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Run the CategorySeeder or create one manually.</p>
                            <Link
                                href="/categories/create"
                                class="mt-4 inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 transition-colors"
                            >New Category</Link>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>