<?php

namespace App\Enums;

enum EmploymentType: string
{
    case Monthly    = 'monthly';
    case Daily      = 'daily';
    case Hourly     = 'hourly';
    case Contract   = 'contract';
    case Commission = 'commission';
    case PieceRate  = 'piece_rate';
    case Temporary  = 'temporary';
    case Intern     = 'intern';
    case PartTime   = 'part_time';
    case Freelancer = 'freelancer';
    case Mixed      = 'mixed';

    public function label(): string
    {
        return match ($this) {
            self::Monthly    => 'Monthly Salary',
            self::Daily      => 'Daily Wage',
            self::Hourly     => 'Hourly',
            self::Contract   => 'Contract',
            self::Commission => 'Commission',
            self::PieceRate  => 'Piece Rate',
            self::Temporary  => 'Temporary',
            self::Intern     => 'Intern',
            self::PartTime   => 'Part Time',
            self::Freelancer => 'Freelancer',
            self::Mixed      => 'Mixed',
        };
    }
}