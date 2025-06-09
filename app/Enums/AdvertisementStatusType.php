<?php

declare(strict_types=1);

namespace App\Enums;

enum AdvertisementStatusType: string
{
    case NEW          = 'new';
    case ACTIVE       = 'active';
    case PARSE_ERROR  = 'error';
    case NOT_FOUND    = 'not_found';
    case NO_PRICE     = 'no_price';
}
