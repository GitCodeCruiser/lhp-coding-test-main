<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { onBeforeUnmount, onMounted, ref } from 'vue';
import EventCard from '@/components/events/EventCard.vue';
import EventFilterBar from '@/components/events/EventFilterBar.vue';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import { useEventsFeed } from '@/composables/useEventsFeed';
import { toQueryParams } from '@/lib/eventsApi';
import type { EventFilters, LocationOption, RawEvent } from '@/types/events';

/*
 * Card grid for Event Visuals 1. Reads /events/data (raw events + eager-loaded
 * images + a server-computed nearest-city `location_label`). The filter bar owns
 * the inputs and emits the applied filters; this page owns the feed (infinite
 * scroll), delegating fetch/pagination/error handling to useEventsFeed.
 */
const { filters, statuses, types, locations } = defineProps<{
    filters: EventFilters;
    statuses: string[];
    types: string[];
    locations: LocationOption[];
}>();

const activeFilters = ref<EventFilters>({ ...filters });

const { items: events, total, loading, error, hasLoadedOnce, hasMore, loadMore, retry, reset } = useEventsFeed<RawEvent>();

const sentinel = ref<HTMLElement | null>(null);
let observer: IntersectionObserver | null = null;

const localTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone;

function load() {
    loadMore(toQueryParams(activeFilters.value));
}

function loadFresh() {
    reset();
    load();
}

function retryLoad() {
    retry(toQueryParams(activeFilters.value));
}

onMounted(() => {
    observer = new IntersectionObserver(
        (entries) => {
            if (entries[0]?.isIntersecting) {
                load();
            }
        },
        { rootMargin: '600px' },
    );

    if (sentinel.value) {
        observer.observe(sentinel.value);
    }

    load();
});

onBeforeUnmount(() => observer?.disconnect());
</script>

<template>
    <Head title="Event Visuals — Cards" />

    <div class="flex flex-col gap-6 p-4 sm:p-6">
        <header class="flex flex-col gap-1">
            <h1 class="text-2xl font-semibold tracking-tight">Browse events</h1>
            <p class="text-sm text-muted-foreground">
                {{ total !== null ? `${total.toLocaleString()} events` : 'Loading events…' }}
                · times shown in {{ localTimezone }}
            </p>
        </header>

        <EventFilterBar
            v-model="activeFilters"
            :statuses="statuses"
            :types="types"
            :locations="locations"
            @apply="loadFresh"
        />

        <!-- Card grid -->
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            <EventCard v-for="event in events" :key="event.id" :event="event" />

            <!-- Loading skeletons -->
            <template v-if="loading">
                <div
                    v-for="n in 8"
                    :key="`skeleton-${n}`"
                    class="flex flex-col overflow-hidden rounded-xl border bg-card shadow-sm"
                >
                    <Skeleton class="aspect-[16/10] w-full rounded-none" />
                    <div class="flex flex-col gap-2 p-4">
                        <Skeleton class="h-4 w-16" />
                        <Skeleton class="h-4 w-3/4" />
                        <Skeleton class="h-3 w-full" />
                        <Skeleton class="h-3 w-2/3" />
                    </div>
                </div>
            </template>
        </div>

        <!-- Error state -->
        <div
            v-if="error && !loading"
            class="flex flex-col items-center justify-center gap-2 rounded-xl border border-dashed p-12 text-center"
        >
            <p class="font-medium">Couldn't load events.</p>
            <p class="text-sm text-muted-foreground">Something went wrong fetching this page.</p>
            <Button class="mt-2" variant="outline" @click="retryLoad">Try again</Button>
        </div>

        <!-- Empty state -->
        <div
            v-else-if="hasLoadedOnce && !loading && events.length === 0"
            class="flex flex-col items-center justify-center gap-2 rounded-xl border border-dashed p-12 text-center"
        >
            <p class="font-medium">No events found.</p>
            <p class="text-sm text-muted-foreground">Try a different filter.</p>
        </div>

        <div ref="sentinel" class="h-px"></div>

        <p v-if="!hasMore && !error && events.length > 0" class="py-4 text-center text-sm text-muted-foreground">
            You've reached the end.
        </p>
    </div>
</template>
