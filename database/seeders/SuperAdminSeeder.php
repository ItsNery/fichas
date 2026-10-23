<?php
namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
/**
 * Run the database seeds.
 *
 * @return void
 */
    public function run(): void
    {
        $user = User::where('email', 'estadistica@puebla.gob.mx')->first();

        if (!$user) {
            $password = config('initial_admin.password');

            if (!is_string($password) || trim($password) === '') {
                throw new \RuntimeException(
                    'Defina INITIAL_ADMIN_PASSWORD antes de ejecutar SuperAdminSeeder.',
                );
            }

            $user = User::create([
                'name'     => 'Super Administrador',
                'email'    => 'estadistica@puebla.gob.mx',
                'password' => Hash::make($password),
            ]);
        }

        $user->assignRole('super_admin');
    }
}
