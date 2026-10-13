import { router } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { useEffect, useId, useState } from 'react';
import type { KeyboardEvent } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogTitle,
} from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';
import { usePatientSearch } from '@/hooks/use-patient-search';
import { formatAge } from '@/lib/dates';
import { cn } from '@/lib/utils';
import { show } from '@/routes/patients';
import type { PatientSummary } from '@/types';

const isMac =
    typeof navigator !== 'undefined' &&
    /Mac|iPhone|iPad/.test(navigator.userAgent);

/**
 * The patient search in the header: a button (or Ctrl/⌘ K) opens a search by
 * name, MRN or phone, and choosing a result opens the patient's record.
 */
export default function PatientSearch({
    compact = false,
}: {
    compact?: boolean;
}) {
    const listId = useId();
    const [open, setOpen] = useState(false);
    const [query, setQuery] = useState('');
    const [activeIndex, setActiveIndex] = useState(0);

    const term = query.trim();
    const { results, loading } = usePatientSearch(term, open && term !== '');
    const shown = term === '' ? [] : results;

    useEffect(() => {
        const onKeyDown = (event: globalThis.KeyboardEvent) => {
            if (
                (event.metaKey || event.ctrlKey) &&
                event.key.toLowerCase() === 'k'
            ) {
                event.preventDefault();
                setOpen((value) => !value);
            }
        };

        window.addEventListener('keydown', onKeyDown);

        return () => window.removeEventListener('keydown', onKeyDown);
    }, []);

    const close = () => {
        setOpen(false);
        setQuery('');
        setActiveIndex(0);
    };

    const choose = (patient: PatientSummary) => {
        close();
        router.visit(show(patient.id));
    };

    const onKeyDown = (event: KeyboardEvent<HTMLInputElement>) => {
        if (event.key === 'ArrowDown') {
            event.preventDefault();
            setActiveIndex((index) =>
                Math.min(index + 1, Math.max(shown.length - 1, 0)),
            );
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            setActiveIndex((index) => Math.max(index - 1, 0));
        } else if (event.key === 'Enter' && shown[activeIndex]) {
            event.preventDefault();
            choose(shown[activeIndex]);
        }
    };

    const optionId = (patient: PatientSummary) => `${listId}-${patient.id}`;

    const listContent = () => {
        if (term === '') {
            return (
                <p className="px-3 py-6 text-center text-sm text-muted-foreground">
                    Type a patient's name, MRN or phone number.
                </p>
            );
        }

        if (shown.length === 0) {
            return (
                <p className="flex items-center justify-center gap-2 px-3 py-6 text-sm text-muted-foreground">
                    {loading ? (
                        <>
                            <Spinner />
                            Searching…
                        </>
                    ) : (
                        `No patients match "${term}".`
                    )}
                </p>
            );
        }

        return (
            <ul id={listId} role="listbox" aria-label="Patients">
                {shown.map((patient, index) => (
                    <li
                        key={patient.id}
                        id={optionId(patient)}
                        role="option"
                        aria-selected={index === activeIndex}
                        className={cn(
                            'flex cursor-pointer items-center justify-between gap-3 rounded-md px-3 py-2',
                            index === activeIndex &&
                                'bg-accent text-accent-foreground',
                        )}
                        onMouseEnter={() => setActiveIndex(index)}
                        onClick={() => choose(patient)}
                    >
                        <div className="min-w-0">
                            <div className="truncate text-sm font-medium">
                                {patient.full_name}
                            </div>
                            <div className="truncate text-xs text-muted-foreground">
                                {formatAge(patient.age)} · {patient.phone}
                            </div>
                        </div>
                        <span className="shrink-0 font-mono text-xs text-muted-foreground">
                            {patient.mrn}
                        </span>
                    </li>
                ))}
            </ul>
        );
    };

    return (
        <>
            <Button
                variant="outline"
                size="sm"
                onClick={() => setOpen(true)}
                aria-label="Search patients"
                aria-keyshortcuts={isMac ? 'Meta+K' : 'Control+K'}
                className={cn(
                    'text-muted-foreground',
                    compact
                        ? 'size-9 px-0'
                        : 'size-9 px-0 sm:w-64 sm:justify-start sm:px-3',
                )}
                data-test="patient-search-button"
            >
                <Search />
                {!compact && (
                    <>
                        <span className="hidden sm:inline">
                            Search patients…
                        </span>
                        <kbd className="pointer-events-none ml-auto hidden rounded border bg-muted px-1.5 font-mono text-[10px] font-medium sm:inline">
                            {isMac ? '⌘K' : 'Ctrl K'}
                        </kbd>
                    </>
                )}
            </Button>

            <Dialog
                open={open}
                onOpenChange={(value) => (value ? setOpen(true) : close())}
            >
                <DialogContent className="top-[15%] translate-y-0 gap-0 overflow-hidden p-0 sm:max-w-lg">
                    <DialogTitle className="sr-only">
                        Search patients
                    </DialogTitle>
                    <DialogDescription className="sr-only">
                        Find a patient by name, MRN or phone number, then press
                        Enter to open their record.
                    </DialogDescription>

                    <div className="flex items-center gap-2 border-b pr-10 pl-3">
                        <Search className="size-4 shrink-0 text-muted-foreground" />
                        <input
                            role="combobox"
                            aria-expanded={shown.length > 0}
                            aria-controls={listId}
                            aria-autocomplete="list"
                            aria-activedescendant={
                                shown[activeIndex]
                                    ? optionId(shown[activeIndex])
                                    : undefined
                            }
                            value={query}
                            onChange={(event) => {
                                setQuery(event.target.value);
                                setActiveIndex(0);
                            }}
                            onKeyDown={onKeyDown}
                            placeholder="Search by name, MRN or phone"
                            autoComplete="off"
                            className="h-12 w-full bg-transparent text-sm outline-none placeholder:text-muted-foreground"
                            data-test="patient-search-input"
                        />
                    </div>

                    <div className="max-h-80 overflow-y-auto p-2">
                        {listContent()}
                    </div>
                </DialogContent>
            </Dialog>
        </>
    );
}
