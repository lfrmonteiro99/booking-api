<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Add indexes for performance optimization
            $table->index(['user_id', 'created_at'], 'bookings_user_created_index');
            $table->index(['room_id', 'check_in', 'check_out'], 'bookings_room_dates_index');
            $table->index(['property_id', 'check_in', 'check_out'], 'bookings_property_dates_index');
            $table->index(['status', 'check_in'], 'bookings_status_checkin_index');
            $table->index(['check_in', 'check_out'], 'bookings_date_range_index');
        });

        // Add check constraints (only for production MySQL, skip for testing)
        if (DB::getDriverName() === 'mysql' && app()->environment() !== 'testing') {
            // Add check constraint to ensure check_out is after check_in
            DB::statement('ALTER TABLE bookings ADD CONSTRAINT check_dates CHECK (check_out > check_in)');
            
            // Add check constraint to ensure guests is positive
            DB::statement('ALTER TABLE bookings ADD CONSTRAINT check_guests CHECK (guests > 0)');
            
            // Add check constraint to ensure check_in is not in the past (for new bookings)
            // Note: This allows existing bookings but prevents new past bookings
            DB::statement('ALTER TABLE bookings ADD CONSTRAINT check_future_checkin CHECK (check_in >= CURDATE() OR created_at < NOW())');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Drop indexes
            $table->dropIndex('bookings_user_created_index');
            $table->dropIndex('bookings_room_dates_index');
            $table->dropIndex('bookings_property_dates_index');
            $table->dropIndex('bookings_status_checkin_index');
            $table->dropIndex('bookings_date_range_index');
        });

        // Drop check constraints (only for production MySQL, skip for testing)
        if (DB::getDriverName() === 'mysql' && app()->environment() !== 'testing') {
            DB::statement('ALTER TABLE bookings DROP CONSTRAINT IF EXISTS check_dates');
            DB::statement('ALTER TABLE bookings DROP CONSTRAINT IF EXISTS check_guests');
            DB::statement('ALTER TABLE bookings DROP CONSTRAINT IF EXISTS check_future_checkin');
        }
    }
};