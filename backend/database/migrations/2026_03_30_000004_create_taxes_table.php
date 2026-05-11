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
        Schema::create('taxes', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('restaurant_id')->constrained('restaurants')->restrictOnDelete();
            $table->string('name');
            $table->integer('percentage');
            $table->timestamps();
            $table->softDeletes();

            $table->boolean('is_active')->virtualAs('IF(deleted_at IS NULL, 1, NULL)');
            $table->unique(['restaurant_id', 'name', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('taxes');
    }
};
