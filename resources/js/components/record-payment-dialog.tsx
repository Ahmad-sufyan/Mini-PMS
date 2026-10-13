import { Form } from '@inertiajs/react';
import { Banknote } from 'lucide-react';
import { useState } from 'react';
import InvoicePaymentController from '@/actions/App/Http/Controllers/InvoicePaymentController';
import Field from '@/components/form-field';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { useCurrency } from '@/hooks/use-currency';
import { amountStep, toAmountInput } from '@/lib/money';
import type { Invoice, PaymentMethod, SelectOption } from '@/types';

export default function RecordPaymentDialog({
    invoice,
    methods,
    today,
}: {
    invoice: Invoice;
    methods: SelectOption<PaymentMethod>[];
    today: string;
}) {
    const { currency, format } = useCurrency();
    const [open, setOpen] = useState(false);
    const [method, setMethod] = useState<string>('cash');

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button data-test="record-payment-button">
                    <Banknote />
                    Record payment
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogTitle>Record payment</DialogTitle>
                <DialogDescription>
                    {invoice.invoice_number} for {invoice.patient.full_name}.
                    The balance is {format(invoice.balance)}.
                </DialogDescription>

                <Form
                    {...InvoicePaymentController.form(invoice.id)}
                    options={{ preserveScroll: true }}
                    onSuccess={() => setOpen(false)}
                    className="space-y-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <Field
                                id="amount"
                                label="Amount"
                                error={errors.amount}
                            >
                                <Input
                                    id="amount"
                                    type="number"
                                    name="amount"
                                    defaultValue={toAmountInput(
                                        invoice.balance,
                                        currency,
                                    )}
                                    min={0}
                                    step={amountStep(currency)}
                                    required
                                    autoFocus
                                    aria-invalid={
                                        errors.amount ? true : undefined
                                    }
                                />
                            </Field>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <Field
                                    id="method"
                                    label="Method"
                                    error={errors.method}
                                >
                                    <input
                                        type="hidden"
                                        name="method"
                                        value={method}
                                    />
                                    <Select
                                        value={method}
                                        onValueChange={setMethod}
                                    >
                                        <SelectTrigger
                                            id="method"
                                            className="w-full"
                                        >
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {methods.map((option) => (
                                                <SelectItem
                                                    key={option.value}
                                                    value={option.value}
                                                >
                                                    {option.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </Field>

                                <Field
                                    id="paid_on"
                                    label="Date"
                                    error={errors.paid_on}
                                >
                                    <Input
                                        id="paid_on"
                                        type="date"
                                        name="paid_on"
                                        defaultValue={today}
                                        min={invoice.issued_on}
                                        max={today}
                                        required
                                        aria-invalid={
                                            errors.paid_on ? true : undefined
                                        }
                                    />
                                </Field>
                            </div>

                            <DialogFooter className="gap-2">
                                <DialogClose asChild>
                                    <Button type="button" variant="secondary">
                                        Cancel
                                    </Button>
                                </DialogClose>
                                <Button
                                    disabled={processing}
                                    data-test="confirm-payment-button"
                                >
                                    {processing && <Spinner />}
                                    Record payment
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
