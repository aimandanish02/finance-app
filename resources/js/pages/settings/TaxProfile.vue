<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';

interface Child {
    type: 'u18' | 'predegree' | 'degree';
    disabled: boolean;
}

interface ReliefItem {
    label: string;
    amount: number;
}

interface Props {
    profile: {
        has_spouse: boolean;
        spouse_disabled: boolean;
        self_disabled: boolean;
        children: Child[];
        has_parents_medical: boolean;
        has_disabled_equipment: boolean;
    };
    reliefs: {
        items: ReliefItem[];
        total: number;
    };
}

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Settings', href: '/settings/profile' },
            { title: 'Tax profile', href: '/settings/tax-profile' },
        ],
    },
});

const form = useForm({
    has_spouse:             props.profile.has_spouse,
    spouse_disabled:        props.profile.spouse_disabled,
    self_disabled:          props.profile.self_disabled,
    children:               props.profile.children as Child[],
    has_parents_medical:    props.profile.has_parents_medical,
    has_disabled_equipment: props.profile.has_disabled_equipment,
});

// Live relief preview — mirrors the backend logic
const liveReliefs = computed(() => {
    const items: ReliefItem[] = [];
    items.push({ label: 'Self relief', amount: 9000 });
    if (form.self_disabled) items.push({ label: 'Disabled self (additional)', amount: 7000 });
    if (form.has_spouse) {
        items.push({ label: 'Spouse relief', amount: 4000 });
        if (form.spouse_disabled) items.push({ label: 'Disabled spouse (additional)', amount: 6000 });
    }
    form.children.forEach((child, i) => {
        const n = i + 1;
        const amount = child.type === 'degree' ? 8000 : 2000;
        const label  = child.type === 'u18' ? `Child ${n} (below 18)`
            : child.type === 'predegree' ? `Child ${n} (18+, pre-degree)`
            : `Child ${n} (18+, diploma or higher)`;
        items.push({ label, amount });
        if (child.disabled) items.push({ label: `Child ${n} — disabled`, amount: 8000 });
    });
    if (form.has_parents_medical)    items.push({ label: 'Medical expenses for parents', amount: 8000 });
    if (form.has_disabled_equipment) items.push({ label: 'Disability supporting equipment', amount: 6000 });
    return {
        items,
        total: items.reduce((s, i) => s + i.amount, 0),
    };
});

const fmt = (v: number) => v.toLocaleString('en-MY');

const addChild = () => {
    form.children.push({ type: 'u18', disabled: false });
};

const removeChild = (index: number) => {
    form.children.splice(index, 1);
};

const submit = () => form.put('/settings/tax-profile');
</script>

<template>
    <Head title="Tax profile" />

    <div class="flex flex-col space-y-6">

        <Heading
            variant="small"
            title="Tax profile"
            description="Set up your personal reliefs for accurate LHDN tax summary"
        />

        <form @submit.prevent="submit" class="space-y-8">

            <!-- Self -->
            <div class="space-y-4">
                <h3 class="text-sm font-medium text-foreground">Personal</h3>
                <div class="flex items-center justify-between rounded-lg border p-4">
                    <div>
                        <Label>Disabled individual</Label>
                        <p class="text-xs text-muted-foreground mt-0.5">You are registered as disabled with JKM — additional RM7,000 relief</p>
                    </div>
                    <button
                        type="button"
                        class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 focus:outline-none"
                        :class="form.self_disabled ? 'bg-primary' : 'bg-muted'"
                        @click="form.self_disabled = !form.self_disabled"
                    >
                        <span
                            class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition duration-200"
                            :class="form.self_disabled ? 'translate-x-5' : 'translate-x-0'"
                        />
                    </button>
                </div>
            </div>

            <Separator />

            <!-- Spouse -->
            <div class="space-y-4">
                <h3 class="text-sm font-medium text-foreground">Spouse</h3>
                <div class="flex items-center justify-between rounded-lg border p-4">
                    <div>
                        <Label>Spouse with no income / joint assessment</Label>
                        <p class="text-xs text-muted-foreground mt-0.5">RM4,000 spouse relief</p>
                    </div>
                    <button
                        type="button"
                        class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 focus:outline-none"
                        :class="form.has_spouse ? 'bg-primary' : 'bg-muted'"
                        @click="form.has_spouse = !form.has_spouse"
                    >
                        <span class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition duration-200"
                            :class="form.has_spouse ? 'translate-x-5' : 'translate-x-0'" />
                    </button>
                </div>
                <div v-if="form.has_spouse" class="ml-4 flex items-center justify-between rounded-lg border p-4">
                    <div>
                        <Label>Spouse is disabled</Label>
                        <p class="text-xs text-muted-foreground mt-0.5">Additional RM6,000 disabled spouse relief</p>
                    </div>
                    <button
                        type="button"
                        class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 focus:outline-none"
                        :class="form.spouse_disabled ? 'bg-primary' : 'bg-muted'"
                        @click="form.spouse_disabled = !form.spouse_disabled"
                    >
                        <span class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition duration-200"
                            :class="form.spouse_disabled ? 'translate-x-5' : 'translate-x-0'" />
                    </button>
                </div>
            </div>

            <Separator />

            <!-- Children -->
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-medium text-foreground">Children</h3>
                    <Button type="button" variant="outline" size="sm" @click="addChild">
                        + Add child
                    </Button>
                </div>

                <div v-if="form.children.length === 0" class="rounded-lg border border-dashed p-6 text-center">
                    <p class="text-sm text-muted-foreground">No children added yet.</p>
                    <p class="text-xs text-muted-foreground mt-1">RM2,000 (below 18 or pre-degree) or RM8,000 (diploma or higher) per child.</p>
                </div>

                <div
                    v-for="(child, index) in form.children"
                    :key="index"
                    class="rounded-lg border p-4 space-y-4"
                >
                    <div class="flex items-center justify-between">
                        <Label class="text-sm font-medium">Child {{ index + 1 }}</Label>
                        <button
                            type="button"
                            class="text-xs text-destructive hover:underline"
                            @click="removeChild(index)"
                        >Remove</button>
                    </div>

                    <div>
                        <Label class="text-xs text-muted-foreground mb-2 block">Education status</Label>
                        <div class="flex rounded-lg border overflow-hidden">
                            <button
                                v-for="opt in [
                                    { value: 'u18', label: 'Below 18' },
                                    { value: 'predegree', label: '18+, pre-degree' },
                                    { value: 'degree', label: '18+, degree' },
                                ]"
                                :key="opt.value"
                                type="button"
                                class="flex-1 py-2 text-xs font-medium transition-colors"
                                :class="child.type === opt.value
                                    ? 'bg-primary text-primary-foreground'
                                    : 'bg-background text-muted-foreground hover:bg-muted'"
                                @click="child.type = opt.value as Child['type']"
                            >{{ opt.label }}</button>
                        </div>
                        <p class="mt-1 text-xs text-muted-foreground">
                            Relief: RM{{ child.type === 'degree' ? '8,000' : '2,000' }}
                        </p>
                    </div>

                    <div class="flex items-center justify-between">
                        <div>
                            <Label class="text-xs">Child is disabled</Label>
                            <p class="text-xs text-muted-foreground">Additional RM8,000</p>
                        </div>
                        <button
                            type="button"
                            class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 focus:outline-none"
                            :class="child.disabled ? 'bg-primary' : 'bg-muted'"
                            @click="child.disabled = !child.disabled"
                        >
                            <span class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition duration-200"
                                :class="child.disabled ? 'translate-x-5' : 'translate-x-0'" />
                        </button>
                    </div>
                </div>
                <InputError :message="form.errors.children" />
            </div>

            <Separator />

            <!-- Parents & Other -->
            <div class="space-y-4">
                <h3 class="text-sm font-medium text-foreground">Parents & dependents</h3>

                <div class="flex items-center justify-between rounded-lg border p-4">
                    <div>
                        <Label>Medical expenses for parents</Label>
                        <p class="text-xs text-muted-foreground mt-0.5">RM8,000 relief for parents' medical, dental, nursing care</p>
                    </div>
                    <button
                        type="button"
                        class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 focus:outline-none"
                        :class="form.has_parents_medical ? 'bg-primary' : 'bg-muted'"
                        @click="form.has_parents_medical = !form.has_parents_medical"
                    >
                        <span class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition duration-200"
                            :class="form.has_parents_medical ? 'translate-x-5' : 'translate-x-0'" />
                    </button>
                </div>

                <div class="flex items-center justify-between rounded-lg border p-4">
                    <div>
                        <Label>Disability supporting equipment</Label>
                        <p class="text-xs text-muted-foreground mt-0.5">RM6,000 for wheelchair, hearing aid, prosthetics, etc.</p>
                    </div>
                    <button
                        type="button"
                        class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 focus:outline-none"
                        :class="form.has_disabled_equipment ? 'bg-primary' : 'bg-muted'"
                        @click="form.has_disabled_equipment = !form.has_disabled_equipment"
                    >
                        <span class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition duration-200"
                            :class="form.has_disabled_equipment ? 'translate-x-5' : 'translate-x-0'" />
                    </button>
                </div>
            </div>

            <Separator />

            <!-- Live relief preview -->
            <div class="rounded-lg border bg-muted/40 p-4 space-y-2">
                <p class="text-sm font-medium text-foreground">Relief preview</p>
                <p class="text-xs text-muted-foreground">Updates as you change settings above</p>
                <div class="mt-3 space-y-1">
                    <div
                        v-for="item in liveReliefs.items"
                        :key="item.label"
                        class="flex justify-between text-sm"
                    >
                        <span class="text-muted-foreground">{{ item.label }}</span>
                        <span class="font-medium tabular-nums">RM {{ fmt(item.amount) }}</span>
                    </div>
                </div>
                <Separator class="my-2" />
                <div class="flex justify-between text-sm font-semibold">
                    <span>Total personal reliefs</span>
                    <span class="text-primary">RM {{ fmt(liveReliefs.total) }}</span>
                </div>
            </div>

            <!-- Save -->
            <div class="flex items-center gap-4">
                <Button type="submit" :disabled="form.processing">
                    {{ form.processing ? 'Saving…' : 'Save' }}
                </Button>
                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p v-show="form.recentlySuccessful" class="text-sm text-neutral-600">Saved.</p>
                </Transition>
            </div>

        </form>
    </div>
</template>