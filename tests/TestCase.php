<?php

namespace Tests;

use App\Services\FirestoreService;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Kreait\Firebase\Contract\Auth;
use Kreait\Firebase\Contract\Messaging;
use Mockery;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (empty(config('app.key'))) {
            config(['app.key' => 'base64:'.base64_encode(random_bytes(32))]);
        }

        $this->withoutVite();

        $credentialsPath = (new FirestoreService)->resolveCredentialsFilePath(
            config('firebase.projects.app.credentials') ?? config('firebase.projects.app.credentials_path')
        );

        if ($credentialsPath === null) {
            $authMock = Mockery::mock(Auth::class);
            $authMock->shouldReceive('verifyIdToken')
                ->byDefault()
                ->andThrow(new \InvalidArgumentException('Invalid Firebase ID token'));
            $this->app->instance('firebase.auth', $authMock);
            $this->app->instance(Auth::class, $authMock);

            $messagingMock = Mockery::mock(Messaging::class);
            $messagingMock->shouldReceive('send')
                ->byDefault()
                ->andReturn(['name' => 'projects/csn-paranaque/messages/mock']);
            $this->app->instance('firebase.messaging', $messagingMock);
            $this->app->instance(Messaging::class, $messagingMock);
        }
    }

    protected function tearDown(): void
    {
        try {
            parent::tearDown();
        } finally {
            $this->flushState();
        }
    }
}
