<script setup lang="ts">
import { computed } from 'vue';
import CityCombobox from '@/components/events/CityCombobox.vue';
import { Button } from '@/components/ui/button';
import type { EventFilters, LocationOption } from '@/types/events';

/**
 * Filter form for the event visualizer pages.
 *
 * Controlled component: the parent owns the filter object via `v-model`, so it's
 * always the single source of truth (handy for URL sync / sharing). Every field
 * change emits `update:modelValue`; `apply` signals the parent to fetch. Because
 * there's no internal copy of the filters, it can never drift from the parent.
 */
const props = defineProps<{
    modelValue: EventFilters;
    statuses: string[];
    types: string[];
    locations: LocationOption[];
}>();

const emit = defineEmits<{
    'update:modelValue': [filters: EventFilters];
    apply: [];
}>();

function update(patch: Partial<EventFilters>) {
    emit('update:modelValue', { ...props.modelValue, ...patch });
}

// Writable proxies so the template can use clean v-model while the parent stays
// the source of truth (each set emits an updated copy upward).
const location = computed({ get: () => props.modelValue.location, set: (value) => update({ location: value }) });
const from = computed({ get: () => props.modelValue.from, set: (value) => update({ from: value }) });
const to = computed({ get: () => props.modelValue.to, set: (value) => update({ to: value }) });
const type = computed({ get: () => props.modelValue.type, set: (value) => update({ type: value }) });
const status = computed({ get: () => props.modelValue.status, set: (value) => update({ status: value }) });

const hasActiveFilters = computed(() => Object.values(props.modelValue).some((value) => value !== ''));

function clearFilters() {
    emit('update:modelValue', { status: '', type: '', location: '', from: '', to: '' });
    emit('apply');
}
</script>

<template>
    <form
        class="flex flex-wrap items-end gap-3 rounded-xl border bg-card p-4 shadow-sm"
        @submit.prevent="emit('apply')"
    >
        <CityCombobox v-model="location" :options="locations" @change="emit('apply')" />

        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-muted-foreground" for="from">From</label>
            <input
                id="from"
                v-model="from"
                type="date"
                class="h-9 rounded-md border border-input bg-background px-3 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-ring"
            />
        </div>
        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-muted-foreground" for="to">To</label>
            <input
                id="to"
                v-model="to"
                type="date"
                class="h-9 rounded-md border border-input bg-background px-3 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-ring"
            />
        </div>
        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-muted-foreground" for="type">Type</label>
            <select
                id="type"
                v-model="type"
                class="h-9 rounded-md border border-input bg-background px-3 text-sm capitalize shadow-sm focus:outline-none focus:ring-2 focus:ring-ring"
            >
                <option value="">All types</option>
                <option v-for="option in types" :key="option" :value="option">{{ option }}</option>
            </select>
        </div>
        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-muted-foreground" for="status">Status</label>
            <select
                id="status"
                v-model="status"
                class="h-9 rounded-md border border-input bg-background px-3 text-sm capitalize shadow-sm focus:outline-none focus:ring-2 focus:ring-ring"
            >
                <option value="">All statuses</option>
                <option v-for="option in statuses" :key="option" :value="option">
                    {{ option.replace('_', ' ') }}
                </option>
            </select>
        </div>
        <Button type="submit">Apply</Button>
        <Button v-if="hasActiveFilters" type="button" variant="ghost" @click="clearFilters">Clear</Button>
    </form>
</template>
