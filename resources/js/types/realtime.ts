export type RealtimeConfig = {
    key: string;
    cluster: string;
    /** Channel privat notifikasi milik user, mis. "App.Models.User.01J…". */
    userChannel: string;
};

export type RealtimeStatus =
    'disabled' | 'connecting' | 'connected' | 'unavailable';
