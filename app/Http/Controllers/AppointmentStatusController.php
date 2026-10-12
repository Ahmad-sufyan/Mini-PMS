<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Support\ClinicSchedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AppointmentStatusController extends Controller
{
    /**
     * Move an appointment to a new status (check in, complete, cancel or
     * mark as a no-show).
     *
     * @throws ValidationException
     */
    public function __invoke(Appointment $appointment, AppointmentStatus $status, ClinicSchedule $clinic): RedirectResponse
    {
        if (! $appointment->status->canTransitionTo($status)) {
            throw ValidationException::withMessages([
                'status' => __('A :from appointment can\'t be changed to :to.', [
                    'from' => strtolower($appointment->status->label()),
                    'to' => strtolower($status->label()),
                ]),
            ]);
        }

        $appointment->status = $status;
        $appointment->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':patient\'s :time appointment is now :status.', [
            'patient' => $appointment->patient->full_name,
            'time' => $clinic->toLocal($appointment->starts_at)->format('H:i'),
            'status' => strtolower($status->label()),
        ])]);

        return back();
    }
}
