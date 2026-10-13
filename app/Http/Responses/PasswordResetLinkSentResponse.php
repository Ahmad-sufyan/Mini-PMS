<?php

namespace App\Http\Responses;

use Illuminate\Support\Facades\Password;
use Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse;
use Laravel\Fortify\Http\Responses\SuccessfulPasswordResetLinkRequestResponse;

/**
 * Answer a reset link request that didn't send an email (an unknown or
 * throttled address) exactly as one that did, so the form can't be used to
 * find out which emails belong to staff.
 */
class PasswordResetLinkSentResponse extends SuccessfulPasswordResetLinkRequestResponse implements FailedPasswordResetLinkRequestResponse
{
    /**
     * Create a new response instance. It takes no status: whatever the
     * broker reported, the user is told the link is on its way.
     */
    public function __construct()
    {
        parent::__construct(Password::RESET_LINK_SENT);
    }
}
