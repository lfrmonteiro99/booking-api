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
        // Only create triggers for MySQL in production, skip for testing
        if (DB::getDriverName() === 'mysql' && app()->environment() !== 'testing') {
            // For MySQL, we need to use a trigger to prevent overlapping bookings
            // since MySQL doesn't support exclusion constraints like PostgreSQL
            
            $trigger = "
            CREATE TRIGGER prevent_booking_overlap 
            BEFORE INSERT ON bookings 
            FOR EACH ROW 
            BEGIN 
                DECLARE overlap_count INT DEFAULT 0;
                
                SELECT COUNT(*) INTO overlap_count 
                FROM bookings 
                WHERE room_id = NEW.room_id 
                  AND status IN ('confirmed', 'pending')
                  AND (
                    (NEW.check_in < check_out AND NEW.check_out > check_in)
                  );
                
                IF overlap_count > 0 THEN 
                    SIGNAL SQLSTATE '45000' 
                    SET MESSAGE_TEXT = 'Booking dates overlap with existing booking for this room';
                END IF;
            END
            ";
            
            DB::unprepared($trigger);

            // Also create an update trigger
            $updateTrigger = "
            CREATE TRIGGER prevent_booking_overlap_update 
            BEFORE UPDATE ON bookings 
            FOR EACH ROW 
            BEGIN 
                DECLARE overlap_count INT DEFAULT 0;
                
                SELECT COUNT(*) INTO overlap_count 
                FROM bookings 
                WHERE room_id = NEW.room_id 
                  AND id != NEW.id
                  AND status IN ('confirmed', 'pending')
                  AND (
                    (NEW.check_in < check_out AND NEW.check_out > check_in)
                  );
                
                IF overlap_count > 0 THEN 
                    SIGNAL SQLSTATE '45000' 
                    SET MESSAGE_TEXT = 'Updated booking dates overlap with existing booking for this room';
                END IF;
            END
            ";
            
            DB::unprepared($updateTrigger);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql' && app()->environment() !== 'testing') {
            DB::unprepared('DROP TRIGGER IF EXISTS prevent_booking_overlap');
            DB::unprepared('DROP TRIGGER IF EXISTS prevent_booking_overlap_update');
        }
    }
};