<?php

declare(strict_types=1);

namespace Tests\Feature\Http\EnrollmentGoal;

use App\Models\Enrollment;
use App\Models\EnrollmentGoal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ContentTestHelpers;
use Tests\TestCase;

class EditTest extends TestCase
{
    use ContentTestHelpers, RefreshDatabase;

    public function test_owner_can_open_edit_page(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $goal = EnrollmentGoal::factory()
            ->forEnrollment(Enrollment::factory()->for($student)->create())
            ->create(['title' => '編集前の目標']);

        $response = $this->actingAs($student)->get(route('enrollment-goals.edit', $goal));

        $response->assertOk();
        $response->assertViewIs('enrollment-goal.edit');
        $response->assertSee('編集前の目標');
    }

    public function test_other_student_is_forbidden(): void
    {
        $other = User::factory()->student()->inProgress()->create();
        $goal = EnrollmentGoal::factory()->create();

        $this->actingAs($other)->get(route('enrollment-goals.edit', $goal))->assertForbidden();
    }

    public function test_assigned_coach_is_forbidden(): void
    {
        $coach = User::factory()->coach()->inProgress()->create();
        $goal = EnrollmentGoal::factory()->create();
        $this->assignCoach($coach, $goal->enrollment->certification);

        $this->actingAs($coach)->get(route('enrollment-goals.edit', $goal))->assertForbidden();
    }

    public function test_goal_of_soft_deleted_enrollment_is_forbidden(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $enrollment = Enrollment::factory()->for($student)->create();
        $goal = EnrollmentGoal::factory()->forEnrollment($enrollment)->create();
        $enrollment->delete();

        $this->actingAs($student)->get(route('enrollment-goals.edit', $goal))->assertForbidden();
    }
}
