import { Form } from '@inertiajs/react';
import StaffController from '@/actions/App/Http/Controllers/StaffController';
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
import type { StaffMember } from '@/types';

export default function DeactivateStaffDialog({
    member,
}: {
    member: StaffMember;
}) {
    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button
                    variant="ghost"
                    size="sm"
                    className="text-red-600 hover:text-red-700 dark:text-red-400"
                    data-test={`deactivate-staff-${member.id}`}
                >
                    Deactivate
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogTitle>Deactivate {member.name}?</DialogTitle>
                <DialogDescription>
                    They will be signed out and won't be able to log in until
                    you reactivate their account. Their records are kept.
                </DialogDescription>

                <Form
                    {...StaffController.deactivate.form(member.id)}
                    options={{
                        preserveScroll: true,
                    }}
                >
                    {({ processing }) => (
                        <DialogFooter className="gap-2">
                            <DialogClose asChild>
                                <Button variant="secondary">Cancel</Button>
                            </DialogClose>

                            <Button
                                variant="destructive"
                                disabled={processing}
                                asChild
                            >
                                <button
                                    type="submit"
                                    data-test="confirm-deactivate-staff-button"
                                >
                                    Deactivate
                                </button>
                            </Button>
                        </DialogFooter>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
