<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import InputError from '@/components/InputError.vue';

const props = defineProps<{
    deductionTypes: Record<string, string>;
}>();

const form = useForm({
    name: '',
    code: '',
    color: '#6366f1',
    is_tax_deductible: false,
    is_active: true,
    deduction_type: 'NOT_DEDUCTIBLE',
    annual_limit: '',
    description: '',
});

// Auto-toggle is_tax_deductible when deduction type changes
watch(() => form.deduction_type, (val) => {
    form.is_tax_deductible = val !== 'NOT_DEDUCTIBLE';
});

const submit = () => form.post('/categories');
</script>

<template>
    <Head title="New Category" />

    <div class="flex h-full flex-1 flex-col gap-6 p-6">
        <div class="mx-auto w-full max-w-2xl">
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">

                <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">New Category</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Add a new LHDN-mapped deduction category.</p>
                </div>

                <form @submit.prevent="submit" class="p-6 space-y-5">

                    <!-- Name -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            Name <span class="text-red-500">*</span>
                        </label>
                        <input
                            id="name"
                            v-model="form.name"
                            type="text"
                            placeholder="e.g. Medical — Self / Spouse / Child"
                            class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400"
                        />
                        <InputError :message="form.errors.name" class="mt-1" />
                    </div>

                    <!-- Code -->
                    <div>
                        <label for="code" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            Code <span class="text-red-500">*</span>
                        </label>
                        <input
                            id="code"
                            v-model="form.code"
                            type="text"
                            placeholder="e.g. MEDICAL_SELF"
                            class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 font-mono text-sm text-gray-900 placeholder-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400"
                        />
                        <p class="mt-1 text-xs text-gray-400">Unique uppercase identifier, e.g. MEDICAL_SELF, EPF, ZAKAT</p>
                        <InputError :message="form.errors.code" class="mt-1" />
                    </div>

                    <!-- LHDN Deduction Type -->
                    <div>
                        <label for="deduction_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            LHDN Deduction Type <span class="text-red-500">*</span>
                        </label>
                        <select
                            id="deduction_type"
                            v-model="form.deduction_type"
                            class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        >
                            <option
                                v-for="(label, key) in deductionTypes"
                                :key="key"
                                :value="key"
                            >
                                {{ label }}
                            </option>
                        </select>
                        <InputError :message="form.errors.deduction_type" class="mt-1" />
                    </div>

                    <!-- Annual Limit -->
                    <div>
                        <label for="annual_limit" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            Annual Limit (MYR)
                        </label>
                        <input
                            id="annual_limit"
                            v-model="form.annual_limit"
                            type="number"
                            step="0.01"
                            min="0"
                            placeholder="Leave blank if unlimited or not applicable"
                            class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400"
                        />
                        <p class="mt-1 text-xs text-gray-400">Maximum claimable amount per year as per LHDN. Leave blank for Zakat, Donation, Business, and Employment expenses.</p>
                        <InputError :message="form.errors.annual_limit" class="mt-1" />
                    </div>

                    <!-- Description -->
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            Description
                        </label>
                        <textarea
                            id="description"
                            v-model="form.description"
                            rows="3"
                            placeholder="What qualifies for this deduction?"
                            class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400"
                        />
                        <InputError :message="form.errors.description" class="mt-1" />
                    </div>

                    <!-- Colour -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Colour</label>
                        <div class="flex items-center gap-3">
                            <input
                                v-model="form.color"
                                type="color"
                                class="h-9 w-12 cursor-pointer rounded border border-gray-300 p-0.5 dark:border-gray-600"
                            />
                            <span class="font-mono text-sm text-gray-500 dark:text-gray-400">{{ form.color }}</span>
                        </div>
                        <InputError :message="form.errors.color" class="mt-1" />
                    </div>

                    <!-- Checkboxes -->
                    <div class="flex items-center gap-6">
                        <label class="flex cursor-pointer items-center gap-2">
                            <input
                                v-model="form.is_tax_deductible"
                                type="checkbox"
                                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                            />
                            <span class="text-sm text-gray-700 dark:text-gray-300">Tax deductible</span>
                        </label>
                        <label class="flex cursor-pointer items-center gap-2">
                            <input
                                v-model="form.is_active"
                                type="checkbox"
                                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                            />
                            <span class="text-sm text-gray-700 dark:text-gray-300">Active</span>
                        </label>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <a
                            href="/categories"
                            class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                        >Cancel</a>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                        >
                            <svg v-if="form.processing" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 12 3 12 12h4a8 8 0 01-8 8z" />
                            </svg>
                            {{ form.processing ? 'Saving...' : 'Create Category' }}
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</template>