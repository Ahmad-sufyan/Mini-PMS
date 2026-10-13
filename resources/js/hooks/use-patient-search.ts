import { useEffect, useState } from 'react';
import { search } from '@/routes/patients';
import type { PatientSummary } from '@/types';

export type UsePatientSearchReturn = {
    results: PatientSummary[];
    loading: boolean;
};

/**
 * Search patients by name, MRN or phone once the user pauses typing. A newer
 * search cancels the one in flight.
 */
export function usePatientSearch(
    query: string,
    enabled: boolean,
): UsePatientSearchReturn {
    const [results, setResults] = useState<PatientSummary[]>([]);
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        if (!enabled) {
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
            } catch {
                // Cancelled by a newer search, or the request failed.
            } finally {
                if (!controller.signal.aborted) {
                    setLoading(false);
                }
            }
        }, 200);

        return () => {
            clearTimeout(timeout);
            controller.abort();
        };
    }, [query, enabled]);

    return { results, loading };
}
