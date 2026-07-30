<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\CategoryStoreRequest;
use App\Http\Requests\Api\Admin\CategoryUpdateRequest;
use App\Http\Resources\MaintenanceCategoryResource;
use App\Models\MaintenanceCategory;
use Illuminate\Database\QueryException;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return MaintenanceCategoryResource::collection(MaintenanceCategory::query()->orderBy('name')->paginate(20));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CategoryStoreRequest $request)
    {
        $validated = $request->validated();

        $validated['is_active'] = $request->has('is_active')
            ? $request->boolean('is_active')
            : true;

        $category = MaintenanceCategory::create($validated);

        return response()->json([
            'message' => 'Category created successfully.',
            'data' => MaintenanceCategoryResource::make($category),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(MaintenanceCategory $category)
    {
        return response()->json([
            'data' => MaintenanceCategoryResource::make($category),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CategoryUpdateRequest $request, MaintenanceCategory $category)
    {
        $validated = $request->validated();

        if ($request->has('is_active')) {
            $validated['is_active'] = $request->boolean('is_active');
        }

        $category->update($validated);

        return response()->json([
            'message' => 'Category updated successfully.',
            'data' => MaintenanceCategoryResource::make($category->fresh()),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MaintenanceCategory $category)
    {
        try {
            $category->delete();
        } catch (QueryException $exception) {
            return response()->json([
                'message' => 'Category cannot be deleted because it is linked to existing tickets.',
            ], 422);
        }

        return response()->json([
            'message' => 'Category deleted successfully.',
        ]);
    }
}
