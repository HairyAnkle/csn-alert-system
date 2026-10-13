<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Emergency;
use App\Services\FirestoreService;
use Illuminate\Support\Facades\Log;

class EmergencyObserver
{
    public function __construct(
        protected FirestoreService $firestore
    ) {}

    /**
     * Handle the Emergency "saved" event (covers created and updated).
     */
    public function saved(Emergency $emergency): void
    {
        try {
            $this->firestore->syncEmergency($emergency);
        } catch (\Throwable $e) {
            Log::warning('Firestore sync failed in EmergencyObserver: '.$e->getMessage());
        }
    }

    /**
     * Handle the Emergency "deleted" event.
     */
    public function deleted(Emergency $emergency): void
    {
        try {
            $this->firestore->deleteEmergency($emergency->id);
        } catch (\Throwable $e) {
            Log::warning('Firestore delete failed in EmergencyObserver: '.$e->getMessage());
        }
    }
}
