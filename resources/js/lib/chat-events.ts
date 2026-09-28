/**
 * Event DOM chat lintas komponen (tanpa store global), dipancarkan RealtimeBridge
 * dan halaman chat.
 */

/** Payload event `chat.message` di channel pribadi user (ChatMessageReceived). */
export type ChatMessageNotice = {
    conversation: string;
    message: string;
    sender: string | null;
    preview: string;
    muted: boolean;
    unread_total: number;
};

/** Pesan baru untuk user ini (detail: ChatMessageNotice). */
export const CHAT_MESSAGE_EVENT = 'skoolia:chat-message';

/** Total unread chat berubah (detail: number). */
export const CHAT_UNREAD_EVENT = 'skoolia:chat-unread';

export function emitChatUnread(total: number): void {
    window.dispatchEvent(
        new CustomEvent<number>(CHAT_UNREAD_EVENT, { detail: total }),
    );
}

/** Percakapan yang sedang terbuka di layar (untuk menekan toast). */
let openConversation: string | null = null;

export function setOpenConversation(publicId: string | null): void {
    openConversation = publicId;
}

export function isConversationOpen(publicId: string): boolean {
    return (
        openConversation === publicId && document.visibilityState === 'visible'
    );
}
