import { router } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import type { FlashToast } from '@/types/ui';

/**
 * Bridges server-side flash messages to vue-sonner toasts.
 *
 * Controllers redirect back with `->with('toast', ['type' => ..., 'message' => ...])`,
 * which HandleInertiaRequests shares as `flash.toast`. On every successful Inertia
 * visit we read that off the freshly-loaded page props and raise the matching toast.
 */
export function initializeFlashToast(): void {
    router.on('success', (event) => {
        const flash = (event.detail.page.props as { flash?: { toast?: FlashToast | null } }).flash;
        const data = flash?.toast;

        if (data) {
            toast[data.type](data.message);
        }
    });
}
