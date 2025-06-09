<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AdvertisementStatusType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Advertisement extends Model
{
    use HasFactory;

    protected $fillable = [
        'url',
        'price',
        'currency',
        'change_date',
        'status',
    ];

    /**
     * Get the subscribers for this advertisement
     */
    public function subscribers(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Get the price history for this advertisement
     */
    public function history(): HasMany
    {
        return $this->hasMany(AdvertisementHistory::class);
    }

    /**
     * Get only verified subscribers
     */
    public function verifiedSubscribers()
    {
        return $this->subscribers()->whereNull('verification_token');
    }

    /**
     * Get the formatted price with currency
     */
    public function getFormattedPriceAttribute(): string
    {
        if (! $this->price || ! $this->currency) {
            return 'N/A';
        }

        return $this->price.' '.$this->currency;
    }

    protected function casts(): array
    {
        return [
            'price'       => 'int',
            'change_date' => 'datetime',
            'status'      => AdvertisementStatusType::class,
        ];
    }
}
