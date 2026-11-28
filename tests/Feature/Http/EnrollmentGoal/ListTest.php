<?php

declare(strict_types=1);

namespace Tests\Feature\Http\EnrollmentGoal;

use App\Models\Enrollment;
use App\Models\EnrollmentGoal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ContentTestHelpers;
use Tests\TestCase;

/**
 * 受講登録詳細(enrollments.show)に埋め込まれた個人目標カードの表示と、一覧の件数を確認する。
 */
class ListTest extends TestCase
{
    use ContentTestHelpers, RefreshDatabase;

    public function test_goals_are_listed_unachieved_first_by_target_date_then_achieved_newest_first(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $enrollment = Enrollment::factory()->for($student)->create();
        $make = fn (string $title, ?int $targetInDays, ?int $achievedDaysAgo) => EnrollmentGoal::factory()
            ->forEnrollment($enrollment)
            ->create([
                'title' => $title,
                'target_date' => $targetInDays === null ? null : today()->addDays($targetInDays),
                'achieved_at' => $achievedDaysAgo === null ? null : now()->subDays($achievedDaysAgo),
            ]);
        $make('古い達成', 1, 10);
        $make('期日なし', null, null);
        $make('期日が遠い', 30, null);
        $make('新しい達成', null, 1);
        $make('期日が近い', 2, null);

        $response = $this->actingAs($student)->get(route('enrollments.show', $enrollment));

        $response->assertOk();
        $response->assertSeeInOrder(['期日が近い', '期日が遠い', '期日なし', '新しい達成', '古い達成']);
    }

    public function test_owner_sees_form_and_action_buttons(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $enrollment = Enrollment::factory()->for($student)->create();
        $goal = EnrollmentGoal::factory()->forEnrollment($enrollment)->create();

        $response = $this->actingAs($student)->get(route('enrollments.show', $enrollment));

        $response->assertSee(route('enrollments.goals.store', $enrollment), false);
        $response->assertSee(route('enrollment-goals.edit', $goal), false);
        $response->assertSee(route('enrollment-goals.markAchieved', $goal), false);
    }

    public function test_assigned_coach_and_admin_see_goals_without_form_or_buttons(): void
    {
        $coach = User::factory()->coach()->inProgress()->create();
        $admin = User::factory()->admin()->create();
        $enrollment = Enrollment::factory()->create();
        $goal = EnrollmentGoal::factory()->forEnrollment($enrollment)->create(['title' => '受講生が立てた目標']);
        $this->assignCoach($coach, $enrollment->certification);

        foreach ([$coach, $admin] as $viewer) {
            $response = $this->actingAs($viewer)->get(route('enrollments.show', $enrollment));

            $response->assertOk();
            $response->assertSee('受講生が立てた目標');
            $response->assertDontSee(route('enrollments.goals.store', $enrollment), false);
            $response->assertDontSee(route('enrollment-goals.edit', $goal), false);
            $response->assertDontSee(route('enrollment-goals.markAchieved', $goal), false);
        }
    }

    public function test_graduated_student_sees_goals_without_form(): void
    {
        $student = User::factory()->student()->graduated()->create();
        $enrollment = Enrollment::factory()->for($student)->create();
        EnrollmentGoal::factory()->forEnrollment($enrollment)->create(['title' => '卒業前の目標']);

        $response = $this->actingAs($student)->get(route('enrollments.show', $enrollment));

        $response->assertOk();
        $response->assertSee('卒業前の目標');
        $response->assertDontSee(route('enrollments.goals.store', $enrollment), false);
    }

    public function test_enrollment_index_shows_goal_count(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $enrollment = Enrollment::factory()->for($student)->create();
        EnrollmentGoal::factory()->forEnrollment($enrollment)->count(3)->create();

        $response = $this->actingAs($student)->get(route('enrollments.index'));

        $response->assertOk();
        $response->assertViewHas('enrollments', fn ($enrollments) => $enrollments->first()->goals_count === 3);
    }
}
