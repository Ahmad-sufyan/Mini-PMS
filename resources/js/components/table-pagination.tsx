import { Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import type { ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import type { Paginated } from '@/types';

function PageArrow({
    url,
    label,
    children,
}: {
    url: string | null;
    label: string;
    children: ReactNode;
}) {
    if (url === null) {
        return (
            <Button variant="ghost" size="icon" disabled aria-label={label}>
                {children}
            </Button>
        );
    }

    return (
        <Button variant="ghost" size="icon" asChild>
            <Link href={url} preserveScroll preserveState aria-label={label}>
                {children}
            </Link>
        </Button>
    );
}

export default function TablePagination<T>({
    paginator,
}: {
    paginator: Paginated<T>;
}) {
    if (paginator.total === 0) {
        return null;
    }

    // The first and last links are "previous" and "next".
    const pages = paginator.links.slice(1, -1);

    return (
        <div className="flex flex-col items-center justify-between gap-3 sm:flex-row">
            <p className="text-sm text-muted-foreground">
                Showing {paginator.from}–{paginator.to} of {paginator.total}
            </p>

            {paginator.last_page > 1 && (
                <nav
                    aria-label="Pagination"
                    className="flex items-center gap-1"
                >
                    <PageArrow
                        url={paginator.prev_page_url}
                        label="Previous page"
                    >
                        <ChevronLeft />
                    </PageArrow>

                    {pages.map((link, index) =>
                        link.url === null ? (
                            <span
                                key={`gap-${index}`}
                                className="px-2 text-sm text-muted-foreground"
                            >
                                …
                            </span>
                        ) : (
                            <Button
                                key={link.label}
                                variant={link.active ? 'outline' : 'ghost'}
                                size="sm"
                                asChild
                            >
                                <Link
                                    href={link.url}
                                    preserveScroll
                                    preserveState
                                    aria-current={
                                        link.active ? 'page' : undefined
                                    }
                                >
                                    {link.label}
                                </Link>
                            </Button>
                        ),
                    )}

                    <PageArrow url={paginator.next_page_url} label="Next page">
                        <ChevronRight />
                    </PageArrow>
                </nav>
            )}
        </div>
    );
}
