import { useState } from 'react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { RoleOption, StaffMember } from '@/types';

type Props = {
    roles: RoleOption[];
    errors: Partial<Record<string, string>>;
    passwordRules: string;
    staffMember?: StaffMember;
    isSelf?: boolean;
};

export default function StaffFormFields({
    roles,
    errors,
    passwordRules,
    staffMember,
    isSelf = false,
}: Props) {
    const [role, setRole] = useState<string>(
        staffMember?.role ?? 'receptionist',
    );

    return (
        <>
            <div className="grid gap-2">
                <Label htmlFor="name">Full name</Label>
                <Input
                    id="name"
                    name="name"
                    defaultValue={staffMember?.name}
                    required
                    autoComplete="off"
                    placeholder="Full name"
                />
                <InputError message={errors.name} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="email">Email address</Label>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    defaultValue={staffMember?.email}
                    required
                    autoComplete="off"
                    placeholder="email@example.com"
                />
                <InputError message={errors.email} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="role">Role</Label>
                <input type="hidden" name="role" value={role} />
                <Select value={role} onValueChange={setRole} disabled={isSelf}>
                    <SelectTrigger
                        id="role"
                        className="w-full"
                        data-test="staff-role-select"
                    >
                        <SelectValue placeholder="Select a role" />
                    </SelectTrigger>
                    <SelectContent>
                        {roles.map((option) => (
                            <SelectItem key={option.value} value={option.value}>
                                {option.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                {isSelf && (
                    <p className="text-sm text-muted-foreground">
                        You can't change your own role or deactivate your own
                        account.
                    </p>
                )}
                <InputError message={errors.role} />
            </div>

            {role === 'doctor' && (
                <div className="grid gap-2">
                    <Label htmlFor="specialization">Specialization</Label>
                    <Input
                        id="specialization"
                        name="specialization"
                        defaultValue={staffMember?.specialization ?? ''}
                        required
                        autoComplete="off"
                        placeholder="e.g. Pediatrics"
                    />
                    <InputError message={errors.specialization} />
                </div>
            )}

            <div className="grid gap-2">
                <Label htmlFor="phone">
                    Phone{' '}
                    <span className="font-normal text-muted-foreground">
                        (optional)
                    </span>
                </Label>
                <Input
                    id="phone"
                    type="tel"
                    name="phone"
                    defaultValue={staffMember?.phone ?? ''}
                    autoComplete="off"
                    placeholder="+1 555 0100"
                />
                <InputError message={errors.phone} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="password">
                    {staffMember ? 'New password' : 'Password'}
                </Label>
                <PasswordInput
                    id="password"
                    name="password"
                    required={!staffMember}
                    autoComplete="new-password"
                    placeholder="Password"
                    passwordrules={passwordRules}
                />
                {staffMember && (
                    <p className="text-sm text-muted-foreground">
                        Leave blank to keep the current password.
                    </p>
                )}
                <InputError message={errors.password} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="password_confirmation">Confirm password</Label>
                <PasswordInput
                    id="password_confirmation"
                    name="password_confirmation"
                    required={!staffMember}
                    autoComplete="new-password"
                    placeholder="Confirm password"
                />
                <InputError message={errors.password_confirmation} />
            </div>

            {staffMember && (
                <div className="grid gap-2">
                    <div className="flex items-center space-x-3">
                        {isSelf && (
                            <input type="hidden" name="is_active" value="1" />
                        )}
                        <Checkbox
                            id="is_active"
                            name="is_active"
                            value="1"
                            defaultChecked={staffMember.is_active}
                            disabled={isSelf}
                        />
                        <Label htmlFor="is_active">Account is active</Label>
                    </div>
                    <p className="text-sm text-muted-foreground">
                        Inactive staff are signed out and can't log in.
                    </p>
                    <InputError message={errors.is_active} />
                </div>
            )}
        </>
    );
}
