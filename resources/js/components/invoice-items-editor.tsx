import { Plus, Trash2 } from 'lucide-react';
import { useRef, useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useCurrency } from '@/hooks/use-currency';
import { amountStep, parseAmount, toAmountInput } from '@/lib/money';
import { cn } from '@/lib/utils';
import type { InvoiceItem, Service } from '@/types';

const MAX_LINES = 50;

// Radix Select items can't use an empty value, so a custom line gets its own.
const CUSTOM = 'custom';

const gridColumns =
    'sm:grid-cols-[minmax(0,1.3fr)_minmax(0,2fr)_5rem_8rem_7rem_auto]';

type Row = {
    key: number;
    serviceId: string;
    description: string;
    quantity: string;
    unitPrice: string;
};

/**
 * The invoice lines (a service from the price list or a custom line) and
 * the discount, with the totals worked out as you type. The backend works
 * them out again when saving.
 */
export default function InvoiceItemsEditor({
    services,
    items,
    discount,
    errors,
}: {
    services: Service[];
    items: InvoiceItem[];
    discount: number;
    errors: Partial<Record<string, string>>;
}) {
    const { currency, format } = useCurrency();
    const nextKey = useRef(Math.max(items.length, 1));

    const toRow = (item: InvoiceItem, key: number): Row => ({
        key,
        serviceId: item.service_id ? String(item.service_id) : '',
        description: item.description,
        quantity: String(item.quantity),
        unitPrice: toAmountInput(item.unit_price, currency),
    });

    const emptyRow = (key: number): Row => ({
        key,
        serviceId: '',
        description: '',
        quantity: '1',
        unitPrice: '',
    });

    const [rows, setRows] = useState<Row[]>(() =>
        items.length > 0 ? items.map(toRow) : [emptyRow(0)],
    );
    const [discountInput, setDiscountInput] = useState(
        discount > 0 ? toAmountInput(discount, currency) : '',
    );

    const update = (key: number, changes: Partial<Row>) =>
        setRows((current) =>
            current.map((row) =>
                row.key === key ? { ...row, ...changes } : row,
            ),
        );

    const chooseService = (key: number, value: string) => {
        const service = services.find((option) => String(option.id) === value);

        update(
            key,
            service
                ? {
                      serviceId: String(service.id),
                      description: service.name,
                      unitPrice: toAmountInput(service.price, currency),
                  }
                : { serviceId: '' },
        );
    };

    const addRow = () => {
        const key = nextKey.current;
        nextKey.current += 1;
        setRows((current) => [...current, emptyRow(key)]);
    };

    const removeRow = (key: number) =>
        setRows((current) => current.filter((row) => row.key !== key));

    const lineTotal = (row: Row): number | null => {
        const price = parseAmount(row.unitPrice, currency);
        const quantity = Number(row.quantity);

        return price !== null && Number.isInteger(quantity) && quantity > 0
            ? price * quantity
            : null;
    };

    const subtotal = rows.reduce((sum, row) => sum + (lineTotal(row) ?? 0), 0);
    const discountMinor = parseAmount(discountInput, currency) ?? 0;
    const total = Math.max(subtotal - discountMinor, 0);
    const step = amountStep(currency);

    return (
        <div className="space-y-4">
            <div
                className={cn(
                    'hidden gap-2 text-sm font-medium sm:grid',
                    gridColumns,
                )}
                aria-hidden
            >
                <div>Service</div>
                <div>Description</div>
                <div>Qty</div>
                <div>Unit price</div>
                <div className="text-right">Amount</div>
                <div className="w-9" />
            </div>

            {rows.map((row, index) => {
                const error = (field: string) =>
                    errors[`items.${index}.${field}`];
                const rowErrors = [
                    'service_id',
                    'description',
                    'quantity',
                    'unit_price',
                ]
                    .map(error)
                    .filter((message): message is string => Boolean(message));
                const amount = lineTotal(row);

                return (
                    <div
                        key={row.key}
                        className="space-y-1 rounded-lg border p-3 sm:rounded-none sm:border-0 sm:p-0"
                        data-test="invoice-line"
                    >
                        <div
                            className={cn(
                                'grid items-center gap-2',
                                gridColumns,
                            )}
                        >
                            <input
                                type="hidden"
                                name={`items[${index}][service_id]`}
                                value={row.serviceId}
                            />
                            <Select
                                value={row.serviceId || CUSTOM}
                                onValueChange={(value) =>
                                    chooseService(row.key, value)
                                }
                            >
                                <SelectTrigger
                                    className="w-full"
                                    aria-label={`Service, line ${index + 1}`}
                                >
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={CUSTOM}>
                                        Custom line
                                    </SelectItem>
                                    {services.map((service) => (
                                        <SelectItem
                                            key={service.id}
                                            value={String(service.id)}
                                        >
                                            {service.name} ·{' '}
                                            {format(service.price)}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <Input
                                name={`items[${index}][description]`}
                                value={row.description}
                                onChange={(event) =>
                                    update(row.key, {
                                        description: event.target.value,
                                    })
                                }
                                placeholder="Description"
                                required
                                autoComplete="off"
                                aria-label={`Description, line ${index + 1}`}
                                aria-invalid={
                                    error('description') ? true : undefined
                                }
                            />
                            <Input
                                type="number"
                                name={`items[${index}][quantity]`}
                                value={row.quantity}
                                onChange={(event) =>
                                    update(row.key, {
                                        quantity: event.target.value,
                                    })
                                }
                                min={1}
                                max={999}
                                required
                                aria-label={`Quantity, line ${index + 1}`}
                                aria-invalid={
                                    error('quantity') ? true : undefined
                                }
                            />
                            <Input
                                type="number"
                                name={`items[${index}][unit_price]`}
                                value={row.unitPrice}
                                onChange={(event) =>
                                    update(row.key, {
                                        unitPrice: event.target.value,
                                    })
                                }
                                min={0}
                                step={step}
                                required
                                placeholder={toAmountInput(0, currency)}
                                aria-label={`Unit price, line ${index + 1}`}
                                aria-invalid={
                                    error('unit_price') ? true : undefined
                                }
                            />
                            <div className="text-right text-sm font-medium tabular-nums">
                                {amount === null ? '—' : format(amount)}
                            </div>
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                onClick={() => removeRow(row.key)}
                                disabled={rows.length === 1}
                                aria-label={`Remove line ${index + 1}`}
                            >
                                <Trash2 />
                            </Button>
                        </div>
                        {rowErrors.map((message) => (
                            <InputError key={message} message={message} />
                        ))}
                    </div>
                );
            })}

            <InputError message={errors.items} />

            <Button
                type="button"
                variant="outline"
                size="sm"
                onClick={addRow}
                disabled={rows.length >= MAX_LINES}
                data-test="add-invoice-line-button"
            >
                <Plus />
                Add line
            </Button>

            <dl className="ml-auto grid max-w-sm grid-cols-[1fr_auto] items-center gap-x-6 gap-y-2 text-sm">
                <dt className="text-muted-foreground">Subtotal</dt>
                <dd
                    className="text-right tabular-nums"
                    data-test="invoice-subtotal"
                >
                    {format(subtotal)}
                </dd>

                <dt>
                    <Label
                        htmlFor="discount"
                        className="font-normal text-muted-foreground"
                    >
                        Discount
                    </Label>
                </dt>
                <dd>
                    <Input
                        id="discount"
                        type="number"
                        name="discount"
                        value={discountInput}
                        onChange={(event) =>
                            setDiscountInput(event.target.value)
                        }
                        min={0}
                        step={step}
                        placeholder={toAmountInput(0, currency)}
                        className="w-32 text-right"
                        aria-invalid={errors.discount ? true : undefined}
                    />
                </dd>

                <dt className="font-medium">Total</dt>
                <dd
                    className="text-right text-base font-semibold tabular-nums"
                    data-test="invoice-total"
                >
                    {format(total)}
                </dd>
            </dl>
            <InputError message={errors.discount} className="text-right" />
        </div>
    );
}
