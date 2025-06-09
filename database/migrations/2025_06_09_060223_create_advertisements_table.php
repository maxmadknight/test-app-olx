<?php

declare(strict_types=1);

use App\Enums\AdvertisementStatusType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('advertisements', function (Blueprint $table) {
            $table->id();
            $table->string('url')->unique();
            $table->dateTime('change_date')->nullable();
            $table->string('price')->nullable();
            $table->string('currency')->nullable();
            $table->enum('status', [
                AdvertisementStatusType::NEW->value,
                AdvertisementStatusType::ACTIVE->value,
                AdvertisementStatusType::PARSE_ERROR->value,
                AdvertisementStatusType::NOT_FOUND->value,
                AdvertisementStatusType::NO_PRICE->value,
            ])->default(AdvertisementStatusType::NEW->value);
            $table->timestamps();
        });
    }
};
