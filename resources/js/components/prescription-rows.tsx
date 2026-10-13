import { Plus, Trash2 } from 'lucide-react';
import { useRef, useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';
import type { PrescriptionItem } from '@/types';

const MAX_ROWS = 20;

type Field = Exclude<keyof PrescriptionItem, 'id'>;

const columns: {
    field: Field;
    label: string;
    placeholder: string;
    list?: string;
    optional?: boolean;
}[] = [
    { field: 'medicine', label: 'Medicine', placeholder: 'e.g. Paracetamol' },
    { field: 'dosage', label: 'Dosage', placeholder: 'e.g. 500 mg' },
    {
        field: 'frequency',
        label: 'Frequency',
        placeholder: 'e.g. Twice daily',
        list: 'prescription-frequencies',
    },
    {
        field: 'duration',
        label: 'Duration',
        placeholder: 'e.g. 5 days',
        list: 'prescription-durations',
    },
    {
        field: 'instructions',
        label: 'Instructions',
        placeholder: 'e.g. After meals',
        optional: true,
    },
];

const gridColumns =
    'sm:grid-cols-[minmax(0,2fr)_minmax(0,1fr)_minmax(0,1.3fr)_minmax(0,1fr)_minmax(0,1.6fr)_auto]';

type Row = {
    key: number;
    item: Partial<PrescriptionItem>;
};

/**
 * Prescription rows the doctor can add and remove. Each row is submitted as
 * prescriptions[n][medicine], prescriptions[n][dosage], and so on.
 */
export default function PrescriptionRows({
    items,
    errors,
    frequencies,
    durations,
}: {
    items: PrescriptionItem[];
    errors: Partial<Record<string, string>>;
    frequencies: string[];
    durations: string[];
}) {
    const nextKey = useRef(items.length);
    const [rows, setRows] = useState<Row[]>(() =>
        items.map((item, index) => ({ key: index, item })),
    );

    const addRow = () => {
        const key = nextKey.current;
        nextKey.current += 1;
        setRows((current) => [...current, { key, item: {} }]);
    };

    const removeRow = (key: number) =>
        setRows((current) => current.filter((row) => row.key !== key));

    return (
        <div className="space-y-3">
            <datalist id="prescription-frequencies">
                {frequencies.map((frequency) => (
                    <option key={frequency} value={frequency} />
                ))}
            </datalist>
            <datalist id="prescription-durations">
                {durations.map((duration) => (
                    <option key={duration} value={duration} />
                ))}
            </datalist>

            {rows.length === 0 ? (
                <p className="text-sm text-muted-foreground">
                    No medicines prescribed. Add a row for each medicine.
                </p>
            ) : (
                <div className="space-y-2">
                    <div
                        className={cn(
                            'hidden gap-2 text-sm font-medium sm:grid',
                            gridColumns,
                        )}
                        aria-hidden
                    >
                        {columns.map((column) => (
                            <div key={column.field}>
                                {column.label}
                                {column.optional && (
                                    <span className="font-normal text-muted-foreground">
                                        {' '}
                                        (optional)
                                    </span>
                                )}
                            </div>
                        ))}
                        <div className="w-9" />
                    </div>

                    {rows.map((row, index) => {
                        const rowErrors = columns
                            .map(
                                (column) =>
                                    errors[
                                        `prescriptions.${index}.${column.field}`
                                    ],
                            )
                            .filter((error): error is string => Boolean(error));

                        return (
                            <div
                                key={row.key}
                                className="space-y-1 rounded-lg border p-3 sm:rounded-none sm:border-0 sm:p-0"
                                data-test="prescription-row"
                            >
                                <div className={cn('grid gap-2', gridColumns)}>
                                    {columns.map((column) => (
                                        <Input
                                            key={column.field}
                                            name={`prescriptions[${index}][${column.field}]`}
                                            defaultValue={
                                                row.item[column.field] ?? ''
                                            }
                                            placeholder={column.placeholder}
                                            list={column.list}
                                            required={!column.optional}
                                            autoComplete="off"
                                            aria-label={`${column.label}, medicine ${index + 1}`}
                                            aria-invalid={
                                                errors[
                                                    `prescriptions.${index}.${column.field}`
                                                ]
                                                    ? true
                                                    : undefined
                                            }
                                        />
                                    ))}
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        onClick={() => removeRow(row.key)}
                                        aria-label={`Remove medicine ${index + 1}`}
                                    >
                                        <Trash2 />
                                    </Button>
                                </div>
                                {rowErrors.map((error) => (
                                    <InputError key={error} message={error} />
                                ))}
                            </div>
                        );
                    })}
                </div>
            )}

            <InputError message={errors.prescriptions} />

            <Button
                type="button"
                variant="outline"
                size="sm"
                onClick={addRow}
                disabled={rows.length >= MAX_ROWS}
                data-test="add-medicine-button"
            >
                <Plus />
                Add medicine
            </Button>
        </div>
    );
}
