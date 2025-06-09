<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdvertisementHistory extends Model
{
    protected $fillable = [
        'price',
        'change_date',
        'currency',
    ];

    protected function casts(): array
    {
        return [
            'change_date' => 'datetime',
            'price'       => 'int',
        ];
    }
}
