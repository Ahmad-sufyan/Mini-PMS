import { Form } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import PatientController from '@/actions/App/Http/Controllers/PatientController';
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
import type { Patient } from '@/types';

export default function DeletePatientDialog({ patient }: { patient: Patient }) {
    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button variant="outline" data-test="delete-patient-button">
                    <Trash2 />
                    Delete
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogTitle>Delete {patient.full_name}?</DialogTitle>
                <DialogDescription>
                    The record ({patient.mrn}) is hidden from the patient list
                    but kept, so an administrator can restore it later.
                </DialogDescription>

                <Form {...PatientController.destroy.form(patient.id)}>
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
                                    data-test="confirm-delete-patient-button"
                                >
                                    Delete record
                                </button>
                            </Button>
                        </DialogFooter>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
