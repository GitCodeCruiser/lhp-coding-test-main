/**
 * Shared presentation for the 8 event types — single source of truth for the
 * type badge colour (cards / detail page) and the map-pin colour (Visual 2),
 * so the three consumers can't drift apart.
 */

const BADGE_CLASSES: Record<string, string> = {
    concert: 'bg-purple-100 text-purple-700 dark:bg-purple-500/15 dark:text-purple-300',
    conference: 'bg-blue-100 text-blue-700 dark:bg-blue-500/15 dark:text-blue-300',
    meetup: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300',
    workshop: 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300',
    festival: 'bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-300',
    sports: 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-300',
    networking: 'bg-cyan-100 text-cyan-700 dark:bg-cyan-500/15 dark:text-cyan-300',
    exhibition: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-300',
};

const PIN_COLORS: Record<string, string> = {
    concert: '#a855f7',
    conference: '#3b82f6',
    meetup: '#10b981',
    workshop: '#f59e0b',
    festival: '#f43f5e',
    sports: '#ef4444',
    networking: '#06b6d4',
    exhibition: '#6366f1',
};

const FALLBACK_PIN = '#6366f1';

/** Tailwind classes for a type badge; neutral fallback for unknown types. */
export function eventTypeBadgeClass(type: string): string {
    return BADGE_CLASSES[type] ?? 'bg-muted text-muted-foreground';
}

/** Hex colour for a map pin; fallback for unknown types. */
export function eventTypePinColor(type: string): string {
    return PIN_COLORS[type] ?? FALLBACK_PIN;
}
