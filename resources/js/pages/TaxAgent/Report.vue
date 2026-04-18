<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

interface DeductionRow {
    deduction_type: string; deduction_label: string;
    total_spent: number; annual_limit: number | null;
    claimable: number; over_limit: boolean; entries_count: number;
}

const props = defineProps<{
    year: number;
    label: string;
    breakdown: DeductionRow[];
    totalClaimable: number;
    generatedAt: string;
    expiresAt: string | null;
    sharedBy: string;
}>();

const fmt = (v: number) =>
    v.toLocaleString('en-MY', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
</script>

<template>
    <Head :title="label" />

    <div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-12 px-4">
        <div class="mx-auto max-w-3xl">

            <!-- Report header -->
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800 overflow-hidden mb-6">
                <div class="bg-indigo-600 px-8 py-6">
                    <h1 class="text-xl font-bold text-white">{{ label }}</h1>
                    <p class="mt-1 text-sm text-indigo-200">Year of Assessment {{ year }} · LHDN Malaysia</p>
                </div>
                <div class="grid grid-cols-2 gap-0 sm:grid-cols-4 divide-x divide-y sm:divide-y-0 divide-gray-200 dark:divide-gray-700">
                    <div class="px-5 py-4">
                        <p class="text-xs text-gray-500 dark:text-gray-400">Shared by</p>
                        <p class="text-sm font-medium text-gray-900 dark:text-white mt-0.5">{{ sharedBy }}</p>
                    </div>
                    <div class="px-5 py-4">
                        <p class="text-xs text-gray-500 dark:text-gray-400">Generated</p>
                        <p class="text-sm font-medium text-gray-900 dark:text-white mt-0.5">{{ generatedAt }}</p>
                    </div>
                    <div class="px-5 py-4">
                        <p class="text-xs text-gray-500 dark:text-gray-400">Expires</p>
                        <p class="text-sm font-medium text-gray-900 dark:text-white mt-0.5">{{ expiresAt ?? 'Never' }}</p>
                    </div>
                    <div class="px-5 py-4">
                        <p class="text-xs text-gray-500 dark:text-gray-400">Total claimable</p>
                        <p class="text-base font-bold text-indigo-600 dark:text-indigo-400 mt-0.5">MYR {{ fmt(totalClaimable) }}</p>
                    </div>
                </div>
            </div>

            <!-- Read-only notice -->
            <div class="rounded-xl border border-blue-200 bg-blue-50 px-5 py-3 mb-6 dark:border-blue-800 dark:bg-blue-900/20">
                <p class="text-sm text-blue-800 dark:text-blue-300">
                    This is a read-only tax deduction summary. It has been shared with you by {{ sharedBy }} for reference during tax preparation.
                </p>
            </div>

            <!-- Breakdown table -->
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Deduction breakdown</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-700/30">
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Deduction type</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Entries</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Total spent</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">LHDN limit</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Claimable</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="row in breakdown" :key="row.deduction_type"
                                :class="row.over_limit ? 'bg-red-50/50 dark:bg-red-900/10' : ''">
                                <td class="px-6 py-3">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ row.deduction_label }}</p>
                                    <span v-if="row.over_limit"
                                        class="text-xs text-red-600 dark:text-red-400">Over LHDN limit</span>
                                </td>
                                <td class="px-6 py-3 text-right text-sm text-gray-500 dark:text-gray-400">{{ row.entries_count }}</td>
                                <td class="px-6 py-3 text-right text-sm text-gray-900 dark:text-white">MYR {{ fmt(row.total_spent) }}</td>
                                <td class="px-6 py-3 text-right text-sm text-gray-500 dark:text-gray-400">
                                    {{ row.annual_limit ? `MYR ${fmt(row.annual_limit)}` : 'No limit' }}
                                </td>
                                <td class="px-6 py-3 text-right">
                                    <span class="text-sm font-bold text-indigo-600 dark:text-indigo-400">
                                        MYR {{ fmt(row.claimable) }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="border-t-2 border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/30">
                                <td colspan="4" class="px-6 py-3 text-sm font-semibold text-gray-900 dark:text-white">Total claimable deductions</td>
                                <td class="px-6 py-3 text-right text-base font-bold text-indigo-600 dark:text-indigo-400">
                                    MYR {{ fmt(totalClaimable) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Footer disclaimer -->
            <p class="mt-6 text-center text-xs text-gray-400">
                For tax reference purposes only. Verify all figures against original receipts before submission to LHDN.
            </p>
        </div>
    </div>
</template>