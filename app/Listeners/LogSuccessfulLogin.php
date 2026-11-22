<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Auth\Events\Login;

class LogSuccessfulLogin
{
    public function __construct(private readonly ActivityLogService $activityLogService) {}

    public function handle(Login $event): void
    {
        if (! ($event->user instanceof User)) {
            return;
        }

        $this->activityLogService->catat(
            'login',
            "Pengguna {$event->user->name} berhasil masuk.",
            $event->user,
            $event->user,
        );
    }
}
