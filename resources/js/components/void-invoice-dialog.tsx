import { Form } from '@inertiajs/react';
import { Ban } from 'lucide-react';
import InvoiceController from '@/actions/App/Http/Controllers/InvoiceController';
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
import { Spinner } from '@/components/ui/spinner';
import type { Invoice } from '@/types';

export default function VoidInvoiceDialog({ invoice }: { invoice: Invoice }) {
    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button variant="outline" data-test="void-invoice-button">
                    <Ban />
                    Void
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogTitle>Void {invoice.invoice_number}?</DialogTitle>
                <DialogDescription>
                    The invoice is kept for the record but no longer counts
                    towards billing or the patient's balance. This can't be
                    undone.
                </DialogDescription>

                <Form {...InvoiceController.void.form(invoice.id)}>
                    {({ processing }) => (
                        <DialogFooter className="gap-2">
                            <DialogClose asChild>
                                <Button type="button" variant="secondary">
                                    Keep invoice
                                </Button>
                            </DialogClose>
                            <Button
                                variant="destructive"
                                disabled={processing}
                                data-test="confirm-void-invoice-button"
                            >
                                {processing && <Spinner />}
                                Void invoice
                            </Button>
                        </DialogFooter>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
