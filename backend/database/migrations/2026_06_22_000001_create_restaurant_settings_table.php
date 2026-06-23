<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurant_settings', function (Blueprint $table) {
            $table->id();
            $table->string('restaurant_name');
            $table->string('app_name');
            $table->string('tagline')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('app_icon_path')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('whatsapp')->nullable();
            $table->text('address')->nullable();
            $table->text('about_text')->nullable();
            $table->string('primary_color')->default('#FFC107');
            $table->string('secondary_color')->default('#111111');
            $table->string('background_color')->default('#F8F2E9');
            $table->string('button_color')->default('#FFC107');
            $table->boolean('is_open')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant_settings');
    }
};
