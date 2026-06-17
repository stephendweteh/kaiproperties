<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\MaintenanceCategory;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
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
            ->where('auditable_type', MaintenanceCategory::class);

        if ($auditAction !== '') {
            $auditQuery->where('action', $auditAction);
        }

        return view('admin.categories.index', [
            'categories' => MaintenanceCategory::query()->orderBy('name')->paginate(20)->withQueryString(),
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
        return view('admin.categories.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:maintenance_categories,name'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        MaintenanceCategory::create($validated);

        return redirect()->route('admin.categories.index')->with('success', 'Category created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function edit(MaintenanceCategory $category)
    {
        return view('admin.categories.edit', ['category' => $category]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, MaintenanceCategory $category)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('maintenance_categories', 'name')->ignore($category->id)],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $category->update($validated);

        return redirect()->route('admin.categories.index')->with('success', 'Category updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MaintenanceCategory $category)
    {
        try {
            $category->delete();
        } catch (QueryException $exception) {
            return redirect()
                ->route('admin.categories.index')
                ->withErrors(['Category cannot be deleted because it is linked to existing tickets.']);
        }

        return redirect()->route('admin.categories.index')->with('success', 'Category deleted successfully.');
    }
}
