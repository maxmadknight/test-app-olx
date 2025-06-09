<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Advertisement;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/** @extends Factory<Advertisement> */
class AdvertisementFactory extends Factory
{
    protected $model = Advertisement::class;

    public function definition()
    {
        return [
            'url'         => $this->faker->url(),
            'price'       => $this->faker->word(),
            'currency'    => $this->faker->currencyCode(),
            'change_date' => Carbon::now(),
            'created_at'  => Carbon::now(),
            'updated_at'  => Carbon::now(),
        ];
    }
}
