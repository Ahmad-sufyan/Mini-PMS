import type { User } from '@/types/auth';

export type StaffMember = Pick<
    User,
    'id' | 'name' | 'email' | 'role' | 'specialization' | 'phone' | 'is_active'
>;
