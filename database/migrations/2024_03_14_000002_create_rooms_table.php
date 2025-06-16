<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->string('room_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('max_guests');
            $table->timestamps();

            $table->unique(['property_id', 'room_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
}; 