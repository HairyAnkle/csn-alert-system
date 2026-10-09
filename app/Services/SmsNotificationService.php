<?php

namespace App\Services;

use App\Models\Emergency;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsNotificationService
{
    /**
     * Send SMS notification to the dispatched responder or emergency unit.
     */
    public function notifyDispatchedUnit(Emergency $emergency): bool
    {
        $emergency->loadMissing(['child', 'responder']);

        $recipientPhone = $emergency->responder?->phone
            ?? $emergency->child?->emergency_contact
            ?? config('services.semaphore.default_recipient');

        $childName = $emergency->child?->name ?? 'Child';
        $location = $emergency->location;
        $type = $emergency->type;
        $urgency = strtoupper($emergency->urgency);
        $notes = $emergency->guardian_notes ? " Notes: {$emergency->guardian_notes}" : '';

        $message = "CSN DISPATCH ALERT [{$urgency}]: Emergency for {$childName} ({$type}) at {$location}.{$notes}";

        if (! $recipientPhone) {
            Log::info("[SMS Notification] No phone number available for dispatch notification. Alert ID #{$emergency->id}: {$message}");

            return false;
        }

        return $this->sendSms($recipientPhone, $message);
    }

    /**
     * Send an SMS using Semaphore API, falling back to log in local/testing environment.
     */
    public function sendSms(string $number, string $message): bool
    {
        $apiKey = config('services.semaphore.api_key');
        $senderName = config('services.semaphore.sender_name', 'CSN_ALERT');

        if (empty($apiKey) || app()->environment('local', 'testing')) {
            Log::info("[SMS Dispatch via Semaphore/Local] To: {$number} | Message: {$message}");

            return true;
        }

        try {
            $response = Http::timeout(5)->post('https://api.semaphore.co/api/v4/messages', [
                'apikey' => $apiKey,
                'number' => $number,
                'message' => $message,
                'sendername' => $senderName,
            ]);

            if ($response->successful()) {
                Log::info("[SMS Sent successfully to {$number}]");

                return true;
            }

            Log::error("[SMS Semaphore Error] Status: {$response->status()} Body: {$response->body()}");

            return false;
        } catch (\Throwable $e) {
            Log::error("[SMS Send Exception] {$e->getMessage()}");

            return false;
        }
    }
}
