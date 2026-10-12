<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\Staff\StoreStaffRequest;
use App\Http\Requests\Staff\UpdateStaffRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class StaffController extends Controller
{
    /**
     * The staff attributes exposed to the frontend.
     *
     * @var list<string>
     */
    private const array ATTRIBUTES = ['id', 'name', 'email', 'role', 'specialization', 'phone', 'is_active'];

    /**
     * Show the list of staff accounts.
     */
    public function index(): Response
    {
        return Inertia::render('staff/index', [
            'staff' => User::query()
                ->orderByDesc('is_active')
                ->orderBy('name')
                ->get(self::ATTRIBUTES),
            'roles' => Role::options(),
        ]);
    }

    /**
     * Show the form for creating a staff account.
     */
    public function create(): Response
    {
        return Inertia::render('staff/create', [
            'roles' => Role::options(),
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
        ]);
    }

    /**
     * Store a new staff account.
     */
    public function store(StoreStaffRequest $request): RedirectResponse
    {
        $user = User::create($request->validated());

        // Accounts are created by the admin for known staff, so there is no
        // need for them to verify their email address.
        $user->markEmailAsVerified();

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name has been added to the staff.', ['name' => $user->name])]);

        return to_route('staff.index');
    }

    /**
     * Show the form for editing a staff account.
     */
    public function edit(Request $request, User $user): Response
    {
        return Inertia::render('staff/edit', [
            'staffMember' => $user->only(self::ATTRIBUTES),
            'isSelf' => $user->is($request->user()),
            'roles' => Role::options(),
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
        ]);
    }

    /**
     * Update a staff account.
     */
    public function update(UpdateStaffRequest $request, User $user): RedirectResponse
    {
        $user->fill($request->safe()->except(['password']));

        // The specialization is excluded from the validated data for roles
        // other than doctor, so this clears it when the role changes.
        $user->specialization = $request->validated('specialization');

        if ($request->filled('password')) {
            $user->password = $request->validated('password');
        }

        $user->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name has been updated.', ['name' => $user->name])]);

        return to_route('staff.index');
    }

    /**
     * Deactivate a staff account so it can no longer sign in.
     */
    public function deactivate(User $user): RedirectResponse
    {
        $user->update(['is_active' => false]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name has been deactivated.', ['name' => $user->name])]);

        return to_route('staff.index');
    }

    /**
     * Reactivate a previously deactivated staff account.
     */
    public function activate(User $user): RedirectResponse
    {
        $user->update(['is_active' => true]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name has been reactivated.', ['name' => $user->name])]);

        return to_route('staff.index');
    }
}
