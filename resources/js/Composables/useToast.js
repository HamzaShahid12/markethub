import { useUiStore } from '@/Stores/ui';

export function useToast() {
    const ui = useUiStore();

    return {
        success: (message) => ui.toast(message, 'success'),
        error: (message) => ui.toast(message, 'error'),
        info: (message) => ui.toast(message, 'info'),
    };
}
