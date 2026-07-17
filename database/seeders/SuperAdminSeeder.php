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
    public function run()
    {
        $user = User::firstOrCreate(
            [
                'email' => 'estadistica@puebla.gob.mx', 
            ],
            [
                'name'     => 'Super Administrador',
                'password' => Hash::make('dei-2025'),
            ]
        );
        $user->assignRole('super_admin');
    }
}
