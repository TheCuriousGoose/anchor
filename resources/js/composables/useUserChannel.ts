import { router, usePage } from '@inertiajs/vue3';
import { useEcho } from '@laravel/echo-vue';
import { useI18n } from 'vue-i18n';
import { toast } from 'vue-sonner';

type GrantedPayload = {
    board: { id: string; name: string; icon: string };
    role: 'editor' | 'viewer';
    sharedBy: string;
};

type ChangedPayload = { boardId: string; role: 'editor' | 'viewer' };

type RevokedPayload = { boardId: string; boardName: string };

function refreshSidebar(): void {
    router.reload({ only: ['sidebarBoards'] });
}

/** True when this tab is currently looking at the given board. */
function isViewing(boardId: string): boolean {
    return window.location.pathname === `/boards/${boardId}`;
}

/**
 * Listens on the signed-in user's own channel for sharing changes, so the sidebar reflects
 * boards being shared with (or taken away from) them without a refresh.
 *
 * Mounted once from the app sidebar, which is present on every authenticated page.
 */
export function useUserChannel(): void {
    const page = usePage();
    const { t } = useI18n();
    const userId = page.props.auth?.user?.id;

    if (!userId) {
        return;
    }

    const channelName = `App.Models.User.${userId}`;

    useEcho<GrantedPayload>(channelName, '.board.access.granted', (payload) => {
        refreshSidebar();
        toast.success(
            t('realtime.boardSharedWithYou', {
                name: payload.board.name,
                user: payload.sharedBy,
            }),
        );
    });

    useEcho<ChangedPayload>(channelName, '.board.access.changed', (payload) => {
        refreshSidebar();

        // Permissions changed underneath them; only a reload re-renders the board with the
        // right affordances, so do it while they're actually looking at it.
        if (isViewing(payload.boardId)) {
            router.reload();
            toast.info(t(`realtime.roleChangedTo.${payload.role}`));
        }
    });

    useEcho<RevokedPayload>(channelName, '.board.access.revoked', (payload) => {
        refreshSidebar();

        if (isViewing(payload.boardId)) {
            router.visit('/boards');
        }

        toast.info(
            t('realtime.boardAccessRevoked', { name: payload.boardName }),
        );
    });

    useEcho(channelName, '.boards.changed', () => {
        refreshSidebar();
    });
}
