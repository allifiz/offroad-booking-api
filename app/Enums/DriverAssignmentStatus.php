<?php

namespace App\Enums;

enum DriverAssignmentStatus: string
{
    case OFFERED = 'offered';
    case ACCEPTED = 'accepted';
    case REJECTED = 'rejected';
    case CANCELLED = 'cancelled';
}
