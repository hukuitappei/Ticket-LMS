<?php

declare(strict_types=1);

namespace Tests\Feature\Http\EnrollmentGoal;

use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ContentTestHelpers;
use Tests\TestCase;

class StoreTest extends TestCase
{
    use ContentTestHelpers, RefreshDatabase;

    public function test_student_can_add_goal_to_own_enrollment(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $enrollment = Enrollment::factory()->for($student)->create();

        $response = $this->actingAs($student)->post(route('enrollments.goals.store', $enrollment), [
            'title' => '過去問 5 年分を解き終える',
            'description' => '1 日 1 回分のペースで進める。',
            'target_date' => '2026-12-20',
        ]);

        $response->assertRedirect(route('enrollments.show', $enrollment));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('enrollment_goals', [
            'enrollment_id' => $enrollment->id,
            'title' => '過去問 5 年分を解き終える',
            'description' => '1 日 1 回分のペースで進める。',
            'achieved_at' => null,
        ]);
    }

    public function test_description_and_target_date_are_optional(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $enrollment = Enrollment::factory()->for($student)->create();

        $response = $this->actingAs($student)->post(route('enrollments.goals.store', $enrollment), [
            'title' => '毎朝 30 分学習する',
            'description' => '',
            'target_date' => '',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('enrollment_goals', [
            'enrollment_id' => $enrollment->id,
            'description' => null,
            'target_date' => null,
        ]);
    }

    public function test_past_target_date_and_max_length_are_accepted(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $enrollment = Enrollment::factory()->for($student)->create();

        $response = $this->actingAs($student)->post(route('enrollments.goals.store', $enrollment), [
            'title' => str_repeat('あ', 100),
            'description' => str_repeat('い', 1000),
            'target_date' => now()->subDays(3)->toDateString(),
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseCount('enrollment_goals', 1);
    }

    public function test_goal_can_be_added_to_passed_enrollment(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $enrollment = Enrollment::factory()->for($student)->passed()->create();

        $response = $this->actingAs($student)->post(route('enrollments.goals.store', $enrollment), [
            'title' => '振り返りをまとめる',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseCount('enrollment_goals', 1);
    }

    /**
     * @return array<string, array{0: string, 1: array<string, mixed>}>
     */
    public static function invalidInputProvider(): array
    {
        return [
            'タイトルが空' => ['title', ['title' => '']],
            'タイトルが101文字' => ['title', ['title' => str_repeat('あ', 101)]],
            '詳細が1001文字' => ['description', ['title' => '目標', 'description' => str_repeat('い', 1001)]],
            '期日が日付でない' => ['target_date', ['title' => '目標', 'target_date' => 'あした']],
        ];
    }

    /**
     * @dataProvider invalidInputProvider
     *
     * @param array<string, mixed> $input
     */
    public function test_invalid_input_is_rejected(string $field, array $input): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $enrollment = Enrollment::factory()->for($student)->create();

        $response = $this->actingAs($student)->post(route('enrollments.goals.store', $enrollment), $input);

        $response->assertSessionHasErrors($field);
        $this->assertDatabaseCount('enrollment_goals', 0);
    }

    public function test_other_student_is_forbidden(): void
    {
        $other = User::factory()->student()->inProgress()->create();
        $enrollment = Enrollment::factory()->create();

        $response = $this->actingAs($other)->post(route('enrollments.goals.store', $enrollment), ['title' => '他人の目標']);

        $response->assertForbidden();
        $this->assertDatabaseCount('enrollment_goals', 0);
    }

    public function test_graduated_student_is_forbidden(): void
    {
        $student = User::factory()->student()->graduated()->create();
        $enrollment = Enrollment::factory()->for($student)->create();

        $response = $this->actingAs($student)->post(route('enrollments.goals.store', $enrollment), ['title' => '卒業後の目標']);

        $response->assertForbidden();
        $this->assertDatabaseCount('enrollment_goals', 0);
    }

    public function test_assigned_coach_and_admin_are_forbidden(): void
    {
        $coach = User::factory()->coach()->inProgress()->create();
        $admin = User::factory()->admin()->create();
        $enrollment = Enrollment::factory()->create();
        $this->assignCoach($coach, $enrollment->certification);

        $this->actingAs($coach)->post(route('enrollments.goals.store', $enrollment), ['title' => 'コーチ'])->assertForbidden();
        $this->actingAs($admin)->post(route('enrollments.goals.store', $enrollment), ['title' => '管理者'])->assertForbidden();
        $this->assertDatabaseCount('enrollment_goals', 0);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $enrollment = Enrollment::factory()->create();

        $response = $this->post(route('enrollments.goals.store', $enrollment), ['title' => 'x']);

        $response->assertRedirect(route('login'));
    }
}
