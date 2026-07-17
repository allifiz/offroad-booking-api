<?php

namespace App\Enums;

enum TourPackageStatus: string
{
    case DRAFT = 'draft';
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
}
