<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ExpenseManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_expense_can_be_created(): void
    {
        $user = $this->ownerUser();

        $this->actingAs($user);

        $this
            ->post(route('expenses.store'), [
                'title' => 'Shop Rent',
                'category' => 'Rent',
                'amount' => 25000,
                'expense_date' => '2026-06-02',
                'notes' => 'Monthly rent',
            ])
            ->assertRedirect(route('expenses.index'));

        $this->assertDatabaseHas('expenses', [
            'tenant_id' => $user->tenant_id,
            'title' => 'Shop Rent',
            'category' => 'Rent',
            'amount' => 25000,
        ]);
    }

    public function test_expense_can_be_updated(): void
    {
        $user = $this->ownerUser();

        $this->actingAs($user);

        $expense = Expense::create([
            'tenant_id' => $user->tenant_id,
            'title' => 'Old Expense',
            'category' => 'General',
            'amount' => 1000,
            'expense_date' => '2026-06-01',
        ]);

        $this
            ->put(route('expenses.update', $expense), [
                'title' => 'Updated Expense',
                'category' => 'Utilities',
                'amount' => 1500,
                'expense_date' => '2026-06-02',
                'notes' => 'Updated note',
            ])
            ->assertRedirect(route('expenses.index'));

        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'title' => 'Updated Expense',
            'category' => 'Utilities',
            'amount' => 1500,
            'notes' => 'Updated note',
        ]);
    }

    public function test_expense_can_be_deleted(): void
    {
        $user = $this->ownerUser();

        $this->actingAs($user);

        $expense = Expense::create([
            'tenant_id' => $user->tenant_id,
            'title' => 'Delete Expense',
            'category' => 'General',
            'amount' => 1000,
            'expense_date' => '2026-06-01',
        ]);

        $this
            ->delete(route('expenses.destroy', $expense))
            ->assertRedirect();

        $this->assertSoftDeleted('expenses', [
            'id' => $expense->id,
        ]);
    }

    private function ownerUser(): User
    {
        $tenant = Tenant::create([
            'name' => 'Demo Store',
            'slug' => 'demo-store',
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        Role::create(['name' => 'owner']);
        $user->assignRole('owner');

        return $user;
    }
}
