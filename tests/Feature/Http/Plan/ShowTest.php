<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Plan;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_plan_detail_with_linked_users(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = Plan::factory()->published()->create(['name' => '3 ヶ月プラン']);
        User::factory()->student()->inProgress()->withPlan($plan)->create(['name' => '受講中 太郎']);
        User::factory()->student()->graduated()->withPlan($plan)->create(['name' => '卒業 花子']);
        $withdrawn = User::factory()->student()->withdrawn()->withPlan($plan)->create(['name' => '退会 次郎']);
        $withdrawn->delete();

        $response = $this->actingAs($admin)->get(route('admin.plans.show', $plan));

        $response->assertOk();
        $response->assertViewIs('plan.management.show');
        $response->assertSee('3 ヶ月プラン');
        $response->assertSee('受講中 太郎');
        $response->assertSee('卒業 花子');
        $response->assertDontSee('退会 次郎');
        $response->assertViewHas('plan', fn ($plan) => $plan->users->count() === 2);
    }

    public function test_coach_cannot_view_plan_detail(): void
    {
        $coach = User::factory()->coach()->create();
        $plan = Plan::factory()->create();

        $this->actingAs($coach)
            ->get(route('admin.plans.show', $plan))
            ->assertForbidden();
    }
}
