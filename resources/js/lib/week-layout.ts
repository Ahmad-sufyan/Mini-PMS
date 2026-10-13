import type { Appointment } from '@/types';

export type PlacedAppointment = {
    appointment: Appointment;
    /** The column the appointment sits in, within its group of overlaps. */
    lane: number;
    /** How many columns its group of overlapping appointments needs. */
    lanes: number;
};

/**
 * Lay out one day's appointments side by side where they overlap (e.g.
 * different doctors at the same time, or a cancelled slot that was rebooked).
 */
export function layoutDay(appointments: Appointment[]): PlacedAppointment[] {
    const sorted = [...appointments].sort(
        (a, b) =>
            a.start_time.localeCompare(b.start_time) ||
            a.end_time.localeCompare(b.end_time),
    );

    const placed: PlacedAppointment[] = [];
    let group: PlacedAppointment[] = [];
    let laneEnds: string[] = [];
    let groupEnd = '';

    const closeGroup = () => {
        for (const item of group) {
            item.lanes = laneEnds.length;
        }

        placed.push(...group);
        group = [];
        laneEnds = [];
        groupEnd = '';
    };

    for (const appointment of sorted) {
        if (group.length > 0 && appointment.start_time >= groupEnd) {
            closeGroup();
        }

        let lane = laneEnds.findIndex((end) => end <= appointment.start_time);

        if (lane === -1) {
            lane = laneEnds.length;
            laneEnds.push(appointment.end_time);
        } else {
            laneEnds[lane] = appointment.end_time;
        }

        group.push({ appointment, lane, lanes: 1 });

        if (appointment.end_time > groupEnd) {
            groupEnd = appointment.end_time;
        }
    }

    closeGroup();

    return placed;
}
