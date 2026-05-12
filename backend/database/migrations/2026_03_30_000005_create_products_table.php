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
            $table->uuid('uuid')->unique();
            $table->foreignId('restaurant_id')->constrained('restaurants')->restrictOnDelete();
            $table->foreignId('family_id')->constrained('families')->restrictOnDelete();
            $table->foreignId('tax_id')->constrained('taxes')->restrictOnDelete();
            $table->string('image_src')->nullable();
            $table->string('name');
            $table->integer('price');
            $table->integer('stock');
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->boolean('is_active')->virtualAs('CASE WHEN deleted_at IS NULL THEN 1 ELSE NULL END');
            $table->unique(['restaurant_id', 'name', 'is_active'], 'products_restaurant_name_active_unique');
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
