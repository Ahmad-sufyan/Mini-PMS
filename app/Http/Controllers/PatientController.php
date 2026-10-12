<?php

namespace App\Http\Controllers;

use App\Enums\BloodGroup;
use App\Enums\Gender;
use App\Enums\Permission;
use App\Http\Requests\Patients\StorePatientRequest;
use App\Http\Requests\Patients\UpdatePatientRequest;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PatientController extends Controller
{
    /**
     * The number of patients shown per page.
     */
    private const int PER_PAGE = 15;

    /**
     * Show the paginated, searchable list of patients.
     */
    public function index(Request $request): Response
    {
        $sort = $request->string('sort')->value();

        // Invalid filter values are ignored rather than rejected, so a bad
        // or outdated link still shows the list.
        $filters = [
            'search' => $request->string('search')->trim()->value(),
            'gender' => $request->enum('gender', Gender::class)?->value,
            'sort' => in_array($sort, Patient::SORTABLE, true) ? $sort : 'name',
            'direction' => $request->query('direction') === 'desc' ? 'desc' : 'asc',
            'status' => $request->query('status') === 'deleted' && Gate::allows(Permission::DeletePatients)
                ? 'deleted'
                : 'active',
        ];

        $patients = Patient::query()
            ->select(['id', 'mrn', 'first_name', 'last_name', 'date_of_birth', 'gender', 'phone', 'allergies', 'created_at', 'deleted_at'])
            ->when($filters['status'] === 'deleted', fn (Builder $query) => $query->onlyTrashed())
            ->search($filters['search'])
            ->when($filters['gender'], fn (Builder $query, string $gender) => $query->where('gender', $gender))
            ->sortBy($filters['sort'], $filters['direction'])
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return Inertia::render('patients/index', [
            'patients' => $patients,
            'filters' => $filters,
            'genders' => Gender::options(),
        ]);
    }

    /**
     * Show the form for registering a patient.
     */
    public function create(): Response
    {
        return Inertia::render('patients/create', [
            'genders' => Gender::options(),
            'bloodGroups' => BloodGroup::options(),
        ]);
    }

    /**
     * Register a new patient.
     */
    public function store(StorePatientRequest $request): RedirectResponse
    {
        $patient = Patient::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name has been registered as :mrn.', [
            'name' => $patient->full_name,
            'mrn' => $patient->mrn,
        ])]);

        return to_route('patients.show', $patient);
    }

    /**
     * Show the patient's record.
     */
    public function show(Patient $patient): Response
    {
        return Inertia::render('patients/show', [
            'patient' => $patient,
        ]);
    }

    /**
     * Show the form for editing the patient.
     */
    public function edit(Patient $patient): Response
    {
        return Inertia::render('patients/edit', [
            'patient' => $patient,
            'genders' => Gender::options(),
            'bloodGroups' => BloodGroup::options(),
        ]);
    }

    /**
     * Update the patient's record.
     */
    public function update(UpdatePatientRequest $request, Patient $patient): RedirectResponse
    {
        $patient->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('The record for :name has been updated.', ['name' => $patient->full_name])]);

        return to_route('patients.show', $patient);
    }

    /**
     * Soft delete the patient's record.
     */
    public function destroy(Patient $patient): RedirectResponse
    {
        $patient->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('The record for :name has been deleted.', ['name' => $patient->full_name])]);

        return to_route('patients.index');
    }

    /**
     * Restore a deleted patient record.
     */
    public function restore(Patient $patient): RedirectResponse
    {
        $patient->restore();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('The record for :name has been restored.', ['name' => $patient->full_name])]);

        return to_route('patients.show', $patient);
    }
}
