<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_consultor_can_read_but_cannot_mutate_or_import(): void
    {
        $user = User::factory()->create();
        $user->assignRole('consultor');

        $this->actingAs($user)->get(route('admin.catalogos.index'))->assertOk();
        $this->actingAs($user)->get(route('admin.datos.index'))->assertOk();
        $this->actingAs($user)->get(route('admin.auditoria.index'))->assertOk();
        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('admin.datos.index'));

        $this->actingAs($user)->post(route('admin.catalogos.dimensions.store'), [])->assertForbidden();
        $this->actingAs($user)->post(route('admin.configuracion-fichas.store'), [])->assertForbidden();
        $this->actingAs($user)->post(route('admin.instrumentos.store'), [])->assertForbidden();
        $this->actingAs($user)->post(route('admin.import.datos.validate'), [])->assertForbidden();
        $this->actingAs($user)->get(route('admin.dashboard'))
            ->assertForbidden()
            ->assertSee('Acceso restringido');
    }

    public function test_analista_cannot_modify_catalogs_or_configuration(): void
    {
        $user = User::factory()->create();
        $user->assignRole('analista');

        $this->actingAs($user)->get(route('admin.catalogos.index'))->assertOk();
        $this->actingAs($user)->post(route('admin.catalogos.indicadores.store'), [])->assertForbidden();
        $this->actingAs($user)->post(route('admin.configuracion-fichas.store'), [])->assertForbidden();
        $this->actingAs($user)->post(route('admin.import.datos.validate'), [])->assertForbidden();
    }

    public function test_roleless_user_cannot_access_protected_admin_modules(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('admin.catalogos.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.datos.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.roles.index'))->assertForbidden();
        $this->actingAs($user)->post(route('admin.instrumentos.store'), [])->assertForbidden();
    }

    public function test_super_admin_can_manage_roles_and_permissions(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $this->actingAs($user)->get(route('admin.roles.index'))->assertOk();
        $this->actingAs($user)->get(route('admin.permissions.index'))->assertOk();
        $this->actingAs($user)->post(route('admin.roles.store'), [
            'name' => 'coordinador',
            'description' => 'Rol personalizado',
            'permissions' => ['datos.ver'],
        ])->assertRedirect(route('admin.roles.index'));

        $this->assertTrue(Role::findByName('coordinador')->hasPermissionTo('datos.ver'));
    }

    public function test_non_super_admin_cannot_manage_roles_even_with_read_only_profile(): void
    {
        $user = User::factory()->create();
        $user->assignRole('gobernanza');

        $this->actingAs($user)->get(route('admin.roles.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.permissions.index'))->assertForbidden();
    }

    public function test_system_permissions_and_super_admin_role_cannot_be_deleted(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $permission = Permission::findByName('datos.ver');
        $role = Role::findByName('super_admin');

        $this->actingAs($user)->delete(route('admin.permissions.destroy', $permission))
            ->assertSessionHasErrors('permission');
        $this->actingAs($user)->delete(route('admin.roles.destroy', $role))
            ->assertSessionHasErrors('role');

        $this->assertDatabaseHas('permissions', ['id' => $permission->id]);
        $this->assertDatabaseHas('roles', ['id' => $role->id]);
    }

    public function test_last_super_admin_cannot_remove_own_role(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $this->actingAs($user)->put(route('admin.users.update', $user), [
            'name' => $user->name,
            'email' => $user->email,
            'role' => 'consultor',
        ])->assertSessionHasErrors('role');

        $this->assertTrue($user->fresh()->hasRole('super_admin'));
    }

    public function test_user_manager_cannot_escalate_an_account_to_super_admin(): void
    {
        $role = Role::create(['name' => 'gestor_usuarios', 'guard_name' => 'web']);
        $role->givePermissionTo(['usuarios.crear', 'usuarios.asignar-roles']);
        $manager = User::factory()->create();
        $manager->assignRole($role);

        $this->actingAs($manager)->post(route('admin.users.store'), [
            'name' => 'Cuenta privilegiada',
            'email' => 'privilegiada@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'super_admin',
        ])->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'privilegiada@example.test']);
    }

    public function test_consultor_login_uses_permission_aware_landing_page(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password')]);
        $user->assignRole('consultor');

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect('/dashboard');

        $this->get('/dashboard')->assertRedirect(route('admin.datos.index'));
    }
}
