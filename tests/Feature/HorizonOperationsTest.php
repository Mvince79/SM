<?php

namespace Tests\Feature;

use App\Jobs\HorizonSmokeFailureJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\Contracts\TagRepository;
use Laravel\Horizon\Jobs\RetryFailedJob;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class HorizonOperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_admin_can_open_dashboard(): void
    {
        $admin = User::factory()->create([
            'role' => 'ADMIN',
            'status' => 'ACTIVE',
        ]);

        $this->actingAs($admin)
            ->get('/horizon')
            ->assertOk()
            ->assertSee('Laravel');
    }

    public function test_dashboard_rejects_guest_regular_user_and_banned_admin(): void
    {
        app()->detectEnvironment(fn (): string => 'local');

        $this->get('/horizon')->assertForbidden();

        $user = User::factory()->create([
            'role' => 'USER',
            'status' => 'ACTIVE',
        ]);

        $this->actingAs($user)->get('/horizon')->assertForbidden();

        $bannedAdmin = User::factory()->create([
            'role' => 'ADMIN',
            'status' => 'BANNED',
        ]);

        $this->actingAs($bannedAdmin)->get('/horizon')->assertForbidden();
    }

    public function test_admin_can_see_a_failed_job_through_horizon_api(): void
    {
        $admin = User::factory()->create([
            'role' => 'ADMIN',
            'status' => 'ACTIVE',
        ]);

        $failedJob = (object) [
            'id' => 'failed-job-123',
            'payload' => json_encode([
                'displayName' => HorizonSmokeFailureJob::class,
                'data' => [],
            ], JSON_THROW_ON_ERROR),
            'exception' => 'Intentional Horizon smoke-test failure [test-run].',
            'context' => null,
            'retried_by' => null,
        ];

        $jobs = Mockery::mock(JobRepository::class);
        $jobs->shouldReceive('getFailed')->once()->with(-1)->andReturn(collect([$failedJob]));
        $jobs->shouldReceive('countFailed')->once()->andReturn(1);

        $this->app->instance(JobRepository::class, $jobs);
        $this->app->instance(TagRepository::class, Mockery::mock(TagRepository::class));

        $this->actingAs($admin)
            ->getJson('/horizon/api/jobs/failed')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('jobs.0.id', 'failed-job-123')
            ->assertJsonPath('jobs.0.payload.displayName', HorizonSmokeFailureJob::class);
    }

    public function test_only_admin_can_retry_a_failed_job_through_horizon_api(): void
    {
        Bus::fake();

        $user = User::factory()->create([
            'role' => 'USER',
            'status' => 'ACTIVE',
        ]);

        $this->actingAs($user)
            ->post('/horizon/api/jobs/retry/failed-job-123')
            ->assertForbidden();

        Bus::assertNothingDispatched();

        $admin = User::factory()->create([
            'role' => 'ADMIN',
            'status' => 'ACTIVE',
        ]);

        $this->actingAs($admin)
            ->post('/horizon/api/jobs/retry/failed-job-123')
            ->assertOk();

        Bus::assertDispatched(
            RetryFailedJob::class,
            fn (RetryFailedJob $job): bool => $job->id === 'failed-job-123',
        );
    }

    public function test_smoke_job_fails_once_then_succeeds_on_retry(): void
    {
        Cache::flush();

        $job = new HorizonSmokeFailureJob('test-run');

        try {
            $job->handle();
            $this->fail('The first smoke-test execution should fail.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('test-run', $exception->getMessage());
        }

        $job->handle();

        $this->assertFalse(Cache::has('ops:horizon-smoke:test-run'));
    }

    public function test_redis_retry_window_exceeds_horizon_and_job_timeouts(): void
    {
        $retryAfter = config('queue.connections.redis.retry_after');
        $workerTimeout = config('horizon.defaults.supervisor-general.timeout');

        $this->assertGreaterThan($workerTimeout, $retryAfter);
        $this->assertGreaterThan(120, $retryAfter);
        $this->assertSame(['notifications', 'kyc', 'default'], config('horizon.defaults.supervisor-general.queue'));
        $this->assertSame(['face-match'], config('horizon.defaults.supervisor-face-match.queue'));
        $this->assertSame(1, config('horizon.environments.production.supervisor-face-match.maxProcesses'));
    }
}
