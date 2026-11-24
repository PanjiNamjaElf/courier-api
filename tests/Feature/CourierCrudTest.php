<?php

namespace Tests\Feature;

use App\Models\Courier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CourierCrudTest extends TestCase
{
    use RefreshDatabase;

    private const string ENDPOINT = '/api/couriers';

    /**
     * Payload valid untuk store/update.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return [
            'name' => 'Budiono Hadi Agung',
            'phone' => '081234567890',
            'email' => 'budiono@example.com',
            'level' => 3,
            'registered_at' => '2025-03-10',
            ...$overrides,
        ];
    }

    public function test_store_saves_a_courier_to_the_database(): void
    {
        $response = $this->postJson(self::ENDPOINT, $this->validPayload());

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Budiono Hadi Agung')
            ->assertJsonPath('data.level', 3)
            ->assertJsonPath('data.registered_at', '2025-03-10')
            ->assertJsonStructure(['data' => ['id', 'name', 'phone', 'email', 'level', 'registered_at', 'created_at', 'updated_at']]);

        $this->assertDatabaseCount('couriers', 1);
        $this->assertDatabaseHas('couriers', [
            'id' => $response->json('data.id'),
            'name' => 'Budiono Hadi Agung',
            'phone' => '081234567890',
            'email' => 'budiono@example.com',
            'level' => 3,
        ]);
    }

    public function test_store_defaults_registration_date_to_today_and_accepts_missing_email(): void
    {
        $this->travelTo('2026-11-24 10:00:00');

        $response = $this->postJson(self::ENDPOINT, $this->validPayload([
            'email' => null,
            'registered_at' => null,
        ]));

        $response->assertCreated()
            ->assertJsonPath('data.email', null)
            ->assertJsonPath('data.registered_at', '2026-11-24');
    }

    public function test_store_normalizes_name_spacing_and_email_case(): void
    {
        $response = $this->postJson(self::ENDPOINT, $this->validPayload([
            'name' => '  Budiono   Hadi  Agung ',
            'email' => 'Budiono@Example.COM',
        ]));

        $response->assertCreated();
        $this->assertDatabaseHas('couriers', [
            'name' => 'Budiono Hadi Agung',
            'email' => 'budiono@example.com',
        ]);
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: string}>
     */
    public static function invalidPayloadProvider(): array
    {
        return [
            'name missing' => [['name' => null], 'name'],
            'name too short' => [['name' => 'Bo'], 'name'],
            'name too long' => [['name' => str_repeat('a', 101)], 'name'],
            'name with digits' => [['name' => 'Budi 123'], 'name'],
            'phone missing' => [['phone' => null], 'phone'],
            'phone not indonesian' => [['phone' => '5551234567'], 'phone'],
            'phone with letters' => [['phone' => '0812abc4567'], 'phone'],
            'email invalid' => [['email' => 'bukan-email'], 'email'],
            'level missing' => [['level' => null], 'level'],
            'level below minimum' => [['level' => 0], 'level'],
            'level above maximum' => [['level' => 6], 'level'],
            'level not a number' => [['level' => 'tinggi'], 'level'],
            'registered_at wrong format' => [['registered_at' => '10-03-2025'], 'registered_at'],
            'registered_at in the future' => [['registered_at' => '2999-01-01'], 'registered_at'],
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    #[DataProvider('invalidPayloadProvider')]
    public function test_store_rejects_invalid_input(array $overrides, string $invalidField): void
    {
        $response = $this->postJson(self::ENDPOINT, $this->validPayload($overrides));

        $response->assertUnprocessable()->assertJsonValidationErrors($invalidField);
        $this->assertDatabaseCount('couriers', 0);
    }

    public function test_store_rejects_duplicate_phone_and_email(): void
    {
        Courier::factory()->create(['phone' => '081234567890', 'email' => 'budiono@example.com']);

        $response = $this->postJson(self::ENDPOINT, $this->validPayload());

        $response->assertUnprocessable()->assertJsonValidationErrors(['phone', 'email']);
        $this->assertDatabaseCount('couriers', 1);
    }

    public function test_validation_messages_are_in_indonesian(): void
    {
        $response = $this->postJson(self::ENDPOINT, []);

        $response->assertUnprocessable()
            ->assertJsonPath('errors.name.0', 'Nama wajib diisi.')
            ->assertJsonPath('errors.level.0', 'Level wajib diisi.');
    }

    public function test_show_returns_all_courier_data(): void
    {
        $courier = Courier::factory()->create([
            'name' => 'Siti Rahmawati',
            'phone' => '081200000004',
            'email' => 'siti@example.com',
            'level' => 1,
            'registered_at' => '2026-01-15',
        ]);

        $response = $this->getJson(self::ENDPOINT."/{$courier->id}");

        $response->assertOk()->assertExactJson([
            'data' => [
                'id' => $courier->id,
                'name' => 'Siti Rahmawati',
                'phone' => '081200000004',
                'email' => 'siti@example.com',
                'level' => 1,
                'registered_at' => '2026-01-15',
                'created_at' => $courier->created_at->toISOString(),
                'updated_at' => $courier->updated_at->toISOString(),
            ],
        ]);
    }

    public function test_show_returns_not_found_for_unknown_courier(): void
    {
        $response = $this->getJson(self::ENDPOINT.'/999');

        $response->assertNotFound()->assertExactJson(['message' => 'Data tidak ditemukan.']);
    }

    public function test_update_with_put_saves_all_changes_to_the_database(): void
    {
        $courier = Courier::factory()->create();

        $response = $this->putJson(self::ENDPOINT."/{$courier->id}", $this->validPayload());

        $response->assertOk()->assertJsonPath('data.name', 'Budiono Hadi Agung');
        $this->assertDatabaseHas('couriers', [
            'id' => $courier->id,
            'name' => 'Budiono Hadi Agung',
            'phone' => '081234567890',
            'email' => 'budiono@example.com',
            'level' => 3,
        ]);
        $this->assertSame('2025-03-10', $courier->fresh()->registered_at->toDateString());
    }

    public function test_update_with_patch_changes_only_the_given_fields(): void
    {
        $courier = Courier::factory()->create(['name' => 'Budi Santoso', 'level' => 2]);

        $response = $this->patchJson(self::ENDPOINT."/{$courier->id}", ['level' => 5]);

        $response->assertOk()
            ->assertJsonPath('data.level', 5)
            ->assertJsonPath('data.name', 'Budi Santoso');
        $this->assertDatabaseHas('couriers', [
            'id' => $courier->id,
            'name' => 'Budi Santoso',
            'phone' => $courier->phone,
            'level' => 5,
        ]);
    }

    public function test_update_allows_keeping_own_phone_and_email(): void
    {
        $courier = Courier::factory()->create();

        $response = $this->putJson(self::ENDPOINT."/{$courier->id}", $this->validPayload([
            'phone' => $courier->phone,
            'email' => $courier->email,
        ]));

        $response->assertOk();
    }

    public function test_update_rejects_phone_and_email_owned_by_another_courier(): void
    {
        $otherCourier = Courier::factory()->create();
        $courier = Courier::factory()->create();

        $response = $this->patchJson(self::ENDPOINT."/{$courier->id}", [
            'phone' => $otherCourier->phone,
            'email' => $otherCourier->email,
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors(['phone', 'email']);
        $this->assertDatabaseHas('couriers', ['id' => $courier->id, 'phone' => $courier->phone]);
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: string}>
     */
    public static function invalidUpdatePayloadProvider(): array
    {
        return [
            'name set to null' => [['name' => null], 'name'],
            'phone set to null' => [['phone' => null], 'phone'],
            'level set to null' => [['level' => null], 'level'],
            'level above maximum' => [['level' => 6], 'level'],
            'registered_at set to null' => [['registered_at' => null], 'registered_at'],
            'registered_at in the future' => [['registered_at' => '2999-01-01'], 'registered_at'],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    #[DataProvider('invalidUpdatePayloadProvider')]
    public function test_update_rejects_invalid_input(array $payload, string $invalidField): void
    {
        $courier = Courier::factory()->create(['level' => 2]);

        $response = $this->patchJson(self::ENDPOINT."/{$courier->id}", $payload);

        $response->assertUnprocessable()->assertJsonValidationErrors($invalidField);
        $this->assertDatabaseHas('couriers', ['id' => $courier->id, 'level' => 2, 'name' => $courier->name]);
    }

    public function test_update_returns_not_found_for_unknown_courier(): void
    {
        $this->putJson(self::ENDPOINT.'/999', $this->validPayload())->assertNotFound();
    }

    public function test_destroy_removes_the_courier_from_the_database(): void
    {
        $courier = Courier::factory()->create();
        $otherCourier = Courier::factory()->create();

        $response = $this->deleteJson(self::ENDPOINT."/{$courier->id}");

        $response->assertNoContent();
        $this->assertModelMissing($courier);
        $this->assertDatabaseMissing('couriers', ['id' => $courier->id]);
        $this->assertModelExists($otherCourier);
    }

    public function test_destroy_returns_not_found_for_unknown_courier(): void
    {
        $this->deleteJson(self::ENDPOINT.'/999')->assertNotFound();
    }
}
