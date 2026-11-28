<?php

declare(strict_types=1);

namespace Tests\Feature\Http\EnrollmentGoal;

use App\Models\Enrollment;
use App\Models\EnrollmentGoal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_update_goal(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $enrollment = Enrollment::factory()->for($student)->create();
        $goal = EnrollmentGoal::factory()->forEnrollment($enrollment)->create();

        $response = $this->actingAs($student)->patch(route('enrollment-goals.update', $goal), [
            'title' => '更新後の目標',
            'description' => '更新後の詳細',
            'target_date' => '2027-01-10',
        ]);

        $response->assertRedirect(route('enrollments.show', $enrollment));
        $response->assertSessionHas('success');
        $goal->refresh();
        $this->assertSame('更新後の目標', $goal->title);
        $this->assertSame('更新後の詳細', $goal->description);
        $this->assertSame('2027-01-10', $goal->target_date->toDateString());
    }

    public function test_updating_achieved_goal_keeps_achieved_at(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $enrollment = Enrollment::factory()->for($student)->create();
        $achievedAt = now()->subDays(5)->startOfSecond();
        $goal = EnrollmentGoal::factory()->forEnrollment($enrollment)->create(['achieved_at' => $achievedAt]);

        $this->actingAs($student)->patch(route('enrollment-goals.update', $goal), [
            'title' => '達成済みの目標を直す',
        ])->assertSessionHasNoErrors();

        $this->assertTrue($goal->fresh()->achieved_at->equalTo($achievedAt));
    }

    public function test_empty_title_is_rejected(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $goal = EnrollmentGoal::factory()
            ->forEnrollment(Enrollment::factory()->for($student)->create())
            ->create(['title' => '元の目標']);

        $response = $this->actingAs($student)->patch(route('enrollment-goals.update', $goal), ['title' => '']);

        $response->assertSessionHasErrors('title');
        $this->assertSame('元の目標', $goal->fresh()->title);
    }

    public function test_other_student_and_admin_are_forbidden(): void
    {
        $other = User::factory()->student()->inProgress()->create();
        $admin = User::factory()->admin()->create();
        $goal = EnrollmentGoal::factory()->create(['title' => '元の目標']);

        $this->actingAs($other)->patch(route('enrollment-goals.update', $goal), ['title' => '他人'])->assertForbidden();
        $this->actingAs($admin)->patch(route('enrollment-goals.update', $goal), ['title' => '管理者'])->assertForbidden();
        $this->assertSame('元の目標', $goal->fresh()->title);
    }
}
