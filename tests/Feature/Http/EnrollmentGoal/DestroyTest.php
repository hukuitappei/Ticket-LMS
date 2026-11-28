<?php

declare(strict_types=1);

namespace Tests\Feature\Http\EnrollmentGoal;

use App\Models\Enrollment;
use App\Models\EnrollmentGoal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ContentTestHelpers;
use Tests\TestCase;

class DestroyTest extends TestCase
{
    use ContentTestHelpers, RefreshDatabase;

    public function test_owner_can_delete_goal(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $enrollment = Enrollment::factory()->for($student)->create();
        $goal = EnrollmentGoal::factory()->forEnrollment($enrollment)->create();

        $response = $this->actingAs($student)->delete(route('enrollment-goals.destroy', $goal));

        $response->assertRedirect(route('enrollments.show', $enrollment));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('enrollment_goals', ['id' => $goal->id]);
    }

    public function test_other_student_and_coach_are_forbidden(): void
    {
        $other = User::factory()->student()->inProgress()->create();
        $coach = User::factory()->coach()->inProgress()->create();
        $goal = EnrollmentGoal::factory()->create();
        $this->assignCoach($coach, $goal->enrollment->certification);

        $this->actingAs($other)->delete(route('enrollment-goals.destroy', $goal))->assertForbidden();
        $this->actingAs($coach)->delete(route('enrollment-goals.destroy', $goal))->assertForbidden();
        $this->assertDatabaseHas('enrollment_goals', ['id' => $goal->id]);
    }

    public function test_goals_are_deleted_when_enrollment_is_force_deleted(): void
    {
        $enrollment = Enrollment::factory()->create();
        EnrollmentGoal::factory()->forEnrollment($enrollment)->count(2)->create();

        $enrollment->forceDelete();

        $this->assertDatabaseCount('enrollment_goals', 0);
    }
}
