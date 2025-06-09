<?php

declare(strict_types=1);

use App\Models\Advertisement;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Advertisement::class)->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->string('verification_token')->default('false')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->timestamps();
        });
    }
};
