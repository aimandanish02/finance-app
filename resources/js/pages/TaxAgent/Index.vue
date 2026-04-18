<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';

interface Share {
    id: number; year: number; label: string; token: string; url: string;
    expires_at: string | null; is_expired: boolean;
    last_accessed_at: string | null; access_count: number; created_at: string;
}

const props = defineProps<{
    shares: Share[];
    availableYears: number[];
}>();

const fmt = (d: string | null) => d ?? 'Never';
const copied = ref<string | null>(null);

const copyUrl = async (url: string, token: string) => {
    await navigator.clipboard.writeText(url);
    copied.value = token;
    setTimeout(() => { copied.value = null; }, 2000);
};

const deleteShare = (id: number) => {
    if (confirm('Delete this share link? Your tax agent will no longer be able to access it.')) {
        router.delete(`/tax-agent/${id}`);
    }
};

const form = useForm({
    year:       props.availableYears[0] ?? new Date().getFullYear(),
    label:      '',
    expires_in: '30',
});

const createLink = () => form.post('/tax-agent', {
    onSuccess: () => { form.reset('label'); },
});
</script>

<template>
    <Head title="Tax Agent Sharing" />

    <div class="flex h-full flex-1 flex-col gap-6 p-6">

        <div>
            <h1 class="text-xl font-semibold text-gray-900 dark:text-white">Tax Agent Sharing</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Generate secure read-only links to share your deduction summary with your accountant or tax agent.
            </p>
        </div>

        <!-- Create form -->
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">Create new share link</h2>

            <form @submit.prevent="createLink" class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">Year</label>
                    <select v-model="form.year"
                        class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option v-for="y in availableYears" :key="y" :value="y">YA{{ y }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">Label (optional)</label>
                    <input v-model="form.label" type="text" placeholder="e.g. For Mr Rashid"
                        class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm placeholder-gray-400 dark:border-gray-600 dark:bg-gray-700 dark:text-white"/>
                    <InputError :message="form.errors.label" class="mt-1"/>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">Expires</label>
                    <select v-model="form.expires_in"
                        class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="7">7 days</option>
                        <option value="30">30 days</option>
                        <option value="90">90 days</option>
                        <option value="">Never</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="submit" :disabled="form.processing"
                        class="w-full rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50 transition-colors">
                        {{ form.processing ? 'Creating...' : 'Create link' }}
                    </button>
                </div>
            </form>
        </div>

        <!-- Existing links -->
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Active share links</h2>
            </div>

            <div v-if="shares.length > 0" class="divide-y divide-gray-200 dark:divide-gray-700">
                <div v-for="share in shares" :key="share.id" class="px-6 py-4">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ share.label }}</p>
                                <span class="text-xs rounded-full px-2 py-0.5 font-medium"
                                    :class="share.is_expired
                                        ? 'bg-red-100 text-red-800 dark:bg-red-800/30 dark:text-red-400'
                                        : 'bg-green-100 text-green-800 dark:bg-green-800/30 dark:text-green-400'">
                                    {{ share.is_expired ? 'Expired' : 'Active' }}
                                </span>
                                <span class="text-xs rounded-full px-2 py-0.5 bg-indigo-100 text-indigo-800 dark:bg-indigo-800/30 dark:text-indigo-400 font-medium">
                                    YA{{ share.year }}
                                </span>
                            </div>
                            <div class="mt-1.5 flex items-center gap-2">
                                <code class="text-xs text-gray-400 bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded truncate max-w-xs">
                                    {{ share.url }}
                                </code>
                                <button type="button"
                                    class="text-xs text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 flex-shrink-0 font-medium"
                                    @click="copyUrl(share.url, share.token)">
                                    {{ copied === share.token ? 'Copied ✓' : 'Copy' }}
                                </button>
                            </div>
                            <p class="mt-1 text-xs text-gray-400">
                                Created {{ share.created_at }}
                                <span v-if="share.expires_at"> · Expires {{ share.expires_at }}</span>
                                <span v-else> · No expiry</span>
                                · Accessed {{ share.access_count }}×
                                <span v-if="share.last_accessed_at"> (last: {{ share.last_accessed_at }})</span>
                            </p>
                        </div>
                        <div class="flex items-center gap-3 flex-shrink-0">
                            <a :href="share.url" target="_blank"
                                class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">
                                Preview
                            </a>
                            <button type="button"
                                class="text-sm text-red-600 hover:text-red-800 dark:text-red-400"
                                @click="deleteShare(share.id)">
                                Delete
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else class="px-6 py-16 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                </svg>
                <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">No share links yet. Create one above to share with your tax agent.</p>
            </div>
        </div>

        <!-- Security note -->
        <div class="rounded-xl border border-gray-200 bg-gray-50 px-5 py-4 dark:border-gray-700 dark:bg-gray-800/50">
            <p class="text-xs text-gray-500 dark:text-gray-400">
                Share links give read-only access to your deduction summary for the selected year. No personal login details are shared.
                Anyone with the link can view the report — set an expiry date for time-sensitive sharing.
                Delete the link at any time to immediately revoke access.
            </p>
        </div>

    </div>
</template>