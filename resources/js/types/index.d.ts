import { InertiaLinkProps } from '@inertiajs/vue3';
import type { LucideIcon } from 'lucide-vue-next';
import { route as routeFn } from 'ziggy-js';

declare global {
    interface Window {
        route: typeof routeFn;
    }
}

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon;
    isActive?: boolean;
}

export type AppPageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    sidebarOpen: boolean;
};

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
}

export interface Unit {
    id: number;
    building: string;
    floor: string;
    unit: string;
    subunit: string;
    max_residents: number;
    residents: number;
    name: string;
    registration_codes: registrationCode[];
}

export interface PaginatedLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface Paginator<T> {
    data: T[];
    links: PaginatedLink[];
    current_page: number;
    first_page_url: string;
    last_page: number;
    last_page_url: string;
    next_page_url: string | null;
    prev_page_url: string | null;
    path: string;
    per_page: number;
    from: number;
    to: number;
    total: number;
}

export interface UnitFilters {
    search: string;
    building: string | null;
    floor: string | null;
}

export interface ReservationFilters {
    search: string;
    room: string | null;
    status: string | null;
    date: date;
}

export interface registrationCode {
    id: number;
    code: string;
    is_used: boolean;
}

export interface RoomStatus {
    name: string;
    label: string;
    text_color: string;
    background_color: string;
}

export interface ReservationStatus {
    name: string;
    value: string;
    label: string;
    text_color: string;
    background_color: string;
}

export interface Room {
    id: number;
    name: string;
    slug: string;
    description: string;
    location: string;
    image_url: string;
    status: RoomStatus;
}

export interface Reservation {
    id: number;
    name: string;
    room: Room;
    start_at: string;
    end_at: string;
    user_name: string;
    user: User;
    organisation: Organisation | null;
}

export interface Organisation {
    id: number;
    name: string;
    about: string;
    image_path: string;
    slug: string;
}

export interface Agenda {
    id: number;
    name: string;
    slug: string;
    description: string;
    public: boolean;
}

export interface AgendaItem {
    id: number;
    title: string;
    description: string;
    short_description: string;
    image_path: string;
    slug: string;
    start_date: string;
    end_date: string;
    user: User;
    organisation: Organisation;
    agenda: Agenda;
    image_url: string;

}

export type BreadcrumbItemType = BreadcrumbItem;
