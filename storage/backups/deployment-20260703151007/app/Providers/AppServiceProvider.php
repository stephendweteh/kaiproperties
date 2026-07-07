<?php

namespace App\Providers;

use App\Models\AuditLog;
use App\Models\MaintenanceCategory;
use App\Models\Property;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        foreach ([Property::class, MaintenanceCategory::class, User::class] as $modelClass) {
            $modelClass::created(fn (Model $model) => $this->logAudit($model, AuditLog::ACTION_CREATED));
            $modelClass::updated(fn (Model $model) => $this->logAudit($model, AuditLog::ACTION_UPDATED));
            $modelClass::deleted(fn (Model $model) => $this->logAudit($model, AuditLog::ACTION_DELETED));
        }
    }

    private function logAudit(Model $model, string $action): void
    {
        if ($this->shouldSkipAuditLogging($model)) {
            return;
        }

        $actorId = Auth::id();

        AuditLog::query()->create([
            'auditable_type' => $model::class,
            'auditable_id' => $model->getKey(),
            'action' => $action,
            'actor_id' => $actorId,
            'meta' => [
                'name' => $model->getAttribute('name'),
                'email' => $model->getAttribute('email'),
                'code' => $model->getAttribute('code'),
            ],
            'created_at' => now(),
        ]);
    }

    private function shouldSkipAuditLogging(Model $model): bool
    {
        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            return true;
        }

        if ($model instanceof User && $model->wasRecentlyCreated && ! Auth::check()) {
            return true;
        }

        return false;
    }
}
