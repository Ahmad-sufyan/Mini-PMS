import { Search } from 'lucide-react';
import { useEffect, useId, useState } from 'react';
import type { KeyboardEvent } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { formatAge } from '@/lib/dates';
import { cn } from '@/lib/utils';
import { search } from '@/routes/patients';
import type { PatientSummary } from '@/types';

type Props = {
    id: string;
    defaultPatient: PatientSummary | null;
    invalid?: boolean;
};

function describe(patient: PatientSummary): string {
    return `${patient.mrn} · ${formatAge(patient.age)} · ${patient.phone}`;
}

/**
 * A searchable patient picker (combobox). The chosen patient's id is
 * submitted as "patient_id".
 */
export default function PatientPicker({ id, defaultPatient, invalid }: Props) {
    const listId = useId();
    const [selected, setSelected] = useState(defaultPatient);
    const [query, setQuery] = useState('');
    const [open, setOpen] = useState(false);
    const [results, setResults] = useState<PatientSummary[]>([]);
    const [activeIndex, setActiveIndex] = useState(0);
    const [loading, setLoading] = useState(false);
    const [changing, setChanging] = useState(false);

    // Search once the user pauses typing; a newer search cancels the last.
    useEffect(() => {
        if (!open) {
            return;
        }

        const controller = new AbortController();
        const timeout = setTimeout(async () => {
            setLoading(true);

            try {
                const response = await fetch(search.url({ query: { query } }), {
                    headers: { Accept: 'application/json' },
                    signal: controller.signal,
                });
                const body = (await response.json()) as {
                    data: PatientSummary[];
                };

                setResults(body.data);
                setActiveIndex(0);
            } catch {
                // Aborted by a newer search, or the request failed.
            } finally {
                setLoading(false);
            }
        }, 200);

        return () => {
            clearTimeout(timeout);
            controller.abort();
        };
    }, [query, open]);

    const choose = (patient: PatientSummary) => {
        setSelected(patient);
        setOpen(false);
    };

    const onKeyDown = (event: KeyboardEvent<HTMLInputElement>) => {
        if (event.key === 'ArrowDown') {
            event.preventDefault();
            setOpen(true);
            setActiveIndex((index) =>
                Math.min(index + 1, Math.max(results.length - 1, 0)),
            );
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            setActiveIndex((index) => Math.max(index - 1, 0));
        } else if (event.key === 'Enter' && open) {
            // Don't submit the form while the list is open.
            event.preventDefault();

            if (results[activeIndex]) {
                choose(results[activeIndex]);
            }
        } else if (event.key === 'Escape') {
            setOpen(false);
        }
    };

    const optionId = (patient: PatientSummary) => `${listId}-${patient.id}`;
    const active = open ? results[activeIndex] : undefined;

    const listContent = () => {
        if (results.length === 0) {
            return (
                <li className="px-2 py-1.5 text-sm text-muted-foreground">
                    {loading ? 'Searching…' : 'No patients found'}
                </li>
            );
        }

        return results.map((patient, index) => (
            <li
                key={patient.id}
                id={optionId(patient)}
                role="option"
                aria-selected={index === activeIndex}
                className={cn(
                    'cursor-pointer rounded-sm px-2 py-1.5',
                    index === activeIndex && 'bg-accent text-accent-foreground',
                )}
                onMouseDown={(event) => event.preventDefault()}
                onMouseEnter={() => setActiveIndex(index)}
                onClick={() => choose(patient)}
            >
                <div className="text-sm font-medium">{patient.full_name}</div>
                <div className="text-xs text-muted-foreground">
                    {describe(patient)}
                </div>
            </li>
        ));
    };

    return (
        <>
            <input type="hidden" name="patient_id" value={selected?.id ?? ''} />

            {selected ? (
                <div
                    className={cn(
                        'flex items-center justify-between gap-3 rounded-md border px-3 py-2',
                        invalid && 'border-destructive',
                    )}
                >
                    <div className="min-w-0">
                        <div className="truncate font-medium">
                            {selected.full_name}
                        </div>
                        <div className="truncate text-sm text-muted-foreground">
                            {describe(selected)}
                        </div>
                    </div>
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        onClick={() => {
                            setSelected(null);
                            setQuery('');
                            setChanging(true);
                        }}
                    >
                        Change
                    </Button>
                </div>
            ) : (
                <div className="relative">
                    <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        id={id}
                        role="combobox"
                        aria-expanded={open}
                        aria-controls={listId}
                        aria-autocomplete="list"
                        aria-activedescendant={
                            active ? optionId(active) : undefined
                        }
                        aria-invalid={invalid ? true : undefined}
                        value={query}
                        autoFocus={changing}
                        autoComplete="off"
                        placeholder="Search by name, MRN or phone"
                        className="pl-9"
                        onChange={(event) => {
                            setQuery(event.target.value);
                            setOpen(true);
                        }}
                        onFocus={() => setOpen(true)}
                        onBlur={() => setOpen(false)}
                        onKeyDown={onKeyDown}
                        data-test="patient-picker-input"
                    />

                    {open && (
                        <ul
                            id={listId}
                            role="listbox"
                            className="absolute z-50 mt-1 max-h-72 w-full overflow-y-auto rounded-md border bg-popover p-1 text-popover-foreground shadow-md"
                        >
                            {listContent()}
                        </ul>
                    )}
                </div>
            )}
        </>
    );
}
