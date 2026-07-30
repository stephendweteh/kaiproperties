<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\PropertyStoreRequest;
use App\Http\Requests\Api\Admin\PropertyUpdateRequest;
use App\Http\Resources\PropertyResource;
use App\Models\Property;
use Illuminate\Database\QueryException;

class PropertyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return PropertyResource::collection(Property::query()->orderBy('name')->paginate(20));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PropertyStoreRequest $request)
    {
        $validated = $request->validated();

        $validated['is_active'] = $request->has('is_active')
            ? $request->boolean('is_active')
            : true;

        $property = Property::create($validated);

        return response()->json([
            'message' => 'Property created successfully.',
            'data' => PropertyResource::make($property),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Property $property)
    {
        return response()->json([
            'data' => PropertyResource::make($property),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PropertyUpdateRequest $request, Property $property)
    {
        $validated = $request->validated();

        if ($request->has('is_active')) {
            $validated['is_active'] = $request->boolean('is_active');
        }

        $property->update($validated);

        return response()->json([
            'message' => 'Property updated successfully.',
            'data' => PropertyResource::make($property->fresh()),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Property $property)
    {
        try {
            $property->delete();
        } catch (QueryException $exception) {
            return response()->json([
                'message' => 'Property cannot be deleted because it is linked to existing tickets.',
            ], 422);
        }

        return response()->json([
            'message' => 'Property deleted successfully.',
        ]);
    }
}
