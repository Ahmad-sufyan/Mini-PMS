import { Form, Head, Link, usePage } from '@inertiajs/react';
import { Pencil, Plus } from 'lucide-react';
import StaffController from '@/actions/App/Http/Controllers/StaffController';
import DeactivateStaffDialog from '@/components/deactivate-staff-dialog';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { cn } from '@/lib/utils';
import { create, edit, index } from '@/routes/staff';
import type { RoleOption, StaffMember } from '@/types';

type Props = {
    staff: StaffMember[];
    roles: RoleOption[];
};

export default function StaffIndex({ staff, roles }: Props) {
    const { auth } = usePage().props;

    const roleLabel = (role: StaffMember['role']): string =>
        roles.find((option) => option.value === role)?.label ?? role;

    return (
        <>
            <Head title="Staff" />

            <div className="flex h-full flex-1 flex-col px-4 py-6">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Staff"
                        description="Manage who can sign in to the clinic and what they can access"
                    />

                    <Button asChild>
                        <Link href={create()} data-test="add-staff-button">
                            <Plus />
                            Add staff member
                        </Link>
                    </Button>
                </div>

                <div className="overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead className="pl-4">Name</TableHead>
                                <TableHead className="hidden sm:table-cell">
                                    Role
                                </TableHead>
                                <TableHead className="hidden md:table-cell">
                                    Specialization
                                </TableHead>
                                <TableHead className="hidden lg:table-cell">
                                    Phone
                                </TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="pr-4">
                                    <span className="sr-only">Actions</span>
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {staff.map((member) => {
                                const isSelf = member.id === auth.user.id;

                                return (
                                    <TableRow
                                        key={member.id}
                                        className={cn(
                                            !member.is_active &&
                                                'text-muted-foreground',
                                        )}
                                    >
                                        <TableCell className="pl-4 whitespace-normal">
                                            <div className="font-medium">
                                                {member.name}
                                                {isSelf && (
                                                    <span className="font-normal text-muted-foreground">
                                                        {' '}
                                                        (you)
                                                    </span>
                                                )}
                                            </div>
                                            <div className="wrap-anywhere text-muted-foreground">
                                                {member.email}
                                            </div>
                                            <div className="text-xs text-muted-foreground sm:hidden">
                                                {roleLabel(member.role)}
                                            </div>
                                        </TableCell>
                                        <TableCell className="hidden sm:table-cell">
                                            <Badge variant="outline">
                                                {roleLabel(member.role)}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="hidden md:table-cell">
                                            {member.specialization ?? '—'}
                                        </TableCell>
                                        <TableCell className="hidden lg:table-cell">
                                            {member.phone ?? '—'}
                                        </TableCell>
                                        <TableCell>
                                            {member.is_active ? (
                                                <Badge variant="secondary">
                                                    Active
                                                </Badge>
                                            ) : (
                                                <Badge variant="destructive">
                                                    Inactive
                                                </Badge>
                                            )}
                                        </TableCell>
                                        <TableCell className="pr-4 whitespace-normal">
                                            <div className="flex flex-wrap justify-end gap-2">
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    asChild
                                                >
                                                    <Link
                                                        href={edit(member.id)}
                                                    >
                                                        <Pencil />
                                                        Edit
                                                    </Link>
                                                </Button>

                                                {member.is_active &&
                                                    !isSelf && (
                                                        <DeactivateStaffDialog
                                                            member={member}
                                                        />
                                                    )}

                                                {!member.is_active && (
                                                    <Form
                                                        {...StaffController.activate.form(
                                                            member.id,
                                                        )}
                                                        options={{
                                                            preserveScroll: true,
                                                        }}
                                                    >
                                                        {({ processing }) => (
                                                            <Button
                                                                variant="outline"
                                                                size="sm"
                                                                disabled={
                                                                    processing
                                                                }
                                                            >
                                                                {processing && (
                                                                    <Spinner />
                                                                )}
                                                                Activate
                                                            </Button>
                                                        )}
                                                    </Form>
                                                )}
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                );
                            })}
                        </TableBody>
                    </Table>
                </div>
            </div>
        </>
    );
}

StaffIndex.layout = {
    breadcrumbs: [
        {
            title: 'Staff',
            href: index(),
        },
    ],
};
