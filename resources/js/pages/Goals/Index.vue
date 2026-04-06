<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';

interface Goal {
    id: number;
    name: string;
    type: 'target_amount' | 'monthly_percentage';
    target_amount: number | null;
    target_percentage: number | null;
    target_monthly: number | null;
    current_savings: number;
    deadline: string | null;
    color: string;
    notes: string | null;
    is_completed: boolean;
    pct: number;
    days_remaining: number | null;
    months_to_goal: number | null;
}

defineProps<{
    goals: Goal[];
    monthlyIncome: number;
    currentMonth: string;
}>();

const fmt = (v: number) =>
    v.toLocaleString('en-MY', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const fmtDate = (d: string) =>
    new Date(d + 'T00:00:00').toLocaleDateString('en-MY', { year: 'numeric', month: 'short', day: 'numeric' });

const deleteGoal = (id: number, name: string) => {
    if (confirm(`Delete goal "${name}"?`)) {
        router.delete(`/goals/${id}`);
    }
};
</script>

<template>
    <Head title="Goals" />

    <div class="flex h-full flex-1 flex-col gap-6 p-6">

        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold text-gray-900 dark:text-white">Savings Goals</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Track your progress toward financial targets</p>
            </div>
            <Link
                href="/goals/create"
                class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 transition-colors"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                New Goal
            </Link>
        </div>

        <!-- No income warning for monthly % goals -->
        <div v-if="monthlyIncome === 0" class="rounded-xl border border-amber-200 bg-amber-50 px-5 py-3 dark:border-amber-800 dark:bg-amber-900/20">
            <p class="text-sm text-amber-800 dark:text-amber-300">
                No income recorded this month — monthly percentage goals won't show progress until you add income entries.
            </p>
        </div>

        <!-- Active goals -->
        <div v-if="goals.filter(g => !g.is_completed).length > 0">
            <h2 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-3">Active</h2>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="goal in goals.filter(g => !g.is_completed)"
                    :key="goal.id"
                    class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800"
                >
                    <!-- Header -->
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="h-3 w-3 rounded-full flex-shrink-0" :style="{ backgroundColor: goal.color }"/>
                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ goal.name }}</p>
                        </div>
                        <span class="ml-2 flex-shrink-0 text-xs rounded-full px-2 py-0.5 bg-indigo-100 text-indigo-800 dark:bg-indigo-800/30 dark:text-indigo-400">
                            {{ goal.type === 'target_amount' ? 'Target' : 'Monthly %' }}
                        </span>
                    </div>

                    <!-- Progress -->
                    <div class="mb-3">
                        <div class="flex items-baseline justify-between mb-1">
                            <span class="text-2xl font-bold text-gray-900 dark:text-white">{{ goal.pct }}%</span>
                            <span class="text-xs text-gray-400">
                                {{ goal.type === 'target_amount'
                                    ? `MYR ${fmt(goal.current_savings)} of MYR ${fmt(goal.target_amount!)}`
                                    : `${goal.target_percentage}% of income` }}
                            </span>
                        </div>
                        <div class="h-2.5 w-full rounded-full bg-gray-200 dark:bg-gray-700">
                            <div
                                class="h-2.5 rounded-full transition-all duration-500"
                                :style="{ width: `${goal.pct}%`, backgroundColor: goal.color }"
                            />
                        </div>
                    </div>

                    <!-- Meta -->
                    <div class="space-y-1 mb-4">
                        <p v-if="goal.deadline" class="text-xs text-gray-400">
                            Deadline: {{ fmtDate(goal.deadline) }}
                            <span v-if="goal.days_remaining !== null" class="ml-1">
                                ({{ goal.days_remaining }}d remaining)
                            </span>
                        </p>
                        <p v-if="goal.months_to_goal !== null" class="text-xs text-gray-400">
                            At current rate: ~{{ goal.months_to_goal }} month{{ goal.months_to_goal !== 1 ? 's' : '' }} to reach goal
                        </p>
                        <p v-if="goal.target_monthly" class="text-xs text-gray-400">
                            Target: MYR {{ fmt(goal.target_monthly) }}/month
                        </p>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-end gap-3">
                        <Link :href="`/goals/${goal.id}`"
                            class="text-sm font-medium text-indigo-600 hover:text-indigo-900 dark:text-indigo-400">View</Link>
                        <Link :href="`/goals/${goal.id}/edit`"
                            class="text-sm font-medium text-gray-600 hover:text-gray-900 dark:text-gray-400">Edit</Link>
                        <button type="button"
                            class="text-sm font-medium text-red-600 hover:text-red-900 dark:text-red-400"
                            @click="deleteGoal(goal.id, goal.name)">Delete</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Completed goals -->
        <div v-if="goals.filter(g => g.is_completed).length > 0">
            <h2 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-3">Completed</h2>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="goal in goals.filter(g => g.is_completed)"
                    :key="goal.id"
                    class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm opacity-60 dark:border-gray-700 dark:bg-gray-800"
                >
                    <div class="flex items-center gap-2 mb-2">
                        <span class="h-3 w-3 rounded-full" :style="{ backgroundColor: goal.color }"/>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ goal.name }}</p>
                        <span class="ml-auto text-xs rounded-full px-2 py-0.5 bg-green-100 text-green-800 dark:bg-green-800/30 dark:text-green-400">Done</span>
                    </div>
                    <div class="h-2 w-full rounded-full" :style="{ backgroundColor: goal.color }"/>
                    <div class="mt-3 flex justify-end gap-3">
                        <Link :href="`/goals/${goal.id}/edit`"
                            class="text-xs font-medium text-gray-500 hover:text-gray-900 dark:text-gray-400">Edit</Link>
                        <button type="button"
                            class="text-xs font-medium text-red-500 hover:text-red-700 dark:text-red-400"
                            @click="deleteGoal(goal.id, goal.name)">Delete</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty state -->
        <div v-if="goals.length === 0" class="rounded-xl border border-gray-200 bg-white p-16 text-center shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
            <p class="mt-4 text-base font-medium text-gray-900 dark:text-white">No goals yet</p>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Create your first savings goal to get started.</p>
            <Link href="/goals/create"
                class="mt-4 inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 transition-colors">
                New Goal
            </Link>
        </div>

    </div>
</template>