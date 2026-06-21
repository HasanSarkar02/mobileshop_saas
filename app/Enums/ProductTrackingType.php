<?php

namespace App\Enums;

enum ProductTrackingType: string
{
    case Serialized = 'serialized';
    case NonSerialized = 'non_serialized';
}