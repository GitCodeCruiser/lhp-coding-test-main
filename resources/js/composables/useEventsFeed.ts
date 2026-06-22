import { computed, ref } from 'vue';
import type { Ref } from 'vue';
import { fetchEventData } from '@/lib/eventsApi';

/**
 * Paginated, append-style event feed shared by the card grid and the listing
 * table (both load `/events/data` page-by-page via infinite scroll).
 *
 * Owns the pagination/loading/error state so the pages don't re-implement it, and
 * — importantly — handles fetch failures: on error it flips `error`, which makes
 * `hasMore` false so an IntersectionObserver stops re-firing into a failing
 * endpoint. `retry()` clears the error and resumes from where it left off.
 */
export function useEventsFeed<T>() {
    const items = ref([]) as Ref<T[]>;
    const page = ref(0);
    const lastPage = ref<number | null>(null);
    const total = ref<number | null>(null);
    const loading = ref(false);
    const error = ref(false);
    const hasLoadedOnce = ref(false);
    const loadedBytes = ref(0);
    const loadedMs = ref(0);

    // Excludes `error` so a failed load halts the infinite-scroll loop until retried.
    const hasMore = computed(() => !error.value && (lastPage.value === null || page.value < lastPage.value));

    async function loadMore(params: Record<string, string>) {
        if (loading.value || !hasMore.value) {
            return;
        }

        loading.value = true;

        try {
            const payload = await fetchEventData({ ...params, page: String(page.value + 1) });

            items.value.push(...(payload.data as T[]));
            page.value = payload.current_page;
            lastPage.value = payload.last_page;
            total.value = payload.total;
            loadedBytes.value += payload.stats.bytes;
            loadedMs.value += payload.stats.ms;
            hasLoadedOnce.value = true;
        } catch {
            error.value = true;
        } finally {
            loading.value = false;
        }
    }

    /** Clear a previous failure and load the next page again. */
    function retry(params: Record<string, string>) {
        error.value = false;

        return loadMore(params);
    }

    /** Reset to an empty feed (e.g. when filters change). */
    function reset() {
        items.value = [];
        page.value = 0;
        lastPage.value = null;
        total.value = null;
        error.value = false;
        hasLoadedOnce.value = false;
        loadedBytes.value = 0;
        loadedMs.value = 0;
    }

    return {
        items,
        page,
        lastPage,
        total,
        loading,
        error,
        hasLoadedOnce,
        hasMore,
        loadedBytes,
        loadedMs,
        loadMore,
        retry,
        reset,
    };
}
