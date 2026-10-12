export type Role = 'admin' | 'doctor' | 'receptionist';

export type RoleOption = {
    value: Role;
    label: string;
};

export type Permission =
    | 'patients.view'
    | 'patients.manage'
    | 'patients.delete'
    | 'appointments.view'
    | 'appointments.manage'
    | 'billing.view'
    | 'billing.manage'
    | 'staff.manage';

export type User = {
    id: number;
    name: string;
    email: string;
    role: Role;
    specialization: string | null;
    phone: string | null;
    is_active: boolean;
    avatar?: string;
    email_verified_at: string | null;
    two_factor_enabled?: boolean;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type Auth = {
    user: User;
    role: RoleOption;
    permissions: Permission[];
};

export type Passkey = {
    id: number;
    name: string;
    authenticator: string | null;
    created_at_diff: string;
    last_used_at_diff: string | null;
};

export type TwoFactorSetupData = {
    svg: string;
    url: string;
};

export type TwoFactorSecretKey = {
    secretKey: string;
};
