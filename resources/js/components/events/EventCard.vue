<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight, CalendarDays, ChevronLeft, ChevronRight, ImageIcon, MapPin, Ticket } from '@lucide/vue';
import { computed, ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { formatEventTime } from '@/lib/datetime';
import { eventTypeBadgeClass } from '@/lib/eventType';
import type { EventImage, RawEvent } from '@/types/events';

const props = defineProps<{ event: RawEvent }>();

const carouselIndex = ref(0);
const failedImageIds = ref<number[]>([]);

const activeImage = computed<EventImage | null>(() => {
    if (props.event.images.length === 0) {
        return null;
    }

    return props.event.images[carouselIndex.value % props.event.images.length] ?? null;
});

const title = computed(() => props.event.payload?.name ?? `${props.event.type} event`);
const description = computed(() => props.event.payload?.description ?? 'No description provided yet.');
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

const dateText = computed(() => formatEventTime(props.event.payload?.schedule?.starts_at ?? props.event.created_time));

const locationText = computed(() => {
    const venue = props.event.payload?.venue?.name;

    return [props.event.location_label, venue].filter(Boolean).join(' · ') || 'Location unavailable';
});

const priceText = computed(() => {
    const price = Number(props.event.payload?.pricing?.min_price ?? 0);

    return price > 0 ? `$${price.toFixed(2)}` : 'Free';
});

function step(delta: number) {
    const count = props.event.images.length;

    if (count > 0) {
        carouselIndex.value = (carouselIndex.value + delta + count) % count;
    }
}
</script>

<template>
    <article
        class="group flex flex-col overflow-hidden rounded-xl border bg-card shadow-sm transition-all duration-300 animate-in fade-in slide-in-from-bottom-3 hover:-translate-y-1 hover:shadow-lg"
    >
        <!-- Image carousel -->
        <div class="relative aspect-[16/10] overflow-hidden bg-muted">
            <template v-if="activeImage">
                <img
                    v-if="!failedImageIds.includes(activeImage.id)"
                    :src="activeImage.path"
                    :alt="activeImage.alt ?? title"
                    loading="lazy"
                    class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                    @error="failedImageIds.push(activeImage.id)"
                />
                <div v-else class="flex h-full items-center justify-center text-muted-foreground">
                    <ImageIcon class="size-8 opacity-40" />
                </div>

                <div
                    v-if="event.images.length > 1"
                    class="absolute inset-0 flex items-center justify-between px-2 opacity-0 transition-opacity duration-200 group-hover:opacity-100"
                >
                    <button
                        type="button"
                        aria-label="Previous image"
                        class="flex size-8 items-center justify-center rounded-full bg-background/80 text-foreground shadow backdrop-blur transition hover:bg-background"
                        @click.prevent.stop="step(-1)"
                    >
                        <ChevronLeft class="size-4" />
                    </button>
                    <button
                        type="button"
                        aria-label="Next image"
                        class="flex size-8 items-center justify-center rounded-full bg-background/80 text-foreground shadow backdrop-blur transition hover:bg-background"
                        @click.prevent.stop="step(1)"
                    >
                        <ChevronRight class="size-4" />
                    </button>
                </div>

                <div
                    v-if="event.images.length > 1"
                    class="absolute bottom-2 left-1/2 flex -translate-x-1/2 gap-1"
                >
                    <span
                        v-for="(image, i) in event.images"
                        :key="image.id"
                        class="h-1.5 rounded-full bg-white/90 shadow transition-all"
                        :class="carouselIndex % event.images.length === i ? 'w-4' : 'w-1.5 bg-white/50'"
                    />
                </div>
            </template>

            <div v-else class="flex h-full items-center justify-center text-muted-foreground">
                <ImageIcon class="size-8 opacity-40" />
            </div>

            <Badge :variant="statusVariant" class="absolute left-3 top-3 capitalize shadow">
                {{ event.status.replace('_', ' ') }}
            </Badge>
        </div>

        <!-- Content -->
        <div class="flex flex-1 flex-col gap-2 p-4">
            <span class="w-fit rounded-full px-2.5 py-0.5 text-xs font-medium capitalize" :class="typeClass">
                {{ event.type }}
            </span>
            <Link :href="`/events/${event.id}`" class="line-clamp-1 font-semibold leading-tight hover:underline">
                {{ title }}
            </Link>
            <p class="line-clamp-2 text-sm text-muted-foreground">{{ description }}</p>

            <div class="mt-auto flex flex-col gap-1.5 pt-2 text-sm text-muted-foreground">
                <span class="flex items-center gap-1.5">
                    <MapPin class="size-4 shrink-0" />
                    <span class="line-clamp-1">{{ locationText }}</span>
                </span>
                <span class="flex items-center gap-1.5">
                    <CalendarDays class="size-4 shrink-0" />
                    <span class="line-clamp-1">{{ dateText }}</span>
                </span>
            </div>

            <div class="mt-2 flex items-center justify-between border-t pt-3">
                <span class="flex items-center gap-1.5 font-semibold">
                    <Ticket class="size-4 text-muted-foreground" /> {{ priceText }}
                </span>
                <Link
                    :href="`/events/${event.id}`"
                    class="inline-flex items-center gap-1 text-sm font-medium text-primary hover:underline"
                >
                    Details <ArrowRight class="size-3.5" />
                </Link>
            </div>
        </div>
    </article>
</template>
