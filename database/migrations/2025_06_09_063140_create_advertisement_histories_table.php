<?php

declare(strict_types=1);

use App\Models\Advertisement;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('advertisement_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Advertisement::class)->constrained()->cascadeOnDelete();
            $table->string('price');
            $table->string('currency');
            $table->dateTime('change_date');
            $table->timestamps();
        });
    }
};
