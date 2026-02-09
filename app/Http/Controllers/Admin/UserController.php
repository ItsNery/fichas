<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display a paginated listing of users, excluding the user with ID 1.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $users = User::where('id', '>', 1)->latest()->paginate(15);
        return view('users.index', ['users' => $users]);
    }

    /**
     * Muestra el formulario para crear un nuevo usuario.
     * @return void
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created user in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

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
        return view('users.edit', ['user' => $user]);
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
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        $user->name  = $validated['name'];
        $user->email = $validated['email'];

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }
        $user->save();

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

        $user->delete();
        return response()->json(['success' => '¡Usuario eliminado correctamente!']);
    }
}
