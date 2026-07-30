<?php

use App\Models\User;
use App\Services\FirebasePushService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('push:test {userId?} {--title=KAI Test Notification} {--body=This is a Firebase push test from Kai Properties.}', function (
    FirebasePushService $firebasePushService,
): int {
    if (! $firebasePushService->enabled()) {
        $this->error('Firebase push is not configured. Set FIREBASE_* variables first.');

        return self::FAILURE;
    }

    $userId = $this->argument('userId');

    $user = $userId
        ? User::query()->find((int) $userId)
        : User::query()
            ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_OPERATIONS_MANAGER])
            ->orderBy('id')
            ->first();

    if (! $user) {
        $this->error('No target user found. Pass a valid userId.');

        return self::FAILURE;
    }

    $title = (string) $this->option('title');
    $body = (string) $this->option('body');

    $firebasePushService->sendToUsers(
        [$user],
        $title,
        $body,
        [
            'type' => 'push_test',
            'link' => route('dashboard'),
        ],
        route('dashboard')
    );

    $this->info("Push test dispatched for user #{$user->id} ({$user->email}).");

    return self::SUCCESS;
})->purpose('Send a Firebase push test to a specific user (or first admin/ops manager)');

Schedule::command('tickets:mark-overdue')->everyMinute();
