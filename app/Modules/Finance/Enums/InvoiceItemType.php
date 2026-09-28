<?php

namespace App\Modules\Finance\Enums;

enum InvoiceItemType: string
{
    case Fee = 'BIAYA';
    case Discount = 'POTONGAN';
}
