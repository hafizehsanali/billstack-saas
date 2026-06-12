<?php

namespace Tests;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Treat legacy tenant test users as owners unless a test assigns a specific role.
     */
    public function actingAs(Authenticatable $user, $guard = null)
    {
        if ($user instanceof User && $user->tenant_id && ! $user->isPlatformAdmin()) {
            $hasNoRole = $user->roles()->doesntExist();

            if ($hasNoRole || $user->getAllPermissions()->isEmpty()) {
                $this->seed(RolePermissionSeeder::class);
            }

            if ($hasNoRole) {
                $user->assignRole('owner');
            }
        }

        return parent::actingAs($user, $guard);
    }
}
