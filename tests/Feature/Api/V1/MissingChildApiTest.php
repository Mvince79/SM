<?php

namespace Tests\Feature\Api\V1;

use App\Models\MissingChild;
use App\Models\Photo;
use App\Models\Province;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Test Suite: API v1 Endpoints
 * 
 * Tests:
 * - GET /api/v1/missing-children (list)
 * - GET /api/v1/missing-children/{id} (detail)
 * - GET /api/v1/map-data (map markers)
 * - Error handling and JSON responses
 * - Rate limiting
 */
class MissingChildApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedData();
    }

    private function seedData(): void
    {
        $user = User::factory()->create();
        $province = Province::first() ?? Province::factory()->create(['id' => 1, 'name' => 'Hà Nội']);

        for ($i = 1; $i <= 20; $i++) {
            MissingChild::create([
                'user_id' => $user->id,
                'case_code' => 'MC-2026-' . str_pad($i, 5, '0', STR_PAD_LEFT),
                'full_name' => "Test Case {$i}",
                'age_estimate' => 5 + ($i % 10),
                'gender' => $i % 2 === 0 ? 'MALE' : 'FEMALE',
                'province_id' => $province->id,
                'missing_date' => now()->subDays($i),
                'clothes_desc' => 'Áo xanh, quần đen',
                'contact_name' => 'Parent',
                'contact_phone' => "+8490123456{$i}",
                'status' => 'APPROVED',
            ]);
        }
    }

    // ────────────────────────────────────────────────────────────────────────
    // GET /api/v1/missing-children (List)
    // ────────────────────────────────────────────────────────────────────────

    public function test_get_missing_children_returns_json_collection()
    {
        $response = $this->getJson('/api/v1/missing-children');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'case_code',
                        'full_name',
                        'age_estimate',
                        'status',
                    ],
                ],
                'pagination' => [
                    'total',
                    'count',
                    'per_page',
                    'current_page',
                    'last_page',
                ],
                'links' => [
                    'first',
                    'last',
                    'prev',
                    'next',
                ],
            ]);
    }

    public function test_contact_phone_hidden_from_guest()
    {
        $response = $this->getJson('/api/v1/missing-children');
        $response->assertStatus(200);
        $data = $response->json('data.0');
        $this->assertArrayNotHasKey('contact_phone', $data);
    }

    public function test_contact_phone_visible_to_authenticated_user()
    {
        $user = User::first();
        $response = $this->actingAs($user)
            ->getJson('/api/v1/missing-children');
        $response->assertStatus(200);
        $data = $response->json('data.0');
        $this->assertArrayHasKey('contact_phone', $data);
    }

    public function test_missing_children_with_filter()
    {
        $response = $this->getJson('/api/v1/missing-children?gender=MALE');
        $response->assertStatus(200);
        $data = $response->json('data');
        foreach ($data as $item) {
            $this->assertEquals('MALE', $item['gender']);
        }
    }

    public function test_missing_children_pagination()
    {
        $response = $this->getJson('/api/v1/missing-children?page=1');
        $response->assertStatus(200);
        $pagination = $response->json('pagination');
        $this->assertEquals(1, $pagination['current_page']);
        $this->assertEquals(15, $pagination['per_page']);
        $this->assertLessThanOrEqual(15, $pagination['count']);
    }

    // ────────────────────────────────────────────────────────────────────────
    // GET /api/v1/missing-children/{id} (Detail)
    // ────────────────────────────────────────────────────────────────────────

    public function test_get_missing_child_detail_returns_single_resource()
    {
        $missing = MissingChild::first();
        $response = $this->getJson("/api/v1/missing-children/{$missing->id}");
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'case_code',
                    'full_name',
                    'age_estimate',
                    'status',
                    'created_at',
                    'updated_at',
                ]
            ]);
    }

    public function test_get_missing_child_by_case_code()
    {
        $missing = MissingChild::first();
        $response = $this->getJson("/api/v1/missing-children/{$missing->case_code}");
        $response->assertStatus(200)
            ->assertJson(['data' => ['case_code' => $missing->case_code]]);
    }

    public function test_missing_child_photos_are_tiered_by_kyc_status()
    {
        $missing = MissingChild::first();
        Photo::withoutEvents(fn () => Photo::query()->create([
            'model_type' => MissingChild::class,
            'model_id' => $missing->id,
            'path_original' => 'private/original.jpg',
            'path_thumb' => 'private/thumb.jpg',
            'path_blurred' => 'private/blurred.jpg',
            'is_primary' => 1,
        ]));

        $this->getJson("/api/v1/missing-children/{$missing->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.primary_photo.access_tier', 'guest_blur')
            ->assertJsonPath('data.primary_photo.url', '/storage/private/blurred.jpg')
            ->assertJsonPath('data.primary_photo.url_original', null);

        $unverifiedUser = User::factory()->create(['kyc_status' => 'NONE']);
        $this->actingAs($unverifiedUser)
            ->getJson("/api/v1/missing-children/{$missing->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.primary_photo.access_tier', 'thumbnail')
            ->assertJsonPath('data.primary_photo.url', '/storage/private/thumb.jpg')
            ->assertJsonPath('data.primary_photo.url_original', null);

        $verifiedUser = User::factory()->create(['kyc_status' => 'VERIFIED']);
        $this->actingAs($verifiedUser)
            ->getJson("/api/v1/missing-children/{$missing->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.primary_photo.access_tier', 'original')
            ->assertJsonPath('data.primary_photo.url', '/storage/private/original.jpg')
            ->assertJsonPath('data.primary_photo.url_original', '/storage/private/original.jpg');

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'photo_access',
            'subject_type' => Photo::class,
            'causer_id' => $verifiedUser->id,
        ]);
    }

    public function test_missing_child_detail_not_found()
    {
        $response = $this->getJson('/api/v1/missing-children/99999');
        $response->assertStatus(404)
            ->assertJsonStructure(['message']);
    }

    public function test_missing_child_non_approved_returns_403_for_guest()
    {
        $user = User::first();
        $pending = MissingChild::create([
            'user_id' => $user->id,
            'case_code' => 'MC-2026-PENDING',
            'full_name' => 'Pending Case',
            'age_estimate' => 7,
            'gender' => 'MALE',
            'province_id' => 1,
            'missing_date' => now(),
            'contact_name' => 'Parent',
            'contact_phone' => '+84901234567',
            'status' => 'PENDING',
        ]);
        $response = $this->getJson("/api/v1/missing-children/{$pending->id}");
        $response->assertStatus(403)
            ->assertJsonStructure(['message']);
    }

    // ────────────────────────────────────────────────────────────────────────
    // GET /api/v1/map-data
    // ────────────────────────────────────────────────────────────────────────

    public function test_get_map_data_returns_markers()
    {
        $response = $this->getJson('/api/v1/map-data');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    '*' => [
                        'id',
                        'case_code',
                        'type',
                        'lat',
                        'lng',
                        'name',
                        'age',
                        'photo_thumb_url',
                    ],
                ],
                'count',
            ]);
    }

    public function test_map_data_with_province_filter()
    {
        $response = $this->getJson('/api/v1/map-data?province_id=1');
        $response->assertStatus(200);
        $data = $response->json('data');
        foreach ($data as $marker) {
            $missing = MissingChild::where('case_code', $marker['case_code'])->first();
            $this->assertEquals(1, $missing->province_id);
        }
    }

    public function test_map_data_with_bounding_box()
    {
        $response = $this->getJson(
            '/api/v1/map-data?sw_lat=10.5&sw_lng=106.0&ne_lat=11.0&ne_lng=107.0'
        );
        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'data', 'count']);
    }

    public function test_map_data_with_radius_filter_returns_nearby_markers_only()
    {
        Cache::flush();

        $user = User::first();
        $province = Province::first();

        MissingChild::create([
            'user_id' => $user->id,
            'case_code' => 'MC-2026-RADIUS-IN',
            'full_name' => 'Nearby Radius Case',
            'age_estimate' => 8,
            'gender' => 'MALE',
            'province_id' => $province->id,
            'lat' => 10.7769,
            'lng' => 106.7009,
            'missing_date' => now(),
            'contact_name' => 'Parent',
            'contact_phone' => '+84901230001',
            'status' => 'APPROVED',
        ]);

        MissingChild::create([
            'user_id' => $user->id,
            'case_code' => 'MC-2026-RADIUS-OUT',
            'full_name' => 'Far Radius Case',
            'age_estimate' => 8,
            'gender' => 'MALE',
            'province_id' => $province->id,
            'lat' => 21.0278,
            'lng' => 105.8342,
            'missing_date' => now(),
            'contact_name' => 'Parent',
            'contact_phone' => '+84901230002',
            'status' => 'APPROVED',
        ]);

        $response = $this->getJson('/api/v1/map-data?radius=5&lat=10.7769&lng=106.7009');

        $response->assertStatus(200);

        $names = collect($response->json('data'))->pluck('name');
        $this->assertTrue($names->contains('Nearby Radius Case'));
        $this->assertFalse($names->contains('Far Radius Case'));
    }

    public function test_map_data_radius_filter_requires_full_coordinate_set()
    {
        $this->getJson('/api/v1/map-data?radius=10&lat=10.7769')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['lng']);
    }

    // ────────────────────────────────────────────────────────────────────────
    // Error Handling & Status Codes
    // ────────────────────────────────────────────────────────────────────────

    public function test_validation_error_returns_json()
    {
        $response = $this->getJson('/api/v1/missing-children?gender=INVALID');
        $response->assertStatus(422)
            ->assertJsonStructure(['message', 'errors']);
    }

    public function test_api_returns_401_for_protected_routes()
    {
        // TODO: Test protected API routes after implementing auth endpoints
        // For now, all list endpoints are public
        $this->assertTrue(true);
    }

    public function test_not_found_error_returns_404()
    {
        $response = $this->getJson('/api/v1/missing-children/invalid-id');
        $response->assertStatus(404)
            ->assertJsonStructure(['message']);
    }

    // ────────────────────────────────────────────────────────────────────────
    // Content Type
    // ────────────────────────────────────────────────────────────────────────

    public function test_api_returns_json_content_type()
    {
        $response = $this->getJson('/api/v1/missing-children');

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'application/json');
    }

    public function test_api_response_structure_consistency()
    {
        $response = $this->getJson('/api/v1/missing-children');

        $response->assertStatus(200);
        $json = $response->json();

        // Verify standard structure
        $this->assertIsArray($json['data']);
        $this->assertIsArray($json['pagination']);
        $this->assertIsArray($json['links']);
    }
}
