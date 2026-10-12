export type Clinic = {
    name: string;
    address: string;
    phone: string;
    timezone: string;
    opening_hours: {
        opens_at: string;
        closes_at: string;
    };
    working_days: string[];
    currency: string;
};
