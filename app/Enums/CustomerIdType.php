<?php

namespace App\Enums;

enum CustomerIdType: string
{
    case Nid              = 'nid';
    case Passport         = 'passport';
    case BirthCertificate = 'birth_certificate';
    case DrivingLicense   = 'driving_license';
    case Other            = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Nid              => 'National ID (NID)',
            self::Passport         => 'Passport',
            self::BirthCertificate => 'Birth Certificate',
            self::DrivingLicense   => 'Driving License',
            self::Other            => 'Other',
        };
    }
}