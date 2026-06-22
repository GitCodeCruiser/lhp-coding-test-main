/**
 * Thin client for the shared `/events/data` endpoint, used by both visual pages
 * and the listing. Centralises the fetch + HTTP-error check so every caller fails
 * the same way (and the pages don't each re-implement it).
 */

/** The envelope `/events/data` always returns (list mode and map mode). */
export interface EventDataPayload {
    data: unknown[];
    total: number;
    current_page: number;
    last_page: number;
    stats: { ms: number; bytes: number };
}

export async function fetchEventData(params: Record<string, string>): Promise<EventDataPayload> {
    const response = await fetch(`/events/data?${new URLSearchParams(params).toString()}`, {
        headers: { Accept: 'application/json' },
    });

    if (!response.ok) {
        throw new Error(`Failed to load events (HTTP ${response.status})`);
    }

    return response.json() as Promise<EventDataPayload>;
}

/** Build a query-param object from a filters object, dropping empty/nullish values. */
export function toQueryParams(filters: object): Record<string, string> {
    return Object.fromEntries(
        Object.entries(filters)
            .filter(([, value]) => value !== '' && value != null)
            .map(([key, value]) => [key, String(value)]),
    );
}
