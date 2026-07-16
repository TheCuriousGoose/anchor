import { echo, echoIsConfigured } from '@laravel/echo-vue';

export function csrfToken(): string {
    return (
        document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
            ?.content ?? ''
    );
}

/**
 * The id of this tab's websocket connection, if there is one.
 *
 * Laravel reads this off the `X-Socket-ID` header to honour `->toOthers()`, which is what
 * stops the person making a change from receiving their own broadcast back and undoing
 * their optimistic update. Echo normally attaches this automatically, but only to axios —
 * this app talks to the API with `fetch`, so we attach it ourselves.
 *
 * Returns undefined for guests (Echo is never configured) and during the moment before
 * the socket connects; in both cases the request simply goes out without the header.
 */
export function socketId(): string | undefined {
    if (!echoIsConfigured()) {
        return undefined;
    }

    return echo().socketId();
}

export function apiHeaders(): Record<string, string> {
    const socket = socketId();

    return {
        Accept: 'application/json',
        'X-CSRF-TOKEN': csrfToken(),
        ...(socket ? { 'X-Socket-ID': socket } : {}),
    };
}

export async function request<T>(
    url: string,
    method: string,
    body?: object,
): Promise<T> {
    const response = await fetch(url, {
        method,
        headers: {
            ...apiHeaders(),
            'Content-Type': 'application/json',
        },
        body: body ? JSON.stringify(body) : undefined,
    });

    if (!response.ok) {
        throw new Error(`Request failed with status ${response.status}`);
    }

    return response.status === 204
        ? (undefined as T)
        : ((await response.json()) as T);
}
