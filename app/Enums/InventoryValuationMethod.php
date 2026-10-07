<?php

namespace App\Enums;

enum InventoryValuationMethod: string
{
    case FIFO = 'fifo';
    case WEIGHTED_AVERAGE = 'weighted_average';
}
