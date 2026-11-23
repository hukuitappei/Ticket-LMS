<?php

declare(strict_types=1);

namespace Tests\Feature\Http\MeetingPack;

use App\Models\MeetingPack;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param array<string, mixed> $override
     *
     * @return array<string, mixed>
     */
    private function payload(array $override = []): array
    {
        return array_merge([
            'name' => '新規 5 回パック',
            'description' => '説明文',
            'meeting_count' => 5,
            'price' => 12000,
            'stripe_price_id' => '',
            'sort_order' => 10,
        ], $override);
    }

    public function test_admin_can_create_meeting_pack_as_draft(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('admin.meeting-packs.store'), $this->payload());

        $plan = MeetingPack::firstWhere('name', '新規 5 回パック');
        $response->assertRedirect(route('admin.meeting-packs.show', $plan));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('meeting_packs', [
            'name' => '新規 5 回パック',
            'meeting_count' => 5,
            'price' => 12000,
            'stripe_price_id' => null,
            'status' => 'draft',
            'created_by_user_id' => $admin->id,
            'updated_by_user_id' => $admin->id,
        ]);
    }

    public function test_sort_order_defaults_to_zero_when_empty(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.meeting-packs.store'), $this->payload(['sort_order' => '']));

        $this->assertDatabaseHas('meeting_packs', ['name' => '新規 5 回パック', 'sort_order' => 0]);
    }

    public function test_boundary_values_are_accepted(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.meeting-packs.store'), $this->payload(['meeting_count' => 1, 'price' => 0]))
            ->assertSessionHasNoErrors();

        $this->actingAs($admin)
            ->post(route('admin.meeting-packs.store'), $this->payload(['meeting_count' => 100, 'price' => 1000000]))
            ->assertSessionHasNoErrors();
    }

    /**
     * @return array<string, array{0: string, 1: mixed}>
     */
    public static function invalidInputProvider(): array
    {
        return [
            '名前が空' => ['name', ''],
            '名前が101文字' => ['name', str_repeat('あ', 101)],
            '説明が2001文字' => ['description', str_repeat('あ', 2001)],
            '面談回数が空' => ['meeting_count', ''],
            '面談回数が0' => ['meeting_count', 0],
            '面談回数が101' => ['meeting_count', 101],
            '面談回数が小数' => ['meeting_count', 1.5],
            '価格が空' => ['price', ''],
            '価格がマイナス' => ['price', -1],
            '価格が上限超え' => ['price', 1000001],
            'Price IDが256文字' => ['stripe_price_id', str_repeat('a', 256)],
            '並び順がマイナス' => ['sort_order', -1],
            '並び順が65536' => ['sort_order', 65536],
        ];
    }

    /**
     * @dataProvider invalidInputProvider
     */
    public function test_invalid_input_is_rejected(string $field, mixed $value): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.meeting-packs.store'), $this->payload([$field => $value]))
            ->assertSessionHasErrors($field);

        $this->assertDatabaseCount('meeting_packs', 0);
    }

    public function test_coach_cannot_create_meeting_pack(): void
    {
        $coach = User::factory()->coach()->create();

        $this->actingAs($coach)
            ->post(route('admin.meeting-packs.store'), $this->payload())
            ->assertForbidden();

        $this->assertDatabaseCount('meeting_packs', 0);
    }
}
