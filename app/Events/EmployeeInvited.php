<?php
namespace App\Events;

use App\Models\Shop;
use App\Models\User;

class EmployeeInvited
{
    public function __construct(public readonly User $employee, public readonly Shop $shop) {}
}