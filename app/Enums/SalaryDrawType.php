<?php
namespace App\Enums;

enum SalaryDrawType: string
{
    case Salary  = 'salary';   // regular salary draw
    case Bonus   = 'bonus';    // bonus payment
    case Advance = 'advance';  // advance beyond salary

    public function label(): string
    {
        return match ($this) {
            self::Salary  => 'Salary Draw',
            self::Bonus   => 'Bonus',
            self::Advance => 'Advance',
        };
    }
}