<?php

namespace App\Enums;

enum VehicleStatus: string
{
    case AVAILABLE = 'available';
    case UNAVAILABLE = 'unavailable';
    case IN_USE = 'in_use';
    case MAINTENANCE = 'maintenance';
    case INACTIVE = 'inactive';
}
