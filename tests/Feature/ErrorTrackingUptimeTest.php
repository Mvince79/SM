<?php

namespace Tests\Feature;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Sentry\State\HubInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class ErrorTrackingUptimeTest extends TestCase
{
    public function test_public_framework_health_endpoint_is_available(): void
    {
        $this->get('/health')->assertOk();
    }

    public function test_sentry_is_registered_without_a_local_dsn(): void
    {
        $client = app(HubInterface::class)->getClient();

        $this->assertNotNull($client);
        $this->assertNull($client->getOptions()->getDsn());
        $this->assertFalse($client->getOptions()->shouldSendDefaultPii());
        $this->assertSame(0.0, $client->getOptions()->getTracesSampleRate());
    }

    public function test_sentry_is_connected_to_the_laravel_exception_handler(): void
    {
        $bootstrap = file_get_contents(base_path('bootstrap/app.php'));

        $this->assertStringContainsString('Integration::handles($exceptions)', $bootstrap);
    }

    public function test_expected_not_found_exceptions_are_filtered(): void
    {
        $ignored = app(HubInterface::class)->getClient()?->getOptions()->getIgnoreExceptions() ?? [];

        $this->assertContains(ModelNotFoundException::class, $ignored);
        $this->assertContains(NotFoundHttpException::class, $ignored);

        $this->get('/__f01-missing-route')->assertNotFound();
    }
}
