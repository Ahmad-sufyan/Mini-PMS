import type { Auth } from '@/types/auth';
import type { Currency } from '@/types/billing';

declare module 'react' {
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            currency: Currency;
            sidebarOpen: boolean;
            [key: string]: unknown;
        };
    }
}
