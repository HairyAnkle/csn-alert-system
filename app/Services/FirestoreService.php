<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Emergency;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FirestoreService
{
    protected ?string $projectId;

    protected ?string $credentialsPath;

    public function __construct()
    {
        $this->projectId = env('FIREBASE_PROJECT_ID', 'csn-paranaque');
        $rawPath = config('firebase.projects.app.credentials') ?? env('FIREBASE_CREDENTIALS');
        $this->credentialsPath = $rawPath && ! file_exists($rawPath) ? base_path($rawPath) : $rawPath;
    }

    /**
     * Fetch OAuth2 access token for Google Datastore / Firestore API.
     */
    public function getAccessToken(): ?string
    {
        if (! $this->credentialsPath || ! file_exists($this->credentialsPath)) {
            Log::warning('Firestore credentials path not found: '.$this->credentialsPath);

            return null;
        }

        try {
            $credentials = new ServiceAccountCredentials(
                ['https://www.googleapis.com/auth/datastore'],
                $this->credentialsPath
            );

            $tokenData = $credentials->fetchAuthToken();

            return $tokenData['access_token'] ?? null;
        } catch (\Throwable $e) {
            Log::error('Failed to obtain Firestore OAuth access token: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Synchronize an Eloquent Emergency model to Cloud Firestore.
     */
    public function syncEmergency(Emergency $emergency): bool
    {
        if (app()->runningUnitTests() && ! config('firebase.sync_in_tests', false)) {
            return true;
        }

        $emergency->loadMissing(['child', 'responder']);
        $payload = $emergency->toFrontendArray();

        $payload['created_at_iso'] = $emergency->created_at?->toISOString() ?? now()->toISOString();
        $payload['updated_at_iso'] = $emergency->updated_at?->toISOString() ?? now()->toISOString();
        if ($emergency->reported_at) {
            $payload['reported_at_iso'] = $emergency->reported_at->toISOString();
        }

        return $this->upsertEmergency($payload);
    }

    /**
     * Upsert an emergency document in Cloud Firestore.
     */
    public function upsertEmergency(array $emergency): bool
    {
        $token = $this->getAccessToken();
        if (! $token || ! $this->projectId) {
            return false;
        }

        $docId = (string) ($emergency['id'] ?? uniqid('em_'));
        $url = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/emergencies/{$docId}";

        $fields = $this->formatFields($emergency);

        try {
            $response = Http::withToken($token)
                ->timeout(6)
                ->patch($url, [
                    'fields' => $fields,
                ]);

            if (! $response->successful()) {
                Log::warning('Firestore upsert warning: '.$response->body());

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('Firestore upsert exception: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Delete an emergency document from Cloud Firestore.
     */
    public function deleteEmergency(string|int $id): bool
    {
        $token = $this->getAccessToken();
        if (! $token || ! $this->projectId) {
            return false;
        }

        $url = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/emergencies/{$id}";

        try {
            $response = Http::withToken($token)->timeout(5)->delete($url);

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error('Firestore delete exception: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Format a PHP array into Firestore REST typed field format.
     */
    public function formatFields(array $data): array
    {
        $formatted = [];
        foreach ($data as $key => $value) {
            $formatted[$key] = $this->formatValue($value);
        }

        return $formatted;
    }

    /**
     * Convert a PHP value to a Firestore REST typed value.
     */
    protected function formatValue(mixed $value): array
    {
        if (is_null($value)) {
            return ['nullValue' => null];
        }

        if (is_bool($value)) {
            return ['booleanValue' => $value];
        }

        if (is_int($value)) {
            return ['integerValue' => (string) $value];
        }

        if (is_float($value)) {
            return ['doubleValue' => $value];
        }

        if (is_array($value)) {
            // Check if associative
            if (array_keys($value) !== range(0, count($value) - 1)) {
                return ['mapValue' => ['fields' => $this->formatFields($value)]];
            }

            // Indexed list
            $list = [];
            foreach ($value as $item) {
                $list[] = $this->formatValue($item);
            }

            return ['arrayValue' => ['values' => $list]];
        }

        return ['stringValue' => (string) $value];
    }
}
