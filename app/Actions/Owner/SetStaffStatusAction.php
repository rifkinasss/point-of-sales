<?php

namespace App\Actions\Owner;

use App\Enums\ShiftStatus;
use App\Models\CashierShift;
use App\Models\User;
use App\Policies\OwnerStaffPolicy;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SetStaffStatusAction
{
    public function execute(User $owner, User $subject, bool $active): User
    {
        abort_unless(app(OwnerStaffPolicy::class)->view($owner, $subject), 404);
        if (! $active && CashierShift::query()->where('user_id', $subject->id)->where('status', ShiftStatus::Open)->exists()) {
            throw ValidationException::withMessages(['user' => 'Pengguna masih memiliki shift aktif. Tutup shift terlebih dahulu sebelum menonaktifkan akses.']);
        }

        return DB::transaction(function () use ($subject, $active): User {
            $subject->forceFill(['is_active' => $active])->save();
            return $subject->refresh();
        });
    }
}
