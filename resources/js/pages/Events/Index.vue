<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useEventsFeed } from '@/composables/useEventsFeed';
import { toQueryParams } from '@/lib/eventsApi';

interface EventRow {
    id: string;
    type: string;
    status: string;
    created_time: number | null;
    user: { id: number; name: string } | null;
}

const props = defineProps<{
    filters: { status: string | null; from: string };
    statuses: string[];
}>();

const form = reactive({
    status: props.filters.status ?? '',
    from: props.filters.from ?? '',
});

const { items: rows, total, loading, error, hasLoadedOnce, loadedBytes, loadedMs, loadMore, retry, reset } =
    useEventsFeed<EventRow>();

const sentinel = ref<HTMLElement | null>(null);
let observer: IntersectionObserver | null = null;

const loadedSize = computed(() => {
    const kb = loadedBytes.value / 1024;

    return kb < 1024 ? `${kb.toFixed(1)} KB` : `${(kb / 1024).toFixed(2)} MB`;
});

const loadedSeconds = computed(() => (loadedMs.value / 1000).toFixed(1));

function load() {
    loadMore(toQueryParams(form));
}

function applyFilters() {
    reset();
    load();
}

function retryLoad() {
    retry(toQueryParams(form));
}

const statusVariant = (status: string) => {
    switch (status) {
        case 'published':
            return 'default';
        case 'cancelled':
            return 'destructive';
        case 'sold_out':
            return 'secondary';
        default:
            return 'outline';
    }
};

onMounted(() => {
    observer = new IntersectionObserver(
        (entries) => {
            if (entries[0]?.isIntersecting) {
                load();
            }
        },
        { rootMargin: '400px' },
    );

    if (sentinel.value) {
        observer.observe(sentinel.value);
    }

    load();
});

onBeforeUnmount(() => observer?.disconnect());
</script>

<template>
    <Head title="Events" />

    <div class="flex flex-col gap-4 p-4">
        <div>
            <h1 class="text-xl font-semibold">Events</h1>
            <p class="text-sm text-muted-foreground">
                {{ total !== null ? `${total.toLocaleString()} total events` : '—' }}
            </p>
        </div>

        <form class="flex flex-wrap items-end gap-3" @submit.prevent="applyFilters">
            <div class="flex flex-col gap-1">
                <label class="text-xs text-muted-foreground" for="status">Status</label>
                <select
                    id="status"
                    v-model="form.status"
                    class="h-9 rounded-md border border-input bg-background px-3 text-sm"
                >
                    <option value="">All</option>
                    <option v-for="s in statuses" :key="s" :value="s">{{ s }}</option>
                </select>
            </div>
            <div class="flex flex-col gap-1">
                <label class="text-xs text-muted-foreground" for="from">From</label>
                <input
                    id="from"
                    v-model="form.from"
                    type="date"
                    class="h-9 rounded-md border border-input bg-background px-3 text-sm"
                />
            </div>
            <Button type="submit">Filter</Button>
        </form>

        <div class="overflow-x-auto rounded-lg border">
            <table class="w-full text-sm">
                <thead class="border-b bg-muted/50 text-left">
                    <tr>
                        <th class="px-3 py-2 font-medium">ID</th>
                        <th class="px-3 py-2 font-medium">Type</th>
                        <th class="px-3 py-2 font-medium">Status</th>
                        <th class="px-3 py-2 font-medium">User</th>
                        <th class="px-3 py-2 font-medium">Time</th>
                        <th class="px-3 py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="event in rows" :key="event.id" class="border-b last:border-0">
                        <td class="px-3 py-2 font-mono text-xs">{{ event.id }}</td>
                        <td class="px-3 py-2">{{ event.type }}</td>
                        <td class="px-3 py-2">
                            <Badge :variant="statusVariant(event.status)">{{ event.status }}</Badge>
                        </td>
                        <td class="px-3 py-2">{{ event.user?.name ?? '—' }}</td>
                        <td class="px-3 py-2 font-mono text-xs">{{ event.created_time }}</td>
                        <td class="px-3 py-2 text-right">
                            <Link :href="`/events/${event.id}`" class="text-primary hover:underline">View</Link>
                        </td>
                    </tr>
                    <tr v-if="error && !loading">
                        <td colspan="6" class="px-3 py-8 text-center">
                            <p class="text-muted-foreground">Couldn't load events.</p>
                            <Button class="mt-2" variant="outline" size="sm" @click="retryLoad">Try again</Button>
                        </td>
                    </tr>
                    <tr v-else-if="!loading && hasLoadedOnce && rows.length === 0">
                        <td colspan="6" class="px-3 py-8 text-center text-muted-foreground">No events found.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div ref="sentinel"></div>

        <div class="py-2 text-sm text-gray-400">
            <span v-if="loading">loading...</span>
            <span v-else-if="hasLoadedOnce && !error">Loaded {{ loadedSize }} in {{ loadedSeconds }}s</span>
        </div>
    </div>
</template>
