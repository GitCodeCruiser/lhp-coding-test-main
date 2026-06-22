<script setup lang="ts">
import { X } from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import type { LocationOption } from '@/types/events';

const props = defineProps<{
    modelValue: string;
    options: LocationOption[];
}>();

const emit = defineEmits<{
    'update:modelValue': [value: string];
    change: [];
}>();

const search = ref(props.options.find((option) => option.value === props.modelValue)?.label ?? '');
const open = ref(false);
const active = ref(-1);
const box = ref<HTMLElement | null>(null);

const filtered = computed<LocationOption[]>(() => {
    const query = search.value.trim().toLowerCase();
    const matches = query
        ? props.options.filter((o) => o.value.includes(query) || o.label.toLowerCase().includes(query))
        : props.options;

    return matches.slice(0, 8);
});

function select(option: LocationOption) {
    emit('update:modelValue', option.value);
    search.value = option.label;
    open.value = false;
    active.value = -1;
    emit('change');
}

function clear() {
    emit('update:modelValue', '');
    search.value = '';
    open.value = false;
    active.value = -1;
    emit('change');
}

function onInput() {
    open.value = true;
    active.value = -1;

    if (!search.value.trim()) {
        emit('update:modelValue', '');
    }
}

function onKeydown(event: KeyboardEvent) {
    const count = filtered.value.length;

    if (event.key === 'ArrowDown') {
        event.preventDefault();
        open.value = true;
        active.value = count ? (active.value + 1) % count : -1;
    } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        active.value = count ? (active.value - 1 + count) % count : -1;
    } else if (event.key === 'Enter') {
        event.preventDefault();
        const choice = filtered.value[active.value] ?? filtered.value[0];

        if (choice) {
            select(choice);
        }
    } else if (event.key === 'Escape') {
        open.value = false;
        active.value = -1;
    }
}

function onClickOutside(event: MouseEvent) {
    if (box.value && !box.value.contains(event.target as Node)) {
        open.value = false;
        active.value = -1;
    }
}

// Keep the visible text in sync when the parent resets the value (e.g. "Clear").
watch(
    () => props.modelValue,
    (value) => {
        search.value = value === '' ? '' : (props.options.find((o) => o.value === value)?.label ?? search.value);
    },
);

onMounted(() => window.addEventListener('click', onClickOutside));
onBeforeUnmount(() => window.removeEventListener('click', onClickOutside));
</script>

<template>
    <div ref="box" class="relative flex min-w-[200px] flex-col gap-1">
        <label class="text-xs font-medium text-muted-foreground" for="city">Location</label>
        <div class="relative flex items-center">
            <input
                id="city"
                v-model="search"
                type="text"
                placeholder="Search city…"
                autocomplete="off"
                role="combobox"
                aria-controls="city-listbox"
                :aria-expanded="open"
                :aria-activedescendant="active >= 0 ? `city-option-${active}` : undefined"
                class="h-9 w-full rounded-md border border-input bg-background pl-3 pr-8 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-ring"
                @focus="open = true"
                @input="onInput"
                @keydown="onKeydown"
            />
            <button
                v-if="search"
                type="button"
                aria-label="Clear location"
                class="absolute right-2.5 text-muted-foreground hover:text-foreground"
                @click="clear"
            >
                <X class="size-4" />
            </button>
        </div>
        <ul
            v-if="open && filtered.length > 0"
            id="city-listbox"
            role="listbox"
            class="absolute left-0 right-0 top-full z-50 mt-1 max-h-60 overflow-y-auto rounded-md border bg-popover py-1 text-popover-foreground shadow-md animate-in fade-in slide-in-from-top-1"
        >
            <li
                v-for="(option, index) in filtered"
                :id="`city-option-${index}`"
                :key="option.value"
                role="option"
                :aria-selected="active === index"
                class="cursor-pointer px-3 py-1.5 text-sm transition-colors"
                :class="
                    active === index
                        ? 'bg-accent text-accent-foreground'
                        : 'hover:bg-accent hover:text-accent-foreground'
                "
                @click="select(option)"
            >
                {{ option.label }}
            </li>
        </ul>
    </div>
</template>
