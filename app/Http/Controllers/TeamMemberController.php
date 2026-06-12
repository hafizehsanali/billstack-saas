<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTeamMemberRequest;
use App\Http\Requests\UpdateTeamMemberRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class TeamMemberController extends Controller
{
    private const MANAGEABLE_ROLES = [
        'manager' => 'Manager',
        'accountant' => 'Accountant',
        'cashier' => 'Cashier',
        'inventory_staff' => 'Inventory Staff',
    ];

    private const ROLE_DESCRIPTIONS = [
        'manager' => 'Can help supervise day-to-day store operations.',
        'accountant' => 'Can manage purchases, expenses, payments, and reports.',
        'cashier' => 'Can handle POS billing, customers, and invoice payments.',
        'inventory_staff' => 'Can manage products, stock, suppliers, and purchases.',
    ];

    public function index(): View
    {
        $tenant = auth()->user()->tenant()->with('activeSubscription.plan')->firstOrFail();
        $members = $tenant->users()->with('roles')->latest()->paginate(10);
        $userLimit = $tenant->activeSubscription?->plan?->user_limit;
        $activeUserCount = $tenant->users()->where('is_active', true)->count();
        $inactiveUserCount = $tenant->users()->where('is_active', false)->count();

        return view('team.index', [
            'members' => $members,
            'tenant' => $tenant,
            'roleDescriptions' => self::ROLE_DESCRIPTIONS,
            'userLimit' => $userLimit,
            'activeUserCount' => $activeUserCount,
            'inactiveUserCount' => $inactiveUserCount,
            'totalUserCount' => $activeUserCount + $inactiveUserCount,
        ]);
    }

    public function create(): View
    {
        return view('team.create', [
            'roles' => self::MANAGEABLE_ROLES,
            'roleDescriptions' => self::ROLE_DESCRIPTIONS,
        ]);
    }

    public function store(StoreTeamMemberRequest $request): RedirectResponse
    {
        $tenant = auth()->user()->tenant()->with('activeSubscription.plan')->firstOrFail();
        $userLimit = $tenant->activeSubscription?->plan?->user_limit;
        $activeUserCount = $tenant->users()->where('is_active', true)->count();

        if ($userLimit !== null && $activeUserCount >= $userLimit) {
            return back()
                ->withInput()
                ->withErrors([
                    'team' => 'Your current plan allows '.$userLimit.' active users. Upgrade the plan before adding more staff.',
                ]);
        }

        $data = $request->validated();
        $member = User::create([
            'tenant_id' => $tenant->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'is_active' => true,
        ]);

        Role::findOrCreate($data['role']);
        $member->assignRole($data['role']);

        return redirect()
            ->route('team.index')
            ->with('success', 'Team member created successfully.');
    }

    public function edit(User $teamMember): View
    {
        $this->authorizeTenantMember($teamMember);

        return view('team.edit', [
            'member' => $teamMember,
            'roles' => self::MANAGEABLE_ROLES,
            'roleDescriptions' => self::ROLE_DESCRIPTIONS,
            'currentRole' => $teamMember->roles()->first()?->name,
        ]);
    }

    public function update(UpdateTeamMemberRequest $request, User $teamMember): RedirectResponse
    {
        $this->authorizeTenantMember($teamMember);

        $data = $request->validated();
        $updates = [
            'name' => $data['name'],
            'email' => $data['email'],
        ];

        if (! empty($data['password'])) {
            $updates['password'] = Hash::make($data['password']);
        }

        $teamMember->update($updates);

        if ((int) $teamMember->id !== (int) auth()->id()) {
            Role::findOrCreate($data['role']);
            $teamMember->syncRoles([$data['role']]);
        }

        return redirect()
            ->route('team.index')
            ->with('success', 'Team member updated successfully.');
    }

    public function deactivate(User $teamMember): RedirectResponse
    {
        $this->authorizeTenantMember($teamMember);

        if ((int) $teamMember->id === (int) auth()->id()) {
            return back()->withErrors([
                'team' => 'You cannot deactivate your own owner account.',
            ]);
        }

        $teamMember->update(['is_active' => false]);

        return back()->with('success', 'Team member deactivated successfully.');
    }

    public function activate(User $teamMember): RedirectResponse
    {
        $this->authorizeTenantMember($teamMember);

        $tenant = auth()->user()->tenant()->with('activeSubscription.plan')->firstOrFail();
        $userLimit = $tenant->activeSubscription?->plan?->user_limit;
        $activeUserCount = $tenant->users()->where('is_active', true)->count();

        if ($userLimit !== null && $activeUserCount >= $userLimit) {
            return back()->withErrors([
                'team' => 'Your current plan allows '.$userLimit.' active users. Deactivate another user or upgrade the plan first.',
            ]);
        }

        $teamMember->update(['is_active' => true]);

        return back()->with('success', 'Team member activated successfully.');
    }

    private function authorizeTenantMember(User $member): void
    {
        abort_if($member->tenant_id !== auth()->user()->tenant_id, 403);
        abort_if($member->hasRole('owner') && (int) $member->id !== (int) auth()->id(), 403);
    }
}
