<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Plan;

use App\Models\Plan;
use App\Models\User;
use App\Models\UserPlanLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DestroyTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_delete_draft_plan_without_users(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = Plan::factory()->draft()->create();

        $response = $this->actingAs($admin)->delete(route('admin.plans.destroy', $plan));

        $response->assertRedirect(route('admin.plans.index'));
        $response->assertSessionHas('success');
        $this->assertModelMissing($plan);
    }

    public function test_cannot_delete_published_or_archived_plan(): void
    {
        $admin = User::factory()->admin()->create();
        $published = Plan::factory()->published()->create();
        $archived = Plan::factory()->archived()->create();

        $this->actingAs($admin)->deleteJson(route('admin.plans.destroy', $published))->assertStatus(409);
        $this->actingAs($admin)->deleteJson(route('admin.plans.destroy', $archived))->assertStatus(409);

        $this->assertModelExists($published);
        $this->assertModelExists($archived);
    }

    public function test_cannot_delete_draft_plan_with_linked_user(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = Plan::factory()->draft()->create();
        User::factory()->student()->invited()->withPlan($plan)->create();

        $this->actingAs($admin)
            ->deleteJson(route('admin.plans.destroy', $plan))
            ->assertStatus(409);

        $this->assertModelExists($plan);
    }

    public function test_cannot_delete_draft_plan_linked_only_to_withdrawn_user(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = Plan::factory()->draft()->create();
        User::factory()->student()->withdrawn()->withPlan($plan)->create()->delete();

        $this->actingAs($admin)
            ->deleteJson(route('admin.plans.destroy', $plan))
            ->assertStatus(409);

        $this->assertModelExists($plan);
    }

    public function test_cannot_delete_draft_plan_with_plan_logs(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = Plan::factory()->draft()->create();
        UserPlanLog::factory()->create(['plan_id' => $plan->id]);

        $this->actingAs($admin)
            ->deleteJson(route('admin.plans.destroy', $plan))
            ->assertStatus(409);

        $this->assertModelExists($plan);
    }

    public function test_browser_delete_of_undeletable_plan_redirects_back_with_error(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = Plan::factory()->published()->create();

        $response = $this->actingAs($admin)
            ->from(route('admin.plans.show', $plan))
            ->delete(route('admin.plans.destroy', $plan));

        $response->assertRedirect(route('admin.plans.show', $plan));
        $response->assertSessionHas('error', '下書き状態で、受講者が紐づいていないプランのみ削除できます。');
    }

    public function test_coach_cannot_delete_plan(): void
    {
        $coach = User::factory()->coach()->create();
        $plan = Plan::factory()->draft()->create();

        $this->actingAs($coach)
            ->delete(route('admin.plans.destroy', $plan))
            ->assertForbidden();

        $this->assertModelExists($plan);
    }
}
