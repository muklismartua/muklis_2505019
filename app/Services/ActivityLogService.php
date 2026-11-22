<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ActivityLogService
{
    public function catat(
        string $aksi,
        string $deskripsi,
        ?Model $subject = null,
        ?User $user = null,
        ?string $ip = null,
    ): ActivityLog {
        return ActivityLog::query()->create([
            'user_id' => ($user ?? Auth::user())?->getAuthIdentifier(),
            'aksi' => $aksi,
            'deskripsi' => $deskripsi,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'ip' => $ip ?? request()->ip(),
        ]);
    }
}
