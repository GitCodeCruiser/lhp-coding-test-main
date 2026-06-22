/**
 * Shared TypeScript interfaces for the Events visualizer pages.
 * Imported by VisualOne, VisualTwo, and the event-related components.
 */

export interface EventImage {
    id: number;
    path: string;
    alt: string | null;
    sort_order: number;
    is_primary: boolean;
}

/** A raw event row as returned by /events/data (plus a server-computed location_label). */
export interface RawEvent {
    id: string;
    type: string;
    status: string;
    created_time: number | null;
    latitude: number | null;
    longitude: number | null;
    location_label?: string | null;
    payload: {
        name?: string;
        description?: string;
        venue?: { name?: string };
        schedule?: { starts_at?: number | string; ends_at?: number | string };
        pricing?: { currency?: string; min_price?: number | string };
    };
    images: EventImage[];
}

export interface LocationOption {
    value: string;
    label: string;
    region: string;
    lat: number;
    lng: number;
}

export interface EventFilters {
    status: string;
    type: string;
    location: string;
    from: string;
    to: string;
}
