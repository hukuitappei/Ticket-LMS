<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Plan;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateTest extends TestCase
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
            'name' => '改定後プラン',
            'description' => '期間改定',
            'duration_days' => 120,
            'default_meeting_quota' => 16,
            'sort_order' => 3,
        ], $override);
    }

    public function test_admin_can_update_published_plan_without_changing_status(): void
    {
        $creator = User::factory()->admin()->create();
        $editor = User::factory()->admin()->create();
        $plan = Plan::factory()->published()->create(['created_by_user_id' => $creator->id]);

        $response = $this->actingAs($editor)->put(route('admin.plans.update', $plan), $this->payload());

        $response->assertRedirect(route('admin.plans.show', $plan));
        $response->assertSessionHas('success');
        $plan->refresh();
        $this->assertSame('改定後プラン', $plan->name);
        $this->assertSame(120, $plan->duration_days);
        $this->assertSame(16, $plan->default_meeting_quota);
        $this->assertSame('published', $plan->status->value);
        $this->assertSame($creator->id, $plan->created_by_user_id);
        $this->assertSame($editor->id, $plan->updated_by_user_id);
    }

    public function test_update_does_not_change_linked_users_plan_terms(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = Plan::factory()->published()->create(['duration_days' => 90, 'default_meeting_quota' => 12]);
        $student = User::factory()->student()->inProgress()->withPlan($plan)->create();
        $expiresAt = $student->plan_expires_at;

        $this->actingAs($admin)->put(route('admin.plans.update', $plan), $this->payload());

        $student->refresh();
        $this->assertTrue($expiresAt->equalTo($student->plan_expires_at));
        $this->assertSame(12, $student->max_meetings);
    }

    public function test_status_in_request_is_ignored(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = Plan::factory()->draft()->create();

        $this->actingAs($admin)->put(route('admin.plans.update', $plan), $this->payload(['status' => 'published']));

        $this->assertSame('draft', $plan->fresh()->status->value);
    }

    public function test_invalid_input_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = Plan::factory()->create(['duration_days' => 90]);

        $this->actingAs($admin)
            ->put(route('admin.plans.update', $plan), $this->payload(['duration_days' => 0]))
            ->assertSessionHasErrors('duration_days');

        $this->assertSame(90, $plan->fresh()->duration_days);
    }

    public function test_coach_cannot_update_plan(): void
    {
        $coach = User::factory()->coach()->create();
        $plan = Plan::factory()->create();

        $this->actingAs($coach)
            ->put(route('admin.plans.update', $plan), $this->payload())
            ->assertForbidden();
    }
}
