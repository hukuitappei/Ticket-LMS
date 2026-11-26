<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Plan;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_plan_list(): void
    {
        $admin = User::factory()->admin()->create();
        Plan::factory()->count(3)->create();

        $response = $this->actingAs($admin)->get(route('admin.plans.index'));

        $response->assertOk();
        $response->assertViewIs('plan.management.index');
        $response->assertViewHas('plans', fn ($plans) => $plans->total() === 3);
    }

    public function test_users_count_includes_only_in_progress_users(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = Plan::factory()->published()->create();
        User::factory()->student()->inProgress()->withPlan($plan)->count(2)->create();
        User::factory()->student()->invited()->withPlan($plan)->create();
        User::factory()->student()->graduated()->withPlan($plan)->create();

        $response = $this->actingAs($admin)->get(route('admin.plans.index'));

        $response->assertViewHas('plans', fn ($plans) => $plans->first()->users_count === 2);
    }

    public function test_list_is_ordered_by_sort_order_then_newest(): void
    {
        $admin = User::factory()->admin()->create();
        $second = Plan::factory()->create(['sort_order' => 1, 'created_at' => now()->subDay()]);
        $third = Plan::factory()->create(['sort_order' => 2]);
        $first = Plan::factory()->create(['sort_order' => 1, 'created_at' => now()]);

        $response = $this->actingAs($admin)->get(route('admin.plans.index'));

        $response->assertViewHas('plans', fn ($plans) => $plans->pluck('id')->all() === [$first->id, $second->id, $third->id]);
    }

    public function test_keyword_and_status_filter_plans(): void
    {
        $admin = User::factory()->admin()->create();
        $target = Plan::factory()->published()->create(['name' => '3 ヶ月プラン']);
        Plan::factory()->draft()->create(['name' => '3 ヶ月プラン(検討中)']);
        Plan::factory()->published()->create(['name' => '6 ヶ月プラン']);

        $response = $this->actingAs($admin)->get(route('admin.plans.index', ['keyword' => '3 ヶ月', 'status' => 'published']));

        $response->assertViewHas('plans', fn ($plans) => $plans->pluck('id')->all() === [$target->id]);
    }

    public function test_invalid_status_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.plans.index', ['status' => 'unknown']))
            ->assertSessionHasErrors('status');
    }

    public function test_coach_and_student_cannot_view_list(): void
    {
        $this->actingAs(User::factory()->coach()->create())
            ->get(route('admin.plans.index'))
            ->assertForbidden();

        $this->actingAs(User::factory()->student()->create())
            ->get(route('admin.plans.index'))
            ->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.plans.index'))->assertRedirect(route('login'));
    }
}
