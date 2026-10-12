export type PaginationLink = {
    url: string | null;
    label: string;
    page?: number | null;
    active: boolean;
};

/**
 * A Laravel length-aware paginator, as serialized by Inertia.
 */
export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    from: number | null;
    to: number | null;
    total: number;
    links: PaginationLink[];
    prev_page_url: string | null;
    next_page_url: string | null;
};
