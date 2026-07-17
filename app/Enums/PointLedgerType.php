<?php

namespace App\Enums;

enum PointLedgerType: string
{
    case CREDIT = 'credit';
    case DEBIT = 'debit';
    case HOLD = 'hold';
    case RELEASE = 'release';
}
