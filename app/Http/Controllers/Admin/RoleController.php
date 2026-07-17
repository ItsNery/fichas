<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:roles.ver')->only(['index']);
        $this->middleware('permission:roles.crear')->only(['create', 'store']);
        $this->middleware('permission:roles.editar|roles.asignar-permisos')->only(['edit', 'update']);
        $this->middleware('permission:roles.eliminar')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $roles = Role::withCount(['users', 'permissions'])
            ->when($search, fn($query) => $query->where('name', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%"))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.roles.index', compact('roles', 'search'));
    }

    public function create()
    {
        return view('admin.roles.form', [
            'role' => new Role(['guard_name' => 'web']),
            'permissions' => Permission::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateRole($request);
        $role = Role::create([
            'name' => $validated['name'],
            'guard_name' => 'web',
            'description' => $validated['description'] ?? null,
            'is_system' => false,
        ]);
        $role->syncPermissions($validated['permissions'] ?? []);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()->route('admin.roles.index')->with('success', 'Rol creado correctamente.');
    }

    public function edit(Role $role)
    {
        $role->load('permissions');
        return view('admin.roles.form', [
            'role' => $role,
            'permissions' => Permission::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Role $role)
    {
        $validated = $this->validateRole($request, $role);
        $role->update([
            'name' => $role->is_system ? $role->name : $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);
        $role->syncPermissions($role->name === 'super_admin'
            ? Permission::all()
            : ($validated['permissions'] ?? []));
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()->route('admin.roles.index')->with('success', 'Rol actualizado correctamente.');
    }

    public function destroy(Role $role)
    {
        if ($role->is_system || $role->name === 'super_admin') {
            return back()->withErrors(['role' => 'Este rol del sistema no puede eliminarse.']);
        }
        if ($role->users()->exists()) {
            return back()->withErrors(['role' => 'No puede eliminarse un rol que aún tiene usuarios asignados.']);
        }

        $role->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return back()->with('success', 'Rol eliminado correctamente.');
    }

    private function validateRole(Request $request, ?Role $role = null): array
    {
        return $request->validate([
            'name' => [
                'required', 'string', 'max:100', 'regex:/^[a-z0-9_-]+$/',
                Rule::unique('roles', 'name')->where('guard_name', 'web')->ignore($role?->id),
            ],
            'description' => 'nullable|string|max:255',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);
    }
}
