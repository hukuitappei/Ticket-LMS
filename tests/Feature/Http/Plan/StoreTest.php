<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Plan;

use App\Models\Plan;
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
            'name' => '新規 3 ヶ月プラン',
            'description' => '説明文',
            'duration_days' => 90,
            'default_meeting_quota' => 12,
            'sort_order' => 10,
        ], $override);
    }

    public function test_admin_can_create_plan_as_draft(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('admin.plans.store'), $this->payload());

        $plan = Plan::firstWhere('name', '新規 3 ヶ月プラン');
        $response->assertRedirect(route('admin.plans.show', $plan));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('plans', [
            'name' => '新規 3 ヶ月プラン',
            'duration_days' => 90,
            'default_meeting_quota' => 12,
            'status' => 'draft',
            'created_by_user_id' => $admin->id,
            'updated_by_user_id' => $admin->id,
        ]);
    }

    public function test_sort_order_defaults_to_zero_when_empty(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.plans.store'), $this->payload(['sort_order' => '']));

        $this->assertDatabaseHas('plans', ['name' => '新規 3 ヶ月プラン', 'sort_order' => 0]);
    }

    public function test_boundary_values_are_accepted(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.plans.store'), $this->payload(['duration_days' => 1, 'default_meeting_quota' => 0]))
            ->assertSessionHasNoErrors();

        $this->actingAs($admin)
            ->post(route('admin.plans.store'), $this->payload(['duration_days' => 3650, 'default_meeting_quota' => 1000]))
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
            '受講期間が空' => ['duration_days', ''],
            '受講期間が0' => ['duration_days', 0],
            '受講期間が3651' => ['duration_days', 3651],
            '受講期間が小数' => ['duration_days', 1.5],
            '面談回数が空' => ['default_meeting_quota', ''],
            '面談回数がマイナス' => ['default_meeting_quota', -1],
            '面談回数が1001' => ['default_meeting_quota', 1001],
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
            ->post(route('admin.plans.store'), $this->payload([$field => $value]))
            ->assertSessionHasErrors($field);

        $this->assertDatabaseMissing('plans', ['name' => '新規 3 ヶ月プラン']);
    }

    public function test_coach_cannot_create_plan(): void
    {
        $coach = User::factory()->coach()->create();

        $this->actingAs($coach)
            ->post(route('admin.plans.store'), $this->payload())
            ->assertForbidden();

        $this->assertDatabaseMissing('plans', ['name' => '新規 3 ヶ月プラン']);
    }
}
