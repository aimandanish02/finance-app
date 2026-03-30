<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import InputError from '@/components/InputError.vue';
import { type BreadcrumbItem } from '@/types';

interface Category {
    id: number;
    name: string;
    code: string;
    color: string | null;
    is_tax_deductible: boolean;
    is_active: boolean;
    expenses_count: number;
}

interface Props {
    categories: Category[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Categories', href: '/categories' },
];

// ─── Create form ──────────────────────────────────────────────
const showCreateModal = ref(false);

const createForm = useForm({
    name: '',
    code: '',
    color: '#6366f1',
    is_tax_deductible: false,
    is_active: true,
});

const submitCreate = () => {
    createForm.post('/categories', {
        onSuccess: () => {
            showCreateModal.value = false;
            createForm.reset();
        },
    });
};

// ─── Edit form ────────────────────────────────────────────────
const showEditModal = ref(false);
const editingCategory = ref<Category | null>(null);

const editForm = useForm({
    name: '',
    code: '',
    color: '#6366f1',
    is_tax_deductible: false,
    is_active: true,
    _method: 'PUT',
});

const openEdit = (category: Category) => {
    editingCategory.value = category;
    editForm.name             = category.name;
    editForm.code             = category.code;
    editForm.color            = category.color ?? '#6366f1';
    editForm.is_tax_deductible = category.is_tax_deductible;
    editForm.is_active        = category.is_active;
    showEditModal.value       = true;
};

const submitEdit = () => {
    if (!editingCategory.value) return;
    editForm.post(`/categories/${editingCategory.value.id}`, {
        onSuccess: () => {
            showEditModal.value = false;
            editingCategory.value = null;
        },
    });
};

// ─── Delete ───────────────────────────────────────────────────
const deleteCategory = (category: Category) => {
    if (category.expenses_count > 0) {
        alert(`Cannot delete "${category.name}" — it has ${category.expenses_count} expense(s) linked to it.`);
        return;
    }
    if (confirm(`Delete category "${category.name}"?`)) {
        router.delete(`/categories/${category.id}`);
    }
};
</script>

<template>
        <Head title="Categories" />

        <div class="flex h-full flex-1 flex-col gap-6 p-6">

            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold text-gray-900 dark:text-white">Categories</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage expense and income categories.</p>
                </div>
                <button
                    type="button"
                    class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 transition-colors"
                    @click="showCreateModal = true"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    New Category
                </button>
            </div>

            <!-- Table -->
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Code</th>
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
                                <!-- Name + colour dot -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <span
                                            class="h-3 w-3 rounded-full flex-shrink-0"
                                            :style="{ backgroundColor: category.color ?? '#94a3b8' }"
                                        />
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ category.name }}</span>
                                    </div>
                                </td>
                                <!-- Code -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <code class="rounded bg-gray-100 px-1.5 py-0.5 text-xs text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                        {{ category.code }}
                                    </code>
                                </td>
                                <!-- Tax deductible -->
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
                                <!-- Active -->
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
                                <!-- Expense count -->
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ category.expenses_count }}
                                </td>
                                <!-- Actions -->
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="flex items-center justify-end gap-3">
                                        <button
                                            type="button"
                                            class="text-sm font-medium text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-300"
                                            @click="openEdit(category)"
                                        >
                                            Edit
                                        </button>
                                        <button
                                            type="button"
                                            class="text-sm font-medium text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                                            @click="deleteCategory(category)"
                                        >
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>

                        <!-- Empty state -->
                        <tr v-else>
                            <td colspan="6" class="px-6 py-16 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                </svg>
                                <p class="mt-4 text-base font-medium text-gray-900 dark:text-white">No categories yet</p>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Run the CategorySeeder or create one manually.</p>
                                <button
                                    type="button"
                                    class="mt-4 inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 transition-colors"
                                    @click="showCreateModal = true"
                                >
                                    Create Category
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ─── Create Modal ─────────────────────────────────────────── -->
        <Teleport to="body">
            <div
                v-if="showCreateModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
                @click.self="showCreateModal = false"
            >
                <div class="w-full max-w-md rounded-xl bg-white shadow-xl dark:bg-gray-800">
                    <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                        <h2 class="text-base font-semibold text-gray-900 dark:text-white">New Category</h2>
                        <button type="button" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" @click="showCreateModal = false">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form @submit.prevent="submitCreate" class="p-6 space-y-4">

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                Name <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="createForm.name"
                                type="text"
                                placeholder="e.g. Housing"
                                class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            />
                            <InputError :message="createForm.errors.name" class="mt-1" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                Code <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="createForm.code"
                                type="text"
                                placeholder="e.g. HOUSE"
                                class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-mono text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            />
                            <InputError :message="createForm.errors.code" class="mt-1" />
                        </div>

                        <div class="flex items-center gap-4">
                            <div class="flex-1">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Colour</label>
                                <div class="flex items-center gap-2">
                                    <input
                                        v-model="createForm.color"
                                        type="color"
                                        class="h-9 w-12 cursor-pointer rounded border border-gray-300 p-0.5 dark:border-gray-600"
                                    />
                                    <span class="text-sm text-gray-500 dark:text-gray-400 font-mono">{{ createForm.color }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-6">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input v-model="createForm.is_tax_deductible" type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                <span class="text-sm text-gray-700 dark:text-gray-300">Tax deductible</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input v-model="createForm.is_active" type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                <span class="text-sm text-gray-700 dark:text-gray-300">Active</span>
                            </label>
                        </div>

                        <div class="flex justify-end gap-3 pt-2">
                            <button
                                type="button"
                                class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300"
                                @click="showCreateModal = false"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="createForm.processing"
                                class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50 transition-colors"
                            >
                                {{ createForm.processing ? 'Saving...' : 'Create' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- ─── Edit Modal ───────────────────────────────────────────── -->
        <Teleport to="body">
            <div
                v-if="showEditModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
                @click.self="showEditModal = false"
            >
                <div class="w-full max-w-md rounded-xl bg-white shadow-xl dark:bg-gray-800">
                    <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Edit Category</h2>
                        <button type="button" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" @click="showEditModal = false">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form @submit.prevent="submitEdit" class="p-6 space-y-4">

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                Name <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="editForm.name"
                                type="text"
                                class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            />
                            <InputError :message="editForm.errors.name" class="mt-1" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                Code <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="editForm.code"
                                type="text"
                                class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-mono text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            />
                            <InputError :message="editForm.errors.code" class="mt-1" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Colour</label>
                            <div class="flex items-center gap-2">
                                <input
                                    v-model="editForm.color"
                                    type="color"
                                    class="h-9 w-12 cursor-pointer rounded border border-gray-300 p-0.5 dark:border-gray-600"
                                />
                                <span class="text-sm text-gray-500 dark:text-gray-400 font-mono">{{ editForm.color }}</span>
                            </div>
                        </div>

                        <div class="flex items-center gap-6">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input v-model="editForm.is_tax_deductible" type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                <span class="text-sm text-gray-700 dark:text-gray-300">Tax deductible</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input v-model="editForm.is_active" type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                <span class="text-sm text-gray-700 dark:text-gray-300">Active</span>
                            </label>
                        </div>

                        <div class="flex justify-end gap-3 pt-2">
                            <button
                                type="button"
                                class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300"
                                @click="showEditModal = false"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="editForm.processing"
                                class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50 transition-colors"
                            >
                                {{ editForm.processing ? 'Saving...' : 'Save Changes' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

</template>