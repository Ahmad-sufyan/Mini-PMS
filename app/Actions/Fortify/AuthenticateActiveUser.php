<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Timebox;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Fortify;

class AuthenticateActiveUser
{
    /**
     * Resolve the user for a login attempt, refusing deactivated accounts.
     *
     * The deactivated message is only revealed once the password has been
     * verified, and the timebox keeps failed lookups from leaking which
     * email addresses exist.
     *
     * @throws ValidationException
     */
    public function __invoke(Request $request): ?User
    {
        return (new Timebox)->call(function () use ($request): ?User {
            $user = User::query()
                ->where(Fortify::username(), $request->string(Fortify::username())->value())
                ->first();

            if (! $user || ! Hash::check($request->string('password')->value(), $user->password)) {
                return null;
            }

            if (! $user->is_active) {
                throw ValidationException::withMessages([
                    Fortify::username() => __('This account has been deactivated. Please contact the clinic administrator.'),
                ]);
            }

            return $user;
        }, 200_000);
    }
}
