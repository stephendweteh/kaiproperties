<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Customer;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    public function index()
    {
        $auditAction = request()->string('audit_action')->toString();
        $allowedActions = ['created', 'updated', 'deleted'];

        if (! in_array($auditAction, $allowedActions, true)) {
            $auditAction = '';
        }

        $auditQuery = AuditLog::query()
            ->with('actor:id,name')
            ->where('auditable_type', Customer::class);

        if ($auditAction !== '') {
            $auditQuery->where('action', $auditAction);
        }

        return view('admin.customers.index', [
            'customers' => Customer::query()
                ->withCount('properties')
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

    public function create()
    {
        return view('admin.customers.create');
    }

    public function show(Customer $customer)
    {
        $customer->load(['properties' => fn ($query) => $query->orderBy('name')]);

        return view('admin.customers.show', [
            'customer' => $customer,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:customers,email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        Customer::create($validated);

        return redirect()->route('admin.customers.index')->with('success', 'Customer created successfully.');
    }

    public function edit(Customer $customer)
    {
        return view('admin.customers.edit', ['customer' => $customer]);
    }

    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('customers', 'email')->ignore($customer->id)],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $customer->update($validated);

        return redirect()->route('admin.customers.index')->with('success', 'Customer updated successfully.');
    }

    public function destroy(Customer $customer)
    {
        try {
            $customer->delete();
        } catch (QueryException $exception) {
            return redirect()
                ->route('admin.customers.index')
                ->withErrors(['Customer cannot be deleted because it is linked to existing properties.']);
        }

        return redirect()->route('admin.customers.index')->with('success', 'Customer deleted successfully.');
    }
}
