<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, CalendarDays, MapPin, Ticket, Users } from '@lucide/vue';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { eventTypeBadgeClass } from '@/lib/eventType';
import type { EventImage, RawEvent } from '@/types/events';

interface DetailEvent extends RawEvent {
    user?: { id: number; name: string } | null;
    attendees_count?: number;
    payload: RawEvent['payload'] & {
        category?: string;
        organizer?: { name?: string; verified?: boolean };
        venue?: { name?: string; capacity?: number | string };
        pricing?: { currency?: string; min_price?: number | string };
    };
}

const props = defineProps<{ event: DetailEvent }>();

// Register-interest form. Timezone is captured silently from the browser so the
// confirmation/reminder emails can be phrased in the attendee's own zone.
const form = useForm({
    name: '',
    email: '',
    timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
});

function register() {
    form.post(`/events/${props.event.id}/register`, {
        preserveScroll: true,
        // The success toast is raised globally by the flash → toast bridge;
        // here we just clear the inputs for the next person.
        onSuccess: () => form.reset('name', 'email'),
    });
}

const attendeeCount = computed(() => props.event.attendees_count ?? 0);
// Registration is closed for cancelled / sold-out events (also enforced server-side).
const registrationClosed = computed(() => ['cancelled', 'sold_out'].includes(props.event.status));

// Primary image first, then up to two more, for the mosaic gallery.
const gallery = computed<EventImage[]>(() =>
    [...props.event.images].sort((a, b) => Number(b.is_primary) - Number(a.is_primary)).slice(0, 3),
);
const hero = computed(() => gallery.value[0] ?? null);
const secondaries = computed(() => gallery.value.slice(1));

const title = computed(() => props.event.payload?.name ?? `${props.event.type} event`);
const description = computed(() => props.event.payload?.description ?? 'No description provided yet.');
const venue = computed(() => props.event.payload?.venue?.name ?? null);
const organizer = computed(() => props.event.payload?.organizer?.name ?? null);
const capacity = computed(() => {
    const value = props.event.payload?.venue?.capacity;

    return value != null ? Number(value).toLocaleString() : null;
});

const price = computed(() => {
    const value = Number(props.event.payload?.pricing?.min_price ?? 0);

    return value > 0 ? `$${value.toFixed(2)}` : 'Free';
});

const coordinates = computed(() => {
    if (props.event.latitude == null || props.event.longitude == null) {
        return null;
    }

    return `${props.event.latitude.toFixed(4)}, ${props.event.longitude.toFixed(4)}`;
});

const localTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone;

const dateTimeFormatter = new Intl.DateTimeFormat(undefined, {
    weekday: 'short',
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
    timeZoneName: 'short',
});

const timeFormatter = new Intl.DateTimeFormat(undefined, {
    hour: 'numeric',
    minute: '2-digit',
    timeZoneName: 'short',
});

const when = computed(() => {
    const startRaw = props.event.payload?.schedule?.starts_at ?? props.event.created_time;
    const start = startRaw != null ? Number(startRaw) : null;

    if (!start) {
        return 'Date to be announced';
    }

    const startDate = new Date(start * 1000);
    const endRaw = props.event.payload?.schedule?.ends_at;
    const end = endRaw != null ? Number(endRaw) : null;

    if (!end) {
        return dateTimeFormatter.format(startDate);
    }

    const endDate = new Date(end * 1000);
    const sameDay = startDate.toDateString() === endDate.toDateString();

    return `${dateTimeFormatter.format(startDate)} – ${sameDay ? timeFormatter.format(endDate) : dateTimeFormatter.format(endDate)}`;
});

const typeClass = computed(() => eventTypeBadgeClass(props.event.type));

const statusVariant = computed(() => {
    switch (props.event.status) {
        case 'published':
            return 'default';
        case 'cancelled':
            return 'destructive';
        case 'sold_out':
            return 'secondary';
        default:
            return 'outline';
    }
});
</script>

<template>
    <Head :title="title" />

    <div class="mx-auto flex w-full max-w-5xl flex-col gap-6 p-4 sm:p-6">
        <Link href="/events-visual-1" class="inline-flex w-fit items-center gap-1.5 text-sm text-muted-foreground hover:text-foreground">
            <ArrowLeft class="size-4" /> Back to events
        </Link>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Main column: gallery + title + description -->
            <div class="flex flex-col gap-4 lg:col-span-2">
                <div class="flex flex-col gap-3 sm:flex-row">
                    <div class="overflow-hidden rounded-xl border bg-muted sm:flex-1">
                        <div class="aspect-video w-full">
                            <img
                                v-if="hero"
                                :src="hero.path"
                                :alt="hero.alt ?? title"
                                class="h-full w-full object-cover transition-transform duration-300 hover:scale-105"
                            />
                        </div>
                    </div>
                    <div v-if="secondaries.length" class="grid grid-cols-2 gap-3 sm:flex sm:w-44 sm:flex-col">
                        <div
                            v-for="image in secondaries"
                            :key="image.id"
                            class="aspect-video overflow-hidden rounded-xl border bg-muted sm:aspect-auto sm:flex-1"
                        >
                            <img
                                :src="image.path"
                                :alt="image.alt ?? ''"
                                class="h-full w-full object-cover transition-transform duration-300 hover:scale-105"
                            />
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <span class="rounded-full px-2.5 py-0.5 text-xs font-medium capitalize" :class="typeClass">
                        {{ event.type }}
                    </span>
                    <Badge :variant="statusVariant" class="capitalize">{{ event.status.replace('_', ' ') }}</Badge>
                </div>

                <h1 class="text-2xl font-semibold tracking-tight sm:text-3xl">{{ title }}</h1>

                <p class="leading-relaxed text-muted-foreground">{{ description }}</p>
            </div>

            <!-- Sidebar: key facts + registration -->
            <div class="flex flex-col gap-6">
                <aside class="flex h-fit flex-col gap-4 rounded-xl border bg-card p-5 shadow-sm">
                    <div class="flex items-start gap-3">
                        <CalendarDays class="mt-0.5 size-5 shrink-0 text-muted-foreground" />
                        <div>
                            <div class="text-sm font-medium">{{ when }}</div>
                            <div class="text-xs text-muted-foreground">Times shown in {{ localTimezone }}</div>
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                        <MapPin class="mt-0.5 size-5 shrink-0 text-muted-foreground" />
                        <div class="text-sm">
                            <div class="font-medium">{{ event.location_label ?? 'Location unavailable' }}</div>
                            <div v-if="venue" class="text-muted-foreground">{{ venue }}</div>
                            <div v-if="coordinates" class="text-xs text-muted-foreground">{{ coordinates }}</div>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <Ticket class="size-5 shrink-0 text-muted-foreground" />
                        <div class="text-sm font-medium">{{ price }}</div>
                    </div>

                    <div v-if="organizer || capacity" class="flex items-start gap-3 border-t pt-4">
                        <Users class="mt-0.5 size-5 shrink-0 text-muted-foreground" />
                        <div class="text-sm">
                            <div v-if="organizer" class="font-medium">{{ organizer }}</div>
                            <div v-if="capacity" class="text-xs text-muted-foreground">Capacity: {{ capacity }}</div>
                        </div>
                    </div>
                </aside>

                <!-- Register interest -->
                <form class="flex h-fit flex-col gap-4 rounded-xl border bg-card p-5 shadow-sm" @submit.prevent="register">
                    <div>
                        <h2 class="text-base font-semibold">Register your interest</h2>
                        <p class="text-sm text-muted-foreground">
                            We'll email you a confirmation, then reminders 3 days and 24 hours before.
                        </p>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <Label for="attendee-name">Name</Label>
                        <Input id="attendee-name" v-model="form.name" type="text" autocomplete="name" required :disabled="form.processing || registrationClosed" :aria-invalid="!!form.errors.name" />
                        <p v-if="form.errors.name" class="text-xs text-destructive">{{ form.errors.name }}</p>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <Label for="attendee-email">Email</Label>
                        <Input id="attendee-email" v-model="form.email" type="email" autocomplete="email" required :disabled="form.processing || registrationClosed" :aria-invalid="!!form.errors.email" />
                        <p v-if="form.errors.email" class="text-xs text-destructive">{{ form.errors.email }}</p>
                    </div>

                    <Button type="submit" :disabled="form.processing || registrationClosed">
                        {{ form.processing ? 'Registering…' : 'Register' }}
                    </Button>

                    <p v-if="registrationClosed" class="text-center text-xs text-muted-foreground">
                        Registration is closed for this event.
                    </p>
                    <p v-else-if="attendeeCount" class="text-center text-xs text-muted-foreground">
                        {{ attendeeCount.toLocaleString() }} {{ attendeeCount === 1 ? 'person' : 'people' }} attending
                    </p>
                </form>
            </div>
        </div>
    </div>
</template>
