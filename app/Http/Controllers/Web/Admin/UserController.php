<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
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
            ->where('auditable_type', User::class);

        if ($auditAction !== '') {
            $auditQuery->where('action', $auditAction);
        }

        return view('admin.users.index', [
            'users' => User::query()->orderBy('name')->paginate(20)->withQueryString(),
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
        return view('admin.users.create', [
            'roles' => [User::ROLE_TENANT, User::ROLE_ADMIN, User::ROLE_OPERATIONS_MANAGER, User::ROLE_TECHNICIAN, User::ROLE_APPROVER],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['required', 'in:tenant,admin,operations_manager,technician,approver'],
            'password' => ['required', Password::min(8)],
            'profile_photo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
        ]);

        $photo = $request->file('profile_photo');
        unset($validated['profile_photo']);

        $user = User::create($validated);

        if ($photo) {
            $path = $photo->store('users/profile-photos', 'public');
            $user->update(['profile_photo_path' => $path]);
        }

        return redirect()->route('admin.users.index')->with('success', 'User created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function edit(User $user)
    {
        return view('admin.users.edit', [
            'user' => $user,
            'roles' => [User::ROLE_TENANT, User::ROLE_ADMIN, User::ROLE_OPERATIONS_MANAGER, User::ROLE_TECHNICIAN, User::ROLE_APPROVER],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['required', 'in:tenant,admin,operations_manager,technician,approver'],
            'password' => ['nullable', Password::min(8)],
            'profile_photo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'remove_profile_photo' => ['nullable', 'boolean'],
        ]);

        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        $newPhoto = $request->file('profile_photo');
        $removePhoto = $request->boolean('remove_profile_photo');

        unset($validated['profile_photo'], $validated['remove_profile_photo']);

        $currentPhotoPath = $user->profile_photo_path;

        if ($removePhoto && $currentPhotoPath) {
            Storage::disk('public')->delete($currentPhotoPath);
            $validated['profile_photo_path'] = null;
            $currentPhotoPath = null;
        }

        if ($newPhoto) {
            if ($currentPhotoPath) {
                Storage::disk('public')->delete($currentPhotoPath);
            }

            $validated['profile_photo_path'] = $newPhoto->store('users/profile-photos', 'public');
        }

        $user->update($validated);

        return redirect()->route('admin.users.index')->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        $photoPath = $user->profile_photo_path;

        try {
            $user->delete();
        } catch (QueryException $exception) {
            return redirect()
                ->route('admin.users.index')
                ->withErrors(['User cannot be deleted because the user is linked to existing tickets or records.']);
        }

        if ($photoPath) {
            Storage::disk('public')->delete($photoPath);
        }

        return redirect()->route('admin.users.index')->with('success', 'User deleted successfully.');
    }
}
