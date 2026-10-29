<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Emergency;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class FcmNotificationService
{
    public function __construct(
        protected ?Messaging $messaging = null
    ) {
        try {
            $this->messaging = $messaging ?? app('firebase.messaging');
        } catch (\Throwable $e) {
            Log::warning('Firebase Messaging initialization error: '.$e->getMessage());
        }
    }

    /**
     * Send emergency broadcast push notification to topic (e.g., 'responders').
     */
    public function sendEmergencyAlert(Emergency $emergency, string $topic = 'responders'): bool
    {
        if (app()->runningUnitTests()) {
            return true;
        }

        if (! $this->messaging) {
            return false;
        }

        try {
            $title = sprintf('CRITICAL ALERT: %s', $emergency->type_label);
            $body = sprintf('Urgency: %s | Location: %s', strtoupper((string) $emergency->urgency), $emergency->location);

            $message = CloudMessage::withTarget('topic', $topic)
                ->withNotification(Notification::create($title, $body))
                ->withData([
                    'emergency_id' => (string) $emergency->id,
                    'type' => (string) $emergency->type,
                    'urgency' => (string) $emergency->urgency,
                    'status' => (string) $emergency->status,
                    'lat' => (string) ($emergency->lat ?? ''),
                    'lng' => (string) ($emergency->lng ?? ''),
                ]);

            $this->messaging->send($message);

            return true;
        } catch (\Throwable $e) {
            Log::warning('FCM emergency push failed: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Send dispatch alert to specific responder device token.
     */
    public function sendDispatchNotification(Emergency $emergency, string $deviceToken): bool
    {
        if (app()->runningUnitTests()) {
            return true;
        }

        if (! $this->messaging || empty($deviceToken)) {
            return false;
        }

        try {
            $title = 'DISPATCH ASSIGNMENT: Emergency Alert';
            $body = sprintf('Assigned to: %s. ETA requested: %s.', $emergency->type_label, $emergency->eta ?? '8 mins');

            $message = CloudMessage::withTarget('token', $deviceToken)
                ->withNotification(Notification::create($title, $body))
                ->withData([
                    'emergency_id' => (string) $emergency->id,
                    'type' => (string) $emergency->type,
                    'location' => (string) $emergency->location,
                ]);

            $this->messaging->send($message);

            return true;
        } catch (\Throwable $e) {
            Log::warning('FCM dispatch notification failed: '.$e->getMessage());

            return false;
        }
    }
}
