<?php

namespace Tests\Feature;

use App\Models\Courier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CourierIndexTest extends TestCase
{
    use RefreshDatabase;

    private const string ENDPOINT = '/api/couriers';

    public function test_index_returns_paginated_couriers_with_default_page_size(): void
    {
        Courier::factory()->count(20)->create();

        $response = $this->getJson(self::ENDPOINT);

        $response->assertOk()
            ->assertJsonCount(15, 'data')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 15)
            ->assertJsonPath('meta.total', 20)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonStructure([
                'data' => [['id', 'name', 'phone', 'email', 'level', 'registered_at']],
                'links' => ['first', 'last', 'prev', 'next'],
                'meta' => ['current_page', 'from', 'last_page', 'per_page', 'to', 'total'],
            ]);
    }

    public function test_index_respects_per_page_and_page(): void
    {
        Courier::factory()->count(12)->create();

        $response = $this->getJson(self::ENDPOINT.'?per_page=5&page=3');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.current_page', 3)
            ->assertJsonPath('meta.per_page', 5)
            ->assertJsonPath('meta.total', 12)
            ->assertJsonPath('meta.last_page', 3);
    }

    public function test_index_sorts_by_name_ascending_by_default(): void
    {
        foreach (['Citra Lestari', 'Agung Prasetyo', 'Budi Santoso'] as $name) {
            Courier::factory()->create(['name' => $name]);
        }

        $response = $this->getJson(self::ENDPOINT);

        $response->assertOk();
        $this->assertSame(['Agung Prasetyo', 'Budi Santoso', 'Citra Lestari'], $response->json('data.*.name'));
    }

    public function test_index_sorts_by_registration_date(): void
    {
        Courier::factory()->create(['name' => 'Agung Prasetyo', 'registered_at' => '2025-06-01']);
        Courier::factory()->create(['name' => 'Budi Santoso', 'registered_at' => '2024-01-01']);
        Courier::factory()->create(['name' => 'Citra Lestari', 'registered_at' => '2026-02-01']);

        $ascending = $this->getJson(self::ENDPOINT.'?sort=registered_at');
        $descending = $this->getJson(self::ENDPOINT.'?sort=registered_at&direction=desc');

        $ascending->assertOk();
        $this->assertSame(['2024-01-01', '2025-06-01', '2026-02-01'], $ascending->json('data.*.registered_at'));
        $descending->assertOk();
        $this->assertSame(['2026-02-01', '2025-06-01', '2024-01-01'], $descending->json('data.*.registered_at'));
    }

    public function test_index_sorts_by_name_descending(): void
    {
        foreach (['Citra Lestari', 'Agung Prasetyo', 'Budi Santoso'] as $name) {
            Courier::factory()->create(['name' => $name]);
        }

        $response = $this->getJson(self::ENDPOINT.'?direction=desc');

        $this->assertSame(['Citra Lestari', 'Budi Santoso', 'Agung Prasetyo'], $response->json('data.*.name'));
    }

    public function test_index_search_matches_every_word_in_any_part_of_the_name(): void
    {
        foreach (['Budiono Hadi Agung', 'Budi Santoso', 'Agung Prasetyo', 'Siti Rahmawati'] as $name) {
            Courier::factory()->create(['name' => $name]);
        }

        $response = $this->getJson(self::ENDPOINT.'?search=budi+agung');

        $response->assertOk();
        $this->assertSame(['Budiono Hadi Agung'], $response->json('data.*.name'));
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function searchTermProvider(): array
    {
        return [
            'reversed word order' => ['agung+budi'],
            'upper case' => ['BUDI+AGUNG'],
            'extra spaces' => ['%20budi%20%20agung%20'],
            'middle word' => ['hadi'],
        ];
    }

    #[DataProvider('searchTermProvider')]
    public function test_index_search_variations_find_the_courier(string $searchTerm): void
    {
        Courier::factory()->create(['name' => 'Budiono Hadi Agung']);
        Courier::factory()->create(['name' => 'Siti Rahmawati']);

        $response = $this->getJson(self::ENDPOINT."?search={$searchTerm}");

        $response->assertOk();
        $this->assertSame(['Budiono Hadi Agung'], $response->json('data.*.name'));
    }

    public function test_index_search_returns_empty_data_when_nothing_matches(): void
    {
        Courier::factory()->create(['name' => 'Budiono Hadi Agung']);

        $response = $this->getJson(self::ENDPOINT.'?search=joko');

        $response->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.total', 0);
    }

    public function test_index_filters_by_multiple_levels(): void
    {
        foreach (range(Courier::MIN_LEVEL, Courier::MAX_LEVEL) as $level) {
            Courier::factory()->count(2)->create(['level' => $level]);
        }

        $response = $this->getJson(self::ENDPOINT.'?level=2,3');

        $response->assertOk()->assertJsonPath('meta.total', 4);
        $this->assertEqualsCanonicalizing([2, 2, 3, 3], $response->json('data.*.level'));
    }

    public function test_index_filters_by_a_single_level(): void
    {
        Courier::factory()->create(['level' => 1]);
        Courier::factory()->create(['level' => 4]);

        $response = $this->getJson(self::ENDPOINT.'?level=4');

        $response->assertOk();
        $this->assertSame([4], $response->json('data.*.level'));
    }

    public function test_index_combines_search_level_sort_and_pagination(): void
    {
        Courier::factory()->create(['name' => 'Budi Santoso', 'level' => 2, 'registered_at' => '2024-01-01']);
        Courier::factory()->create(['name' => 'Budiono Hadi Agung', 'level' => 3, 'registered_at' => '2025-03-10']);
        Courier::factory()->create(['name' => 'Budiman Saputra', 'level' => 5, 'registered_at' => '2026-01-01']);
        Courier::factory()->create(['name' => 'Siti Rahmawati', 'level' => 2, 'registered_at' => '2026-02-01']);

        $response = $this->getJson(self::ENDPOINT.'?search=budi&level=2,3&sort=registered_at&direction=desc&per_page=1');

        $response->assertOk()
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('data.0.name', 'Budiono Hadi Agung');

        $nextPageUrl = $response->json('links.next');
        $this->assertStringContainsString('search=budi', $nextPageUrl);
        $this->assertStringContainsString('level=2%2C3', $nextPageUrl);
        $this->assertStringContainsString('sort=registered_at', $nextPageUrl);
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function invalidQueryProvider(): array
    {
        return [
            'level out of range' => ['level=2,9', 'level'],
            'level not a number' => ['level=dua', 'level'],
            'level trailing comma' => ['level=2,', 'level'],
            'unknown sort column' => ['sort=email', 'sort'],
            'unknown direction' => ['direction=up', 'direction'],
            'per_page above maximum' => ['per_page=101', 'per_page'],
            'per_page zero' => ['per_page=0', 'per_page'],
            'page zero' => ['page=0', 'page'],
            'search with wildcard' => ['search=bud%25', 'search'],
            'search too long' => ['search='.str_repeat('a', 101), 'search'],
        ];
    }

    #[DataProvider('invalidQueryProvider')]
    public function test_index_rejects_invalid_query_parameters(string $queryString, string $invalidField): void
    {
        $response = $this->getJson(self::ENDPOINT."?{$queryString}");

        $response->assertUnprocessable()->assertJsonValidationErrors($invalidField);
    }
}
