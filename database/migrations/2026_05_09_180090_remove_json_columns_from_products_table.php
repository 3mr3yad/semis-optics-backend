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
        $columns = [
            'gallery',
            'magnification',
            'frame_colors',
            'features',
            'technical_specifications',
            'trust_badges',
        ];

        $existingColumns = array_values(array_filter($columns, fn (string $column): bool => Schema::hasColumn('products', $column)));

        if ($existingColumns === []) {
            return;
        }

        Schema::table('products', function (Blueprint $table) use ($existingColumns) {
            $table->dropColumn($existingColumns);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->json('gallery')->nullable();
            $table->json('magnification')->nullable();
            $table->json('frame_colors')->nullable();
            $table->json('features')->nullable();
            $table->json('technical_specifications')->nullable();
            $table->json('trust_badges')->nullable();
        });
    }
};
