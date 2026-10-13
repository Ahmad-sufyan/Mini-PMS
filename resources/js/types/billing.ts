/**
 * The clinic's currency. Amounts are integers in its smallest unit.
 */
export type Currency = {
    code: string;
    decimals: number;
};

export type InvoiceStatus = 'unpaid' | 'partial' | 'paid' | 'void';

export type PaymentMethod = 'cash' | 'card' | 'bank_transfer';

export type Service = {
    id: number;
    code: string;
    name: string;
    price: number;
};

export type InvoiceItem = {
    id?: number;
    service_id: number | null;
    description: string;
    quantity: number;
    unit_price: number;
    amount?: number;
};

export type Payment = {
    id: number;
    amount: number;
    method: PaymentMethod;
    method_label: string;
    paid_on: string;
    received_by: string | null;
};

/**
 * An invoice as given by InvoiceResource. Amounts are in minor units; the
 * lines and payments are only present on the pages that load them.
 */
export type Invoice = {
    id: number;
    invoice_number: string;
    status: InvoiceStatus;
    status_label: string;
    issued_on: string;
    patient: {
        id: number;
        mrn: string;
        full_name: string;
    };
    visit_id: number | null;
    subtotal: number;
    discount: number;
    total: number;
    paid: number;
    balance: number;
    notes: string | null;
    voided_on: string | null;
    items?: InvoiceItem[];
    payments?: Payment[];
    can: {
        update: boolean;
        record_payment: boolean;
        void: boolean;
    };
};

export type InvoiceTotals = {
    billed: number;
    collected: number;
    outstanding: number;
};

export type InvoiceFilters = {
    search: string;
    status: InvoiceStatus | null;
    from: string | null;
    to: string | null;
};

/**
 * A visit that hasn't been invoiced yet, for the patient's Billing tab.
 */
export type UninvoicedVisit = {
    id: number;
    date: string;
    doctor: string;
};

/**
 * The visit an invoice is (being) raised for.
 */
export type InvoiceVisit = {
    id: number;
    date: string;
    doctor: string;
};
