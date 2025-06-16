<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\Property;
use App\Models\Room;
use App\Models\Availability;

class ProcessAvailabilityChunk implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $propertyId;
    protected $roomsData;

    /**
     * Create a new job instance.
     *
     * @param string $propertyId
     * @param array $roomsData
     * @return void
     */
    public function __construct(string $propertyId, array $roomsData)
    {
        $this->propertyId = $propertyId;
        $this->roomsData = $roomsData;
    }

    /**
     * Get the property ID.
     *
     * @return string
     */
    public function getPropertyId(): string
    {
        return $this->propertyId;
    }

    /**
     * Get the rooms data.
     *
     * @return array
     */
    public function getRoomsData(): array
    {
        return $this->roomsData;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        DB::connection()->disableQueryLog();

        DB::transaction(function () {
            $property = Property::firstOrCreate(
                ['property_id' => $this->propertyId],
                ['name' => 'Property ' . $this->propertyId]
            );

            // Group data by room_id to handle room creation and availability in batches
            $roomsGroupedById = collect($this->roomsData)->groupBy('room_id');

            $roomRecords = [];
            foreach ($roomsGroupedById as $roomId => $roomAvailabilities) {
                $firstAvailability = $roomAvailabilities->first();
                $roomRecords[] = [
                    'property_id' => $property->id,
                    'room_id' => $roomId,
                    'name' => $firstAvailability['name'] ?? 'Room ' . $roomId,
                    'max_guests' => $firstAvailability['max_guests'] ?? 2,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if (!empty($roomRecords)) {
                Room::upsert($roomRecords, ['property_id', 'room_id'], ['name', 'max_guests', 'updated_at']);
            }

            // Retrieve the rooms we just created to get their internal IDs
            $roomsInDb = $property->rooms()->whereIn('room_id', array_keys($roomsGroupedById->all()))->get()->keyBy('room_id');
            
            $availabilityRecords = [];
            foreach ($this->roomsData as $dailyData) {
                $room = $roomsInDb->get($dailyData['room_id']);
                if ($room) {
                    $availabilityRecords[] = [
                        'room_id' => $room->id,
                        'date' => $dailyData['date'],
                        'is_available' => true,
                        'price' => $dailyData['price'],
                        'max_guests' => $dailyData['max_guests'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            if (!empty($availabilityRecords)) {
                Availability::upsert($availabilityRecords, ['room_id', 'date'], ['is_available', 'price', 'max_guests', 'updated_at']);
            }
        });

        Cache::tags(['availability_property:' . $this->propertyId])->flush();
    }
} 