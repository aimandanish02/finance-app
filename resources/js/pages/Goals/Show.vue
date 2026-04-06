<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';

interface SavingsPoint {
    label: string; month: string; net: number;
    income: number; spent: number; target_monthly: number | null;
}

interface GoalDetail {
    id: number; name: string;
    type: 'target_amount' | 'monthly_percentage';
    target_amount: number | null; target_percentage: number | null;
    target_monthly: number | null; current_savings: number;
    deadline: string | null; color: string; notes: string | null;
    is_completed: boolean; pct: number;
    days_remaining: number | null; months_to_goal: number | null;
}

const props = defineProps<{
    goal: GoalDetail;
    savingsHistory: SavingsPoint[];
    monthlyIncome: number;
    currentMonth: string;
}>();

const fmt = (v: number) =>
    v.toLocaleString('en-MY', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const fmtDate = (d: string) =>
    new Date(d + 'T00:00:00').toLocaleDateString('en-MY', { year: 'numeric', month: 'long', day: 'numeric' });

const BAR_H = 100;
const chartMax = computed(() => {
    const vals = props.savingsHistory.flatMap(p => [Math.abs(p.net), p.target_monthly ?? 0]);
    return Math.max(...vals, 1);
});
const barHeight = (v: number) => Math.max(4, Math.round((Math.abs(v) / chartMax.value) * BAR_H));

const deleteGoal = () => {
    if (confirm(`Delete goal "${props.goal.name}"? This cannot be undone.`)) {
        router.delete(`/goals/${props.goal.id}`);
    }
};
</script>

<template>
    <Head :title="goal.name" />

    <div class="flex h-full flex-1 flex-col gap-6 p-6">
        <div class="mx-auto w-full max-w-2xl space-y-5">

            <!-- Main card -->
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">

                <!-- Header -->
                <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                    <div class="flex items-center gap-3">
                        <span class="h-4 w-4 rounded-full" :style="{ backgroundColor: goal.color }"/>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ goal.name }}</h2>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-800/30 dark:text-indigo-400">
                            {{ goal.type === 'target_amount' ? 'Target Amount' : 'Monthly %' }}
                        </span>
                        <span v-if="goal.is_completed"
                            class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800/30 dark:text-green-400">
                            Completed
                        </span>
                    </div>
                </div>

                <!-- Progress -->
                <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-baseline justify-between mb-2">
                        <span class="text-3xl font-bold text-gray-900 dark:text-white">{{ goal.pct }}%</span>
                        <span class="text-sm text-gray-500 dark:text-gray-400">
                            {{ goal.type === 'target_amount'
                                ? `MYR ${fmt(goal.current_savings)} of MYR ${fmt(goal.target_amount!)}`
                                : `${goal.target_percentage}% of monthly income` }}
                        </span>
                    </div>
                    <div class="h-3 w-full rounded-full bg-gray-200 dark:bg-gray-700">
                        <div
                            class="h-3 rounded-full transition-all duration-700"
                            :style="{ width: `${goal.pct}%`, backgroundColor: goal.color }"
                        />
                    </div>
                </div>

                <!-- Details -->
                <dl class="divide-y divide-gray-200 dark:divide-gray-700">
                    <div v-if="goal.type === 'target_amount'" class="flex justify-between px-6 py-3">
                        <dt class="text-sm text-gray-500 dark:text-gray-400">Current savings</dt>
                        <dd class="text-sm font-medium text-gray-900 dark:text-white">MYR {{ fmt(goal.current_savings) }}</dd>
                    </div>
                    <div v-if="goal.type === 'target_amount'" class="flex justify-between px-6 py-3">
                        <dt class="text-sm text-gray-500 dark:text-gray-400">Still needed</dt>
                        <dd class="text-sm font-medium text-gray-900 dark:text-white">
                            MYR {{ fmt(Math.max(0, goal.target_amount! - goal.current_savings)) }}
                        </dd>
                    </div>
                    <div v-if="goal.target_monthly" class="flex justify-between px-6 py-3">
                        <dt class="text-sm text-gray-500 dark:text-gray-400">Monthly target</dt>
                        <dd class="text-sm font-medium text-gray-900 dark:text-white">MYR {{ fmt(goal.target_monthly) }}</dd>
                    </div>
                    <div v-if="goal.months_to_goal !== null" class="flex justify-between px-6 py-3">
                        <dt class="text-sm text-gray-500 dark:text-gray-400">Estimated time</dt>
                        <dd class="text-sm font-medium text-gray-900 dark:text-white">
                            ~{{ goal.months_to_goal }} month{{ goal.months_to_goal !== 1 ? 's' : '' }}
                        </dd>
                    </div>
                    <div v-if="goal.deadline" class="flex justify-between px-6 py-3">
                        <dt class="text-sm text-gray-500 dark:text-gray-400">Deadline</dt>
                        <dd class="text-sm font-medium text-gray-900 dark:text-white">
                            {{ fmtDate(goal.deadline) }}
                            <span v-if="goal.days_remaining !== null" class="ml-1 text-gray-400">({{ goal.days_remaining }}d)</span>
                        </dd>
                    </div>
                    <div v-if="goal.notes" class="px-6 py-3">
                        <dt class="text-sm text-gray-500 dark:text-gray-400 mb-1">Notes</dt>
                        <dd class="text-sm text-gray-700 dark:text-gray-300">{{ goal.notes }}</dd>
                    </div>
                </dl>
            </div>

            <!-- Monthly net savings history -->
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-1">Monthly Net Savings — Last 6 Months</h3>
                <p class="text-xs text-gray-400 mb-6">Income minus expenses per month</p>

                <div class="flex items-end justify-between gap-2" :style="`height: ${BAR_H + 4}px`">
                    <div v-for="point in savingsHistory" :key="point.month" class="flex flex-1 flex-col items-center">
                        <div class="flex w-full items-end justify-center gap-1" :style="`height: ${BAR_H}px`">
                            <!-- Target monthly line -->
                            <div v-if="point.target_monthly"
                                class="w-1 rounded-t-sm bg-gray-300 dark:bg-gray-600"
                                :style="`height: ${barHeight(point.target_monthly)}px`"
                                :title="`Target: MYR ${fmt(point.target_monthly)}`"/>
                            <!-- Net savings bar -->
                            <div
                                class="flex-1 rounded-t-sm transition-all duration-500"
                                :class="point.net >= 0 ? 'bg-green-400' : 'bg-red-400'"
                                :style="`height: ${barHeight(point.net)}px`"
                                :title="`Net: MYR ${fmt(point.net)}`"/>
                        </div>
                    </div>
                </div>
                <div class="mt-2 flex justify-between gap-2">
                    <div v-for="point in savingsHistory" :key="point.month + '-l'"
                        class="flex-1 text-center text-[10px] text-gray-400">
                        {{ point.label.slice(0, 3) }}
                    </div>
                </div>
                <div class="mt-3 flex items-center justify-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                    <span class="flex items-center gap-1.5"><span class="inline-block h-2.5 w-2.5 rounded-sm bg-green-400"/>Net positive</span>
                    <span class="flex items-center gap-1.5"><span class="inline-block h-2.5 w-2.5 rounded-sm bg-red-400"/>Net negative</span>
                    <span v-if="goal.type === 'monthly_percentage'" class="flex items-center gap-1.5">
                        <span class="inline-block h-2.5 w-1 rounded-sm bg-gray-300 dark:bg-gray-600"/>Target
                    </span>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-between">
                <Link href="/goals" class="text-sm font-medium text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">
                    ← Back to Goals
                </Link>
                <div class="flex gap-3">
                    <Link :href="`/goals/${goal.id}/edit`"
                        class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">Edit</Link>
                    <button type="button"
                        class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 transition-colors"
                        @click="deleteGoal">Delete</button>
                </div>
            </div>

        </div>
    </div>
</template>