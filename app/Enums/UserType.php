<?php

namespace App\Enums;

enum UserType: string
{
    case SuperAdmin = 'super_admin';
    case Owner = 'owner';
    case Employee = 'employee';
}