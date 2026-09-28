export type ChatUserRef = { public_id: string; name: string };

export type Conversation = {
    public_id: string;
    other: (ChatUserRef & { role: string; active: boolean }) | null;
    student: ChatUserRef | null;
    last_message: {
        preview: string;
        is_mine: boolean;
        created_at: string;
    } | null;
    last_message_at: string | null;
    unread: number;
    archived: boolean;
    muted: boolean;
    /** Hanya ada pada detail percakapan. */
    can_send?: boolean;
};

export type Attachment = {
    public_id: string;
    name: string;
    mime_type: string;
    is_image: boolean;
    size: number;
    width: number | null;
    height: number | null;
    url: string;
};

/** Pesan dari server (payload broadcast tidak punya is_mine/read/can). */
export type ChatMessage = {
    public_id: string;
    type: 'TEXT' | 'IMAGE' | 'FILE';
    body: string | null;
    deleted: boolean;
    sender: ChatUserRef | null;
    attachment: Attachment | null;
    created_at: string;
    edited_at: string | null;
    is_mine?: boolean;
    read?: boolean;
    can?: { edit: boolean; delete: boolean };
};

/** Pesan di layar: bisa masih dikirim (optimistic) atau gagal. */
export type UiMessage = ChatMessage & {
    client_id?: string;
    status?: 'sending' | 'failed';
    error?: string;
    /** Berkas lokal untuk dikirim ulang. */
    file?: File | null;
};

export type Contact = {
    user: string;
    name: string;
    role: string;
    context: string | null;
    student: ChatUserRef | null;
};

export type ContactGroup = {
    key: string;
    title: string;
    contacts: Contact[];
    hint?: string;
};

export type MessagePage = { data: ChatMessage[]; next_cursor: string | null };

export type SentEvent = { conversation: string; message: ChatMessage };

export type ReadEvent = {
    conversation: string;
    reader: string;
    last_read_message: string;
    read_at: string;
};
