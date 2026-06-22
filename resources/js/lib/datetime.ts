/**
 * Event date/time formatting in the viewer's local timezone.
 *
 * The formatter is created once at module scope (not per component instance), so
 * a grid of hundreds of cards shares a single `Intl.DateTimeFormat`. Note:
 * `dateStyle`/`timeStyle` can't be combined with `timeZoneName`, so explicit
 * component options are used to keep the zone label.
 */
const viewerDateTime = new Intl.DateTimeFormat(undefined, {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
    timeZoneName: 'short',
});

/** Format a unix-second timestamp (number or numeric string) in the viewer's zone. */
export function formatEventTime(unix: number | string | null | undefined): string {
    const seconds = unix != null ? Number(unix) : null;

    return seconds ? viewerDateTime.format(new Date(seconds * 1000)) : 'Date to be announced';
}
