<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('availabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->boolean('is_available')->default(true);
            $table->decimal('price', 10, 2)->nullable();
            $table->integer('max_guests');
            $table->timestamps();

            $table->unique(['room_id', 'date']);

            $table->index(['room_id', 'date', 'is_available']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('availabilities');
    }
}; 