<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:usuarios.ver')->only(['index']);
        $this->middleware('permission:usuarios.crear')->only(['create', 'store']);
        $this->middleware('permission:usuarios.editar')->only(['edit', 'update']);
        $this->middleware('permission:usuarios.eliminar')->only(['destroy']);
    }
    /**
     * Display a paginated listing of users, excluding the user with ID 1.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $users = User::with('roles')->latest()->paginate(15);
        $roles = auth()->user()->can('usuarios.asignar-roles') ? Role::orderBy('name')->get() : collect();
        return view('users.index', compact('users', 'roles'));
    }

    /**
     * Muestra el formulario para crear un nuevo usuario.
     * @return void
     */
    public function create()
    {
        $roles = auth()->user()->can('usuarios.asignar-roles') ? Role::orderBy('name')->get() : collect();
        return view('users.create', compact('roles'));
    }

    /**
     * Store a newly created user in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $this->authorizeRoleAssignment($request);
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role'     => 'nullable|string|exists:roles,name',
        ]);

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        if (!empty($validated['role'])) {
            $user->assignRole($validated['role']);
        }

        return response()->json(['success' => '¡Usuario creado correctamente!', 'user' => $user]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\User  $user
     * @return void
     */
    public function show(User $user)
    {
        //
    }

    /**
     * Muestra el formulario para editar un usuario.
     *
     * @param  \App\Models\User  $user // Asume que el modelo se llama 'User'
     * @return \Illuminate\View\View
     */
    public function edit(User $user)
    {
        $this->ensureCanManageTarget($user);
        $roles = auth()->user()->can('usuarios.asignar-roles') ? Role::orderBy('name')->get() : collect();
        return view('users.edit', compact('user', 'roles'));
    }

    /**
     * Update the specified user in storage via API/AJAX.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user // Asume que el modelo se llama 'User'
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, User $user)
    {
        $this->ensureCanManageTarget($user);
        $this->authorizeRoleAssignment($request, $user);
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:8|confirmed',
            'role'     => 'nullable|string|exists:roles,name',
        ]);

        $user->name  = $validated['name'];
        $user->email = $validated['email'];

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }
        $user->save();

        if ($request->user()->can('usuarios.asignar-roles')) {
            if (!empty($validated['role'])) {
                $user->syncRoles([$validated['role']]);
            } else {
                $user->syncRoles([]);
            }
        }

        return response()->json(['success' => '¡Usuario actualizado correctamente!', 'user' => $user]);
    }

    /**
     * Remove the specified user from storage via API/AJAX.
     *
     * @param  \App\Models\User  $user // Asume que el modelo se llama 'User'
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(User $user)
    {
        if (auth()->id() == $user->id) {
            return response()->json(['error' => 'No puedes eliminar tu propio usuario.'], 403);
        }

        $this->ensureCanManageTarget($user);
        if ($user->hasRole('super_admin') && User::role('super_admin')->count() <= 1) {
            return response()->json(['error' => 'No puedes eliminar al último superadministrador.'], 422);
        }

        $user->delete();
        return response()->json(['success' => '¡Usuario eliminado correctamente!']);
    }

    private function authorizeRoleAssignment(Request $request, ?User $target = null): void
    {
        if (!$request->has('role')) {
            return;
        }
        abort_unless($request->user()->can('usuarios.asignar-roles'), 403);
        if ($request->input('role') === 'super_admin') {
            abort_unless($request->user()->hasRole('super_admin'), 403);
        }
        if ($target?->hasRole('super_admin')
            && $request->input('role') !== 'super_admin'
            && User::role('super_admin')->count() <= 1) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'role' => 'No puedes degradar al último superadministrador.',
            ]);
        }
    }

    private function ensureCanManageTarget(User $target): void
    {
        if ($target->hasRole('super_admin')) {
            abort_unless(auth()->user()->hasRole('super_admin'), 403);
        }
    }
}
