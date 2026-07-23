<?php

namespace App\Actions;

use App\Enums\UserType;
use App\Events\EmployeeInvited;
use App\Models\Shop;
use App\Models\User;
use App\Services\UserInviter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateEmployeeAction
{
    public function __construct(
        private readonly UserInviter $userInviter,
    ) {}

    /**
     * @param  array{name: string, email: string, phone?: string, branch_id?: int, role: string}  $data
     */
    public function execute(Shop $shop, array $data): User
    {
        return DB::transaction(function () use ($shop, $data) {
            $employee = User::create([
                'shop_id' => $shop->id,
                'branch_id' => $data['branch_id'] ?? null,
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Str::password(40),
                'user_type' => UserType::Employee,
                'phone' => $data['phone'] ?? null,
                'is_active' => true,
                'email_verified_at' => null,
            ]);

            $employee->assignRole($data['role']);

            $this->userInviter->invite($employee, "an employee of {$shop->name}", $shop);
            DB::afterCommit(fn () => event(new EmployeeInvited($employee, $shop)));

            activity()
            ->causedBy(Auth::user())
            ->performedOn($employee)
            ->withProperties([
                'name'      => $data['name'],
                'email'     => $data['email'],
                'phone'     => $data['phone'] ?? null,
                'branch_id' => $data['branch_id'] ?? null,
                'role'      => $data['role'],
            ])
            ->log('employee.created');

            return $employee;
        });
    }
}