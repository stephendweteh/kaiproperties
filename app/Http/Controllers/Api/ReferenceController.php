<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MaintenanceCategoryResource;
use App\Http\Resources\PropertyResource;
use App\Http\Resources\UserResource;
use App\Models\MaintenanceCategory;
use App\Models\Property;
use App\Models\User;

class ReferenceController extends Controller
{
    public function properties()
    {
        return response()->json([
            'data' => PropertyResource::collection(
                Property::query()->where('is_active', true)->orderBy('name')->get()
            ),
        ]);
    }

    public function categories()
    {
        return response()->json([
            'data' => MaintenanceCategoryResource::collection(
                MaintenanceCategory::query()->where('is_active', true)->orderBy('name')->get()
            ),
        ]);
    }

    public function technicians()
    {
        return response()->json([
            'data' => UserResource::collection(
                User::query()
                    ->where('role', User::ROLE_TECHNICIAN)
                    ->orderBy('name')
                    ->get()
            ),
        ]);
    }
}
