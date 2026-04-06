<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';

interface Category { id: number; name: string; code: string; color: string | null; }
interface Suggestion { id: number; name: string; code: string; color: string | null; deduction_type: string; }
interface ExpenseData {
    id: number; title: string; amount: string;
    type: 'income' | 'expense'; expense_date: string;
    description: string | null; notes: string | null; category_id: number;
}

const props = defineProps<{ expense: ExpenseData; categories: Category[] }>();

const form = useForm({
    title: props.expense.title,
    category_id: String(props.expense.category_id),
    amount: props.expense.amount,
    type: props.expense.type,
    expense_date: props.expense.expense_date,
    description: props.expense.description ?? '',
    notes: props.expense.notes ?? '',
    receipts: [] as File[],
    _method: 'PUT',
});

const submit = () => form.post(`/expenses/${props.expense.id}`, { forceFormData: true });

// ── Auto-categorize ────────────────────────────────────────────────────────
const suggestions    = ref<Suggestion[]>([]);
const suggestLoading = ref(false);
const suggestSource  = ref<'ai' | 'keywords' | null>(null);
let   suggestTimer: ReturnType<typeof setTimeout> | null = null;

const onTitleInput = () => {
    if (suggestTimer) clearTimeout(suggestTimer);
    suggestions.value = [];
    if (form.title.length < 3) return;
    suggestTimer = setTimeout(() => fetchSuggestions(), 600);
};

const fetchSuggestions = async () => {
    suggestLoading.value = true;
    try {
        const res = await fetch('/expenses/categorize', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '',
            },
            body: JSON.stringify({ title: form.title, description: form.description }),
        });
        if (res.ok) {
            const data = await res.json();
            suggestions.value  = data.suggestions ?? [];
            suggestSource.value = data.source ?? null;
        }
    } catch { /* silent */ } finally {
        suggestLoading.value = false;
    }
};

const applySuggestion = (s: Suggestion) => {
    form.category_id = String(s.id);
    suggestions.value = [];
};

// ── OCR state ──────────────────────────────────────────────────────────────
const ocrLoading = ref(false);
const ocrPanel   = ref(false);
const ocrRawText = ref('');
const ocrAmount  = ref<string | null>(null);
const ocrDate    = ref<string | null>(null);
const ocrError   = ref<string | null>(null);

const handleFileChange = async (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (!target.files?.length) return;
    form.receipts = Array.from(target.files);
    await runOcr(target.files[0]);
};

const runOcr = async (file: File) => {
    ocrLoading.value = true;
    ocrPanel.value   = false;
    ocrError.value   = null;
    ocrRawText.value = '';
    ocrAmount.value  = null;
    ocrDate.value    = null;

    const data = new FormData();
    data.append('receipt', file);

    try {
        const response = await fetch('/receipts/ocr', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '',
            },
            body: data,
        });

        const result = await response.json();

        if (result.success) {
            ocrRawText.value = result.raw_text ?? '';
            ocrAmount.value  = result.amount ?? null;
            ocrDate.value    = result.date ?? null;
            ocrPanel.value   = true;
        } else {
            ocrError.value = result.message ?? 'OCR failed.';
        }
    } catch {
        ocrError.value = 'Could not reach the OCR service. Fill in details manually.';
    } finally {
        ocrLoading.value = false;
    }
};

const applyOcrValues = () => {
    if (ocrAmount.value) form.amount       = ocrAmount.value;
    if (ocrDate.value)   form.expense_date = ocrDate.value;
    ocrPanel.value = false;
};
</script>

<template>
    <Head :title="`Edit: ${expense.title}`" />

    <div class="flex h-full flex-1 flex-col gap-6 p-6">
        <div class="mx-auto w-full max-w-2xl">
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">

                <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Edit Expense</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Update the details below.</p>
                </div>

                <form @submit.prevent="submit" class="p-6 space-y-5" enctype="multipart/form-data">

                    <!-- Add more receipts + OCR -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            Add More Receipts
                            <span class="ml-1 text-xs font-normal text-gray-400">(scan to auto-fill amount & date)</span>
                        </label>

                        <div class="rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-600 px-6 py-6 text-center">
                            <label for="receipts" class="cursor-pointer text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">
                                Click to select files
                            </label>
                            <p class="mt-1 text-xs text-gray-400">JPG, PNG, WEBP, HEIC, PDF up to 10MB</p>
                            <input
                                id="receipts" type="file" multiple
                                accept="image/*,application/pdf"
                                class="sr-only"
                                @change="handleFileChange"
                            />
                        </div>

                        <ul v-if="form.receipts.length > 0" class="mt-2 space-y-1">
                            <li v-for="(file, i) in form.receipts" :key="i" class="text-sm text-gray-600 dark:text-gray-400">
                                {{ file.name }}
                            </li>
                        </ul>

                        <div v-if="ocrLoading" class="mt-3 flex items-center gap-2 text-sm text-indigo-600 dark:text-indigo-400">
                            <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 12 3 12 12h4a8 8 0 01-8 8z" />
                            </svg>
                            Scanning receipt…
                        </div>

                        <p v-if="ocrError" class="mt-2 text-xs text-amber-600 dark:text-amber-400">⚠ {{ ocrError }}</p>

                        <!-- OCR preview panel -->
                        <div v-if="ocrPanel" class="mt-3 rounded-lg border border-indigo-200 bg-indigo-50 p-4 dark:border-indigo-800 dark:bg-indigo-900/20">
                            <div class="flex items-start justify-between gap-2 mb-3">
                                <p class="text-sm font-medium text-indigo-900 dark:text-indigo-300">Receipt scanned — review detected values</p>
                                <button type="button" @click="ocrPanel = false" class="text-indigo-400 hover:text-indigo-600 flex-shrink-0">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <div class="grid grid-cols-2 gap-3 mb-3">
                                <div class="rounded-md bg-white p-3 dark:bg-gray-800 border border-indigo-100 dark:border-indigo-800">
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Detected Amount</p>
                                    <p v-if="ocrAmount" class="text-base font-bold text-gray-900 dark:text-white">MYR {{ ocrAmount }}</p>
                                    <p v-else class="text-sm text-gray-400 italic">Not detected</p>
                                </div>
                                <div class="rounded-md bg-white p-3 dark:bg-gray-800 border border-indigo-100 dark:border-indigo-800">
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Detected Date</p>
                                    <p v-if="ocrDate" class="text-base font-bold text-gray-900 dark:text-white">{{ ocrDate }}</p>
                                    <p v-else class="text-sm text-gray-400 italic">Not detected</p>
                                </div>
                            </div>

                            <details class="mb-3">
                                <summary class="cursor-pointer text-xs text-indigo-600 dark:text-indigo-400 hover:underline">View raw extracted text</summary>
                                <pre class="mt-2 max-h-32 overflow-y-auto rounded bg-white dark:bg-gray-800 p-2 text-xs text-gray-600 dark:text-gray-400 whitespace-pre-wrap border border-indigo-100 dark:border-indigo-800">{{ ocrRawText }}</pre>
                            </details>

                            <div class="flex gap-2">
                                <button type="button"
                                    class="flex-1 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700 transition-colors disabled:opacity-50"
                                    :disabled="!ocrAmount && !ocrDate"
                                    @click="applyOcrValues"
                                >Use these values</button>
                                <button type="button"
                                    class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300"
                                    @click="ocrPanel = false"
                                >Fill manually</button>
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-gray-200 dark:border-gray-700" />

                    <!-- Type toggle -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Type</label>
                        <div class="flex rounded-lg border border-gray-300 dark:border-gray-600 overflow-hidden">
                            <button type="button"
                                class="flex-1 py-2 text-sm font-medium transition-colors"
                                :class="form.type === 'expense' ? 'bg-red-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300'"
                                @click="form.type = 'expense'"
                            >Expense</button>
                            <button type="button"
                                class="flex-1 py-2 text-sm font-medium transition-colors"
                                :class="form.type === 'income' ? 'bg-green-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300'"
                                @click="form.type = 'income'"
                            >Income</button>
                        </div>
                    </div>

                    <!-- Title -->
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            Title <span class="text-red-500">*</span>
                        </label>
                        <input id="title" v-model="form.title" type="text"
                            class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            @input="onTitleInput"
                        />
                        <div v-if="suggestLoading" class="mt-2 text-xs text-gray-400">Suggesting categories…</div>
                        <div v-if="suggestions.length > 0" class="mt-2">
                            <p class="text-xs text-gray-400 mb-1.5">
                                {{ suggestSource === 'ai' ? '✨ AI suggestion' : '💡 Suggested category' }} — click to apply:
                            </p>
                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="s in suggestions" :key="s.code"
                                    type="button"
                                    class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-medium text-white transition-opacity hover:opacity-80"
                                    :style="{ backgroundColor: s.color ?? '#6366f1' }"
                                    @click="applySuggestion(s)"
                                >{{ s.name }}</button>
                            </div>
                        </div>
                        <InputError :message="form.errors.title" class="mt-1" />
                    </div>

                    <!-- Amount + Date -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="amount" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                Amount (MYR) <span class="text-red-500">*</span>
                            </label>
                            <input id="amount" v-model="form.amount" type="number" step="0.01" min="0"
                                class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            />
                            <InputError :message="form.errors.amount" class="mt-1" />
                        </div>
                        <div>
                            <label for="expense_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                Date <span class="text-red-500">*</span>
                            </label>
                            <input id="expense_date" v-model="form.expense_date" type="date"
                                class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            />
                            <InputError :message="form.errors.expense_date" class="mt-1" />
                        </div>
                    </div>

                    <!-- Category -->
                    <div>
                        <label for="category_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            Category <span class="text-red-500">*</span>
                        </label>
                        <select id="category_id" v-model="form.category_id"
                            class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        >
                            <option value="" disabled>Select a category</option>
                            <option v-for="category in categories" :key="category.id" :value="String(category.id)">
                                {{ category.name }}
                            </option>
                        </select>
                        <InputError :message="form.errors.category_id" class="mt-1" />
                    </div>

                    <!-- Description -->
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Description</label>
                        <input id="description" v-model="form.description" type="text"
                            class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        />
                        <InputError :message="form.errors.description" class="mt-1" />
                    </div>

                    <!-- Notes -->
                    <div>
                        <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Notes</label>
                        <textarea id="notes" v-model="form.notes" rows="3"
                            class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        />
                        <InputError :message="form.errors.notes" class="mt-1" />
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-end gap-3 pt-2">
                        <a :href="`/expenses/${expense.id}`"
                            class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                        >Cancel</a>
                        <button type="submit" :disabled="form.processing"
                            class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                        >
                            <svg v-if="form.processing" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 12 3 12 12h4a8 8 0 01-8 8z" />
                            </svg>
                            {{ form.processing ? 'Saving...' : 'Update Expense' }}
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</template>