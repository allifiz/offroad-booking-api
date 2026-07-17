<?php

namespace App\Enums;

enum DriverStatus: string
{
    case AVAILABLE = 'available';
    case UNAVAILABLE = 'unavailable';
    case SUSPENDED = 'suspended';
}
