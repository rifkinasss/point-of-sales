<?php

namespace App\Enums;

enum ProductType: string
{
    case Stockable = 'stockable';
    case NonStockable = 'non_stockable';

    public function tracksInventory(): bool
    {
        return $this === self::Stockable;
    }
}
