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
        Schema::table('bookings', function (Blueprint $table) {
            $table->decimal('price_per_night', 10, 2)->nullable()->after('guests');
            $table->decimal('total_price', 10, 2)->nullable()->after('price_per_night');
            $table->string('currency', 3)->default('USD')->after('total_price');
            $table->integer('nights')->nullable()->after('currency');
            $table->decimal('tax_amount', 10, 2)->default(0)->after('nights');
            $table->decimal('final_total', 10, 2)->nullable()->after('tax_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn([
                'price_per_night',
                'total_price', 
                'currency',
                'nights',
                'tax_amount',
                'final_total'
            ]);
        });
    }
};