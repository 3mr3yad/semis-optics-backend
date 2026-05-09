<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('badge')->nullable();
            $table->decimal('price', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->decimal('rating_score', 3, 1)->default(0.0);
            $table->unsignedInteger('total_reviews')->default(0);
            $table->text('main_image')->nullable();
            $table->json('gallery')->nullable();
            $table->json('magnification')->nullable();
            $table->json('frame_colors')->nullable();
            $table->json('features')->nullable();
            $table->json('technical_specifications')->nullable();
            $table->json('trust_badges')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
