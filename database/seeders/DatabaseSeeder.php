<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $roles = [
            'Quality Inspector' => ['email' => 'inspector@cpoint.test', 'role' => 'quality_inspector'],
            'Product Manager' => ['email' => 'manager@cpoint.test', 'role' => 'product_manager'],
            'System Admin' => ['email' => 'admin@cpoint.test', 'role' => 'system_admin'],
            'Shoe Constructor' => ['email' => 'constructor@cpoint.test', 'role' => 'shoe_constructor'],
        ];

        foreach ($roles as $name => $attrs) {
            User::query()->updateOrCreate(
                ['email' => $attrs['email']],
                [
                    'name' => $name,
                    'role' => $attrs['role'],
                    'password' => bcrypt('password'),
                    'email_verified_at' => now(),
                ]
            );
        }

        $this->call(DemoDataSeeder::class);
    }
}
