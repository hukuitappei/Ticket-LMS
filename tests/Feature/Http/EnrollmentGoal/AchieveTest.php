<?php

declare(strict_types=1);

namespace Tests\Feature\Http\EnrollmentGoal;

use App\Models\Enrollment;
use App\Models\EnrollmentGoal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Support\ContentTestHelpers;
use Tests\TestCase;

/**
 * 達成マーク(POST)と達成解除(DELETE)を確認する。
 */
class AchieveTest extends TestCase
{
    use ContentTestHelpers, RefreshDatabase;

    public function test_owner_can_mark_goal_as_achieved(): void
    {
        Carbon::setTestNow('2026-12-01 10:00:00');
        $student = User::factory()->student()->inProgress()->create();
        $enrollment = Enrollment::factory()->for($student)->create();
        $goal = EnrollmentGoal::factory()->forEnrollment($enrollment)->create();

        $response = $this->actingAs($student)->post(route('enrollment-goals.markAchieved', $goal));

        $response->assertRedirect(route('enrollments.show', $enrollment));
        $response->assertSessionHas('success');
        $this->assertSame('2026-12-01 10:00:00', $goal->fresh()->achieved_at->toDateTimeString());
    }

    public function test_marking_achieved_goal_again_keeps_first_achieved_at(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $achievedAt = now()->subDays(3)->startOfSecond();
        $goal = EnrollmentGoal::factory()
            ->forEnrollment(Enrollment::factory()->for($student)->create())
            ->create(['achieved_at' => $achievedAt]);

        $this->actingAs($student)->post(route('enrollment-goals.markAchieved', $goal))->assertRedirect();

        $this->assertTrue($goal->fresh()->achieved_at->equalTo($achievedAt));
    }

    public function test_owner_can_unmark_achieved_goal(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $enrollment = Enrollment::factory()->for($student)->create();
        $goal = EnrollmentGoal::factory()->forEnrollment($enrollment)->achieved()->create();

        $response = $this->actingAs($student)->delete(route('enrollment-goals.unmarkAchieved', $goal));

        $response->assertRedirect(route('enrollments.show', $enrollment));
        $this->assertNull($goal->fresh()->achieved_at);
    }

    public function test_coach_and_admin_cannot_mark_or_unmark(): void
    {
        $coach = User::factory()->coach()->inProgress()->create();
        $admin = User::factory()->admin()->create();
        $goal = EnrollmentGoal::factory()->create();
        $achievedGoal = EnrollmentGoal::factory()->forEnrollment($goal->enrollment)->achieved()->create();
        $this->assignCoach($coach, $goal->enrollment->certification);

        $this->actingAs($coach)->post(route('enrollment-goals.markAchieved', $goal))->assertForbidden();
        $this->actingAs($admin)->post(route('enrollment-goals.markAchieved', $goal))->assertForbidden();
        $this->actingAs($coach)->delete(route('enrollment-goals.unmarkAchieved', $achievedGoal))->assertForbidden();
        $this->actingAs($admin)->delete(route('enrollment-goals.unmarkAchieved', $achievedGoal))->assertForbidden();

        $this->assertNull($goal->fresh()->achieved_at);
        $this->assertNotNull($achievedGoal->fresh()->achieved_at);
    }

    public function test_graduated_student_cannot_mark_own_goal(): void
    {
        $student = User::factory()->student()->graduated()->create();
        $goal = EnrollmentGoal::factory()
            ->forEnrollment(Enrollment::factory()->for($student)->create())
            ->create();

        $this->actingAs($student)->post(route('enrollment-goals.markAchieved', $goal))->assertForbidden();
        $this->assertNull($goal->fresh()->achieved_at);
    }
}
