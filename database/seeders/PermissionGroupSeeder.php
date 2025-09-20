<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\PermissionGroup;

class PermissionGroupSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create groups
        $userMgmt = PermissionGroup::create(['name' => 'User Management']);
        $productMgmt = PermissionGroup::create(['name' => 'Product Management']);

        // 2. Create roles
        $adminRole = Role::create(['name' => 'admin']);
        $userRole = Role::create(['name' => 'user']);

        // 3. Create permissions and assign group_id
        $permissions = [
            $userMgmt->id => ['view users', 'create users', 'edit users', 'delete users'],
            $productMgmt->id => ['view products', 'create products', 'edit products', 'delete products'],
        ];

        foreach ($permissions as $groupId => $perms) {
            foreach ($perms as $perm) {
                $permission = Permission::create([
                    'name' => $perm,
                    'group_id' => $groupId,
                ]);

                // Assign all permissions to admin role
                $adminRole->givePermissionTo($permission);
            }
        }
    }
}
