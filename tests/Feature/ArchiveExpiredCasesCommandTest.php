<?php

namespace Tests\Feature;

use App\Mail\CaseExpiringWarningMail;
use App\Models\MissingChild;
use App\Models\Province;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ArchiveExpiredCasesCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-08-06 09:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_new_case_receives_a_two_year_expiration_date(): void
    {
        $case = $this->createCase(expiresAt: null, withEvents: true);

        $this->assertTrue($case->expires_at->equalTo(now()->addYears(2)));
    }

    public function test_command_archives_expired_unresolved_case_without_deleting_it(): void
    {
        $case = $this->createCase(expiresAt: now()->subMinute());

        $exitCode = Artisan::call('cases:archive-expired');

        $this->assertSame(0, $exitCode);

        $case->refresh();

        $this->assertSame(MissingChild::STATUS_ARCHIVED, $case->status);
        $this->assertSame('APPROVED', $case->status_before_archive);
        $this->assertNotNull($case->archived_at);
        $this->assertNull($case->deleted_at);
        $this->assertDatabaseHas('missing_children', ['id' => $case->getKey()]);
    }

    public function test_command_initializes_legacy_case_from_created_at_before_archiving(): void
    {
        $case = $this->createCase(expiresAt: null);
        $legacyCreatedAt = now()->subYears(2)->subDay();

        $case->forceFill([
            'created_at' => $legacyCreatedAt,
            'updated_at' => $legacyCreatedAt,
            'expires_at' => null,
        ])->saveQuietly();

        Artisan::call('cases:archive-expired');

        $case->refresh();

        $this->assertTrue($case->expires_at->equalTo($legacyCreatedAt->copy()->addYears(2)));
        $this->assertSame(MissingChild::STATUS_ARCHIVED, $case->status);
        $this->assertNull($case->deleted_at);
    }

    public function test_command_warns_once_when_case_enters_thirty_day_window(): void
    {
        Mail::fake();

        $case = $this->createCase(expiresAt: now()->addDays(30));

        Artisan::call('cases:archive-expired');
        Artisan::call('cases:archive-expired');

        $case->refresh();

        $this->assertNotNull($case->expiration_warning_sent_at);
        Mail::assertQueued(
            CaseExpiringWarningMail::class,
            fn (CaseExpiringWarningMail $mail): bool => $mail->missingChild->is($case)
                && $mail->hasTo($case->user->email),
        );
        Mail::assertQueuedCount(1);

        $renderedMail = (new CaseExpiringWarningMail($case->fresh('user')))->render();
        $this->assertStringContainsString('Xác nhận hồ sơ vẫn còn hiệu lực', $renderedMail);
    }

    public function test_command_does_not_archive_resolved_cases(): void
    {
        $found = $this->createCase(status: 'FOUND', expiresAt: now()->subDay());
        $closed = $this->createCase(status: 'CLOSED', expiresAt: now()->subDay());

        Artisan::call('cases:archive-expired');

        $this->assertSame('FOUND', $found->fresh()->status);
        $this->assertSame('CLOSED', $closed->fresh()->status);
    }

    public function test_owner_can_renew_archived_case_during_grace_period(): void
    {
        $case = $this->createCase(expiresAt: now()->subDay());
        Artisan::call('cases:archive-expired');
        $case->refresh();

        $showUrl = URL::temporarySignedRoute(
            'missing.renew.show',
            now()->addMinutes(5),
            ['missingChild' => $case->getKey()],
        );

        $this->actingAs($case->user)
            ->get($showUrl)
            ->assertOk()
            ->assertSee('Gia hạn hồ sơ tìm kiếm');

        $storeUrl = URL::temporarySignedRoute(
            'missing.renew.store',
            now()->addMinutes(5),
            ['missingChild' => $case->getKey()],
        );

        $this->actingAs($case->user)
            ->post($storeUrl)
            ->assertRedirect(route('missing.detail', ['id' => $case->getKey()]));

        $case->refresh();

        $this->assertSame('APPROVED', $case->status);
        $this->assertTrue($case->expires_at->equalTo(now()->addYears(2)));
        $this->assertNull($case->expiration_warning_sent_at);
        $this->assertNull($case->archived_at);
        $this->assertNull($case->status_before_archive);
    }

    public function test_non_owner_cannot_renew_case(): void
    {
        $case = $this->createCase(expiresAt: now()->addDays(10));
        $otherUser = User::factory()->create();
        $storeUrl = URL::temporarySignedRoute(
            'missing.renew.store',
            now()->addMinutes(5),
            ['missingChild' => $case->getKey()],
        );

        $this->actingAs($otherUser)
            ->post($storeUrl)
            ->assertForbidden();
    }

    private function createCase(
        string $status = 'APPROVED',
        mixed $expiresAt = null,
        bool $withEvents = false,
    ): MissingChild {
        $user = User::factory()->create();
        $province = Province::query()->first() ?? Province::factory()->create();

        $attributes = [
            'user_id' => $user->getKey(),
            'case_code' => 'MC-'.fake()->unique()->numerify('######'),
            'full_name' => fake()->name(),
            'gender' => 'UNKNOWN',
            'province_id' => $province->getKey(),
            'missing_date' => now()->subDay(),
            'contact_name' => 'Người thân',
            'contact_phone' => '0905123456',
            'contact_relation' => 'PARENT',
            'status' => $status,
        ];

        if ($expiresAt !== null) {
            $attributes['expires_at'] = $expiresAt;
        }

        if ($withEvents) {
            return MissingChild::query()->forceCreate($attributes);
        }

        return MissingChild::withoutEvents(
            fn (): MissingChild => MissingChild::query()->forceCreate($attributes),
        );
    }
}
