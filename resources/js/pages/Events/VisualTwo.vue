<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import L from 'leaflet';
import { onBeforeUnmount, onMounted, ref } from 'vue';
import EventFilterBar from '@/components/events/EventFilterBar.vue';
import { fetchEventData, toQueryParams } from '@/lib/eventsApi';
import { eventTypePinColor } from '@/lib/eventType';
import type { EventFilters, LocationOption } from '@/types/events';
import 'leaflet/dist/leaflet.css';
import 'leaflet.markercluster';
import 'leaflet.markercluster/dist/MarkerCluster.css';
import 'leaflet.markercluster/dist/MarkerCluster.Default.css';
    
/*
 * Map for Event Visuals 2 — viewport loading. As the user pans/zooms, we fetch a
 * capped, lightweight marker set for the *current map bounds* from /events/data
 * (plus status/type/date filters); leaflet.markercluster clusters them. Picking a
 * city flies the camera there, which triggers a load of that area. The map bounds
 * are the source of truth for "which events", so the location filter is a camera
 * command, not a query param here.
 */
interface MapMarker {
    id: string;
    lat: number;
    lng: number;
    title: string;
    type: string;
    status: string;
    location_label: string | null;
    starts_at: number;
    price: number;
    image: string | null;
}

const { filters, statuses, types, locations } = defineProps<{
    filters: EventFilters;
    statuses: string[];
    types: string[];
    locations: LocationOption[];
}>();

const activeFilters = ref<EventFilters>({ ...filters });
const loading = ref(false);
const error = ref(false);
const total = ref<number | null>(null);
const shown = ref(0);

const mapEl = ref<HTMLElement | null>(null);
let map: L.Map | null = null;
let cluster: L.MarkerClusterGroup | null = null;
let moveTimer: ReturnType<typeof setTimeout> | null = null;
let lastLocation = activeFilters.value.location;

const dateFormatter = new Intl.DateTimeFormat(undefined, {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
});

function escapeHtml(value: string): string {
    return value.replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' })[c] ?? c);
}

function pinIcon(type: string): L.DivIcon {
    const color = eventTypePinColor(type);

    return L.divIcon({
        className: 'event-pin',
        html: `<span style="display:block;width:14px;height:14px;border-radius:9999px;background:${color};border:2px solid white;box-shadow:0 1px 3px rgba(0,0,0,.4)"></span>`,
        iconSize: [14, 14],
        iconAnchor: [7, 7],
        popupAnchor: [0, -8],
    });
}

function popupHtml(marker: MapMarker): string {
    const date = marker.starts_at ? dateFormatter.format(new Date(marker.starts_at * 1000)) : 'Date to be announced';
    const price = marker.price > 0 ? `$${marker.price.toFixed(2)}` : 'Free';
    const image = marker.image
        ? `<img src="${escapeHtml(marker.image)}" alt="" class="mb-2 h-24 w-full rounded-md object-cover" />`
        : '';

    return `
        <div class="w-52">
            ${image}
            <div class="text-sm font-semibold leading-tight">${escapeHtml(marker.title)}</div>
            <div class="mt-1 text-xs capitalize text-muted-foreground">${escapeHtml(marker.type)} · ${escapeHtml(marker.status.replace('_', ' '))}</div>
            <div class="text-xs text-muted-foreground">${escapeHtml(marker.location_label ?? 'Location unavailable')}</div>
            <div class="text-xs text-muted-foreground">${escapeHtml(date)} · ${price}</div>
            <a href="/events/${escapeHtml(marker.id)}" class="mt-1 inline-block text-xs font-medium text-primary hover:underline">View details →</a>
        </div>`;
}

function renderMarkers(markers: MapMarker[]) {
    if (!cluster) {
        return;
    }

    cluster.clearLayers();

    const layers = markers.map((marker) =>
        L.marker([marker.lat, marker.lng], { icon: pinIcon(marker.type) }).bindPopup(popupHtml(marker)),
    );

    cluster.addLayers(layers);
}

async function loadForCurrentView() {
    if (!map) {
        return;
    }

    loading.value = true;
    error.value = false;

    const bounds = map.getBounds();
    // Bounds drive "which events"; the location filter is only a camera command here.
    const filterParams = toQueryParams(activeFilters.value);
    delete filterParams.location;

    const params = {
        north: String(bounds.getNorth()),
        south: String(bounds.getSouth()),
        east: String(bounds.getEast()),
        west: String(bounds.getWest()),
        ...filterParams,
    };

    try {
        const payload = await fetchEventData(params);

        total.value = payload.total;
        shown.value = payload.data.length;
        renderMarkers(payload.data as MapMarker[]);
    } catch {
        error.value = true;
    } finally {
        loading.value = false;
    }
}

function flyToCity(slug: string) {
    const city = locations.find((option) => option.value === slug);

    if (city && map) {
        map.flyTo([city.lat, city.lng], 10, { duration: 1.2 });
    }
}

// Applied from the filter bar (Apply button, city select, clear).
function onApply() {
    const location = activeFilters.value.location;

    if (location !== lastLocation) {
        lastLocation = location;

        if (location) {
            flyToCity(location); // the resulting moveend loads the new area

            return;
        }
    }

    loadForCurrentView();
}

function onMoveEnd() {
    if (moveTimer) {
        clearTimeout(moveTimer);
    }

    moveTimer = setTimeout(loadForCurrentView, 300);
}

onMounted(() => {
    if (!mapEl.value) {
        return;
    }

    let center: L.LatLngExpression = [25, 0];
    let zoom = 2;
    const initial = locations.find((option) => option.value === activeFilters.value.location);

    if (initial) {
        center = [initial.lat, initial.lng];
        zoom = 10;
    }

    map = L.map(mapEl.value, { worldCopyJump: true }).setView(center, zoom);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 19,
    }).addTo(map);

    cluster = L.markerClusterGroup({ chunkedLoading: true, maxClusterRadius: 50 });
    map.addLayer(cluster);
    map.on('moveend', onMoveEnd);

    loadForCurrentView();
});

onBeforeUnmount(() => {
    if (moveTimer) {
        clearTimeout(moveTimer);
    }

    map?.remove();
    map = null;
    cluster = null;
});
</script>

<template>
    <Head title="Event Visuals — Map" />

    <div class="flex h-[calc(100svh-5rem)] flex-col gap-4 p-4 sm:p-6">
        <header class="flex shrink-0 flex-col gap-1">
            <h1 class="text-2xl font-semibold tracking-tight">Events map</h1>
            <p class="text-sm text-muted-foreground">
                <template v-if="total !== null">
                    Showing {{ shown.toLocaleString() }} of {{ total.toLocaleString() }} events in this area
                    <span v-if="total > shown"> · zoom in to see the rest</span>
                </template>
                <template v-else>Loading map…</template>
            </p>
        </header>

        <div class="shrink-0">
            <EventFilterBar
                v-model="activeFilters"
                :statuses="statuses"
                :types="types"
                :locations="locations"
                @apply="onApply"
            />
        </div>

        <div class="relative min-h-0 flex-1 overflow-hidden rounded-xl border shadow-sm">
            <div
                v-if="loading"
                class="pointer-events-none absolute inset-0 z-[1000] flex items-center justify-center"
            >
                <div class="flex items-center gap-2 rounded-full border bg-background/90 px-4 py-2 text-sm font-medium shadow-md backdrop-blur">
                    <span class="size-4 animate-spin rounded-full border-2 border-primary border-r-transparent" />
                    Scanning area…
                </div>
            </div>

            <div
                v-if="error && !loading"
                class="absolute left-1/2 top-3 z-[1000] flex -translate-x-1/2 items-center gap-3 rounded-full border bg-background/95 px-4 py-2 text-sm shadow-md backdrop-blur"
            >
                <span class="font-medium">Couldn't load this area.</span>
                <button type="button" class="font-medium text-primary hover:underline" @click="loadForCurrentView">
                    Retry
                </button>
            </div>

            <div ref="mapEl" class="z-0 h-full w-full bg-muted"></div>
        </div>
    </div>
</template>

<style>
/* Match Leaflet popups to the app theme (tokens are full hsl() colours). */
.leaflet-popup-content-wrapper {
    background-color: var(--popover);
    color: var(--popover-foreground);
    border-radius: 0.75rem;
}

.leaflet-popup-tip {
    background-color: var(--popover);
}

.leaflet-popup-close-button {
    color: var(--muted-foreground) !important;
    margin: 0.25rem 0.25rem 0 0 !important;
}

.leaflet-popup-close-button:hover {
    color: var(--foreground) !important;
}
</style>
