<?php

namespace App\Actions\Owner;

use App\Models\User;
use App\Policies\OwnerStaffPolicy;
use Illuminate\Support\Facades\Password;

class SendStaffPasswordResetAction
{
    public function execute(User $owner, User $subject): string
    {
        abort_unless(app(OwnerStaffPolicy::class)->view($owner, $subject), 404);
        return Password::sendResetLink(['email' => $subject->email]);
    }
}
