<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:permisos.ver')->only(['index']);
        $this->middleware('permission:permisos.crear')->only(['create', 'store']);
        $this->middleware('permission:permisos.editar')->only(['edit', 'update']);
        $this->middleware('permission:permisos.eliminar')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $permissions = Permission::withCount('roles')
            ->when($search, fn($query) => $query->where('name', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%"))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('admin.permissions.index', compact('permissions', 'search'));
    }

    public function create()
    {
        return view('admin.permissions.form', ['permission' => new Permission(['guard_name' => 'web'])]);
    }

    public function store(Request $request)
    {
        $validated = $this->validatePermission($request);
        Permission::create([
            'name' => $validated['name'],
            'guard_name' => 'web',
            'description' => $validated['description'] ?? null,
            'is_system' => false,
        ]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()->route('admin.permissions.index')->with('success', 'Permiso creado correctamente.');
    }

    public function edit(Permission $permission)
    {
        return view('admin.permissions.form', compact('permission'));
    }

    public function update(Request $request, Permission $permission)
    {
        $validated = $this->validatePermission($request, $permission);
        $permission->update([
            'name' => $permission->is_system ? $permission->name : $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()->route('admin.permissions.index')->with('success', 'Permiso actualizado correctamente.');
    }

    public function destroy(Permission $permission)
    {
        if ($permission->is_system) {
            return back()->withErrors(['permission' => 'Los permisos del sistema no pueden eliminarse.']);
        }
        $permission->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return back()->with('success', 'Permiso eliminado correctamente.');
    }

    private function validatePermission(Request $request, ?Permission $permission = null): array
    {
        return $request->validate([
            'name' => [
                'required', 'string', 'max:125', 'regex:/^[a-z0-9_.-]+$/',
                Rule::unique('permissions', 'name')->where('guard_name', 'web')->ignore($permission?->id),
            ],
            'description' => 'nullable|string|max:255',
        ]);
    }
}
