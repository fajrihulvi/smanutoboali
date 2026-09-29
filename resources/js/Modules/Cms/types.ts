import type { StatusVariant } from '@/types';

export type ContentStatusValue = 'DRAFT' | 'REVIEW' | 'PUBLISHED' | 'ARCHIVED';

export type ContentStatus = {
    value: ContentStatusValue;
    label: string;
    scheduled: boolean;
};

export type ImageRef = { public_id: string; url: string; thumb_url: string };

export type PageRow = {
    public_id: string;
    type: { value: string; label: string };
    is_system: boolean;
    slug: string;
    title: string;
    content: string;
    excerpt: string | null;
    seo_title: string | null;
    seo_description: string | null;
    status: ContentStatus;
    publish_at: string | null;
    published_at: string | null;
    show_in_menu: boolean;
    position: number;
    image: ImageRef | null;
    updated_at: string;
};

export type PostRow = {
    public_id: string;
    type: 'BERITA' | 'AGENDA';
    slug: string;
    title: string;
    category: { public_id: string; name: string } | null;
    excerpt: string | null;
    content: string;
    seo_title: string | null;
    seo_description: string | null;
    status: ContentStatus;
    publish_at: string | null;
    published_at: string | null;
    is_featured: boolean;
    event_starts_at: string | null;
    event_ends_at: string | null;
    event_location: string | null;
    image: ImageRef | null;
    author: string | null;
    updated_at: string;
};

export type PostTypeInfo = {
    value: 'BERITA' | 'AGENDA';
    label: string;
    segment: 'berita' | 'agenda';
};

export type GalleryPhoto = {
    public_id: string;
    caption: string | null;
    url: string | null;
    thumb_url: string | null;
};

export type GalleryRow = {
    public_id: string;
    slug: string;
    title: string;
    description: string | null;
    date: string | null;
    status: ContentStatus;
    publish_at: string | null;
    photos_count: number;
    cover: ImageRef | null;
    photos: GalleryPhoto[];
};

export type BannerRow = {
    public_id: string;
    title: string;
    subtitle: string | null;
    link_url: string | null;
    link_label: string | null;
    position: number;
    is_active: boolean;
    is_live: boolean;
    starts_at: string | null;
    ends_at: string | null;
    image: ImageRef | null;
};

export type CategoryRow = {
    public_id: string;
    name: string;
    slug: string;
    posts_count: number;
};

export type TeacherWebsiteRow = {
    public_id: string;
    name: string;
    photo_url: string | null;
    show_on_website: boolean;
    website_title: string | null;
};

export const STATUS_VARIANTS: Record<ContentStatusValue, StatusVariant> = {
    DRAFT: 'neutral',
    REVIEW: 'warning',
    PUBLISHED: 'success',
    ARCHIVED: 'neutral',
};

/** Cermin ContentStatus::canTransitionTo() di server. */
export const TRANSITIONS: Record<ContentStatusValue, ContentStatusValue[]> = {
    DRAFT: ['REVIEW', 'PUBLISHED', 'ARCHIVED'],
    REVIEW: ['DRAFT', 'PUBLISHED'],
    PUBLISHED: ['DRAFT', 'ARCHIVED'],
    ARCHIVED: ['DRAFT', 'PUBLISHED'],
};

export const TRANSITION_LABELS: Record<ContentStatusValue, string> = {
    DRAFT: 'Kembalikan ke draf',
    REVIEW: 'Ajukan untuk ditinjau',
    PUBLISHED: 'Terbitkan sekarang',
    ARCHIVED: 'Arsipkan',
};
