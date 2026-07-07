<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Property;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PropertyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $auditAction = request()->string('audit_action')->toString();
        $allowedActions = ['created', 'updated', 'deleted'];

        if (! in_array($auditAction, $allowedActions, true)) {
            $auditAction = '';
        }

        $auditQuery = AuditLog::query()
            ->with('actor:id,name')
            ->where('auditable_type', Property::class);

        if ($auditAction !== '') {
            $auditQuery->where('action', $auditAction);
        }

        return view('admin.properties.index', [
            'properties' => Property::query()
                ->with('customer:id,name')
                ->orderBy('name')
                ->paginate(20)
                ->withQueryString(),
            'recentAudits' => $auditQuery
                ->latest('created_at')
                ->latest('id')
                ->limit(15)
                ->get(),
            'auditAction' => $auditAction,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.properties.create', [
            'customers' => Customer::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:100', 'unique:properties,code'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        Property::create($validated);

        return redirect()->route('admin.properties.index')->with('success', 'Property created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function edit(Property $property)
    {
        return view('admin.properties.edit', [
            'property' => $property,
            'customers' => Customer::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Property $property)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:100', Rule::unique('properties', 'code')->ignore($property->id)],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $property->update($validated);

        return redirect()->route('admin.properties.index')->with('success', 'Property updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Property $property)
    {
        try {
            $property->delete();
        } catch (QueryException $exception) {
            return redirect()
                ->route('admin.properties.index')
                ->withErrors(['Property cannot be deleted because it is linked to existing tickets.']);
        }

        return redirect()->route('admin.properties.index')->with('success', 'Property deleted successfully.');
    }
}
