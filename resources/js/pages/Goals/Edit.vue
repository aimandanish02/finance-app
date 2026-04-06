<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';

interface GoalData {
    id: number;
    name: string;
    type: 'target_amount' | 'monthly_percentage';
    target_amount: number | null;
    target_percentage: number | null;
    current_savings: number;
    deadline: string | null;
    color: string;
    notes: string;
    is_completed: boolean;
}

const props = defineProps<{ goal: GoalData }>();

const form = useForm({
    name:               props.goal.name,
    type:               props.goal.type,
    target_amount:      props.goal.target_amount ?? '',
    target_percentage:  props.goal.target_percentage ?? '',
    current_savings:    props.goal.current_savings,
    deadline:           props.goal.deadline ?? '',
    color:              props.goal.color,
    notes:              props.goal.notes,
    is_completed:       props.goal.is_completed,
    _method:            'PUT',
});

const submit = () => {
    form.transform(data => ({
        ...data,
        target_amount:     data.type === 'target_amount' ? data.target_amount : null,
        target_percentage: data.type === 'monthly_percentage' ? data.target_percentage : null,
        deadline:          data.deadline || null,
    })).post(`/goals/${props.goal.id}`);
};
</script>

<template>
    <Head :title="`Edit: ${goal.name}`" />

    <div class="flex h-full flex-1 flex-col gap-6 p-6">
        <div class="mx-auto w-full max-w-lg">
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">

                <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Edit Goal</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ goal.name }}</p>
                </div>

                <form @submit.prevent="submit" class="p-6 space-y-5">

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            Goal Name <span class="text-red-500">*</span>
                        </label>
                        <input v-model="form.name" type="text"
                            class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"/>
                        <InputError :message="form.errors.name" class="mt-1" />
                    </div>

                    <!-- Type toggle -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Goal Type</label>
                        <div class="flex rounded-lg border border-gray-300 dark:border-gray-600 overflow-hidden">
                            <button type="button"
                                class="flex-1 py-2.5 text-sm font-medium transition-colors"
                                :class="form.type === 'target_amount' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 dark:bg-gray-800 dark:text-gray-300'"
                                @click="form.type = 'target_amount'">Target Amount</button>
                            <button type="button"
                                class="flex-1 py-2.5 text-sm font-medium transition-colors"
                                :class="form.type === 'monthly_percentage' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 dark:bg-gray-800 dark:text-gray-300'"
                                @click="form.type = 'monthly_percentage'">Monthly % of Income</button>
                        </div>
                    </div>

                    <div v-if="form.type === 'target_amount'">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            Target Amount (MYR) <span class="text-red-500">*</span>
                        </label>
                        <input v-model="form.target_amount" type="number" step="0.01" min="1"
                            class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"/>
                        <InputError :message="form.errors.target_amount" class="mt-1" />
                    </div>

                    <div v-if="form.type === 'monthly_percentage'">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            Save % of Monthly Income <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input v-model="form.target_percentage" type="number" step="1" min="1" max="100"
                                class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 pr-8 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"/>
                            <span class="absolute right-3 top-2 text-sm text-gray-400">%</span>
                        </div>
                        <InputError :message="form.errors.target_percentage" class="mt-1" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            Current Savings (MYR)
                        </label>
                        <input v-model="form.current_savings" type="number" step="0.01" min="0"
                            class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"/>
                        <InputError :message="form.errors.current_savings" class="mt-1" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Deadline</label>
                        <input v-model="form.deadline" type="date"
                            class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"/>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Colour</label>
                        <div class="flex items-center gap-3">
                            <input v-model="form.color" type="color"
                                class="h-9 w-12 cursor-pointer rounded border border-gray-300 p-0.5 dark:border-gray-600"/>
                            <span class="font-mono text-sm text-gray-500 dark:text-gray-400">{{ form.color }}</span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Notes</label>
                        <textarea v-model="form.notes" rows="2"
                            class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"/>
                    </div>

                    <label class="flex cursor-pointer items-center gap-2">
                        <input v-model="form.is_completed" type="checkbox"
                            class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"/>
                        <span class="text-sm text-gray-700 dark:text-gray-300">Mark as completed</span>
                    </label>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <a :href="`/goals/${goal.id}`"
                            class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">Cancel</a>
                        <button type="submit" :disabled="form.processing"
                            class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50 transition-colors">
                            {{ form.processing ? 'Saving...' : 'Save Changes' }}
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</template>