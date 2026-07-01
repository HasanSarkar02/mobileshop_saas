<?php

namespace App\Reporting\Enums;

enum ExportFormat: string
{
    case Pdf   = 'pdf';
    case Excel = 'excel';
    case Csv   = 'csv';
    case Print = 'print';

    public function mimeType(): string
    {
        return match ($this) {
            self::Pdf   => 'application/pdf',
            self::Excel => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            self::Csv   => 'text/csv',
            self::Print => 'text/html',
        };
    }

    public function extension(): string
    {
        return match ($this) {
            self::Pdf   => 'pdf',
            self::Excel => 'xlsx',
            self::Csv   => 'csv',
            self::Print => 'html',
        };
    }
}