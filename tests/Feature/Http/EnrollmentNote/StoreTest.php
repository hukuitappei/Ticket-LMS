<?php

declare(strict_types=1);

namespace Tests\Feature\Http\EnrollmentNote;

use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ContentTestHelpers;
use Tests\TestCase;

class StoreTest extends TestCase
{
    use ContentTestHelpers, RefreshDatabase;

    public function test_assigned_coach_can_add_note(): void
    {
        $coach = User::factory()->coach()->inProgress()->create();
        $enrollment = Enrollment::factory()->create();
        $this->assignCoach($coach, $enrollment->certification);

        $response = $this->actingAs($coach)->post(route('enrollments.notes.store', $enrollment), [
            'body' => '次回面談で学習時間を確認する。',
        ]);

        $response->assertRedirect(route('enrollments.show', $enrollment));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('enrollment_notes', [
            'enrollment_id' => $enrollment->id,
            'author_user_id' => $coach->id,
            'body' => '次回面談で学習時間を確認する。',
        ]);
    }

    public function test_admin_can_add_note_to_any_enrollment(): void
    {
        $admin = User::factory()->admin()->create();
        $enrollment = Enrollment::factory()->create();

        $response = $this->actingAs($admin)->post(route('enrollments.notes.store', $enrollment), [
            'body' => '運営からの観察メモ',
        ]);

        $response->assertRedirect(route('enrollments.show', $enrollment));
        $this->assertDatabaseHas('enrollment_notes', [
            'enrollment_id' => $enrollment->id,
            'author_user_id' => $admin->id,
        ]);
    }

    public function test_body_of_2000_characters_is_accepted(): void
    {
        $admin = User::factory()->admin()->create();
        $enrollment = Enrollment::factory()->create();

        $response = $this->actingAs($admin)->post(route('enrollments.notes.store', $enrollment), [
            'body' => str_repeat('あ', 2000),
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseCount('enrollment_notes', 1);
    }

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function invalidBodyProvider(): array
    {
        return [
            '空' => [''],
            '2001文字' => [str_repeat('あ', 2001)],
        ];
    }

    /**
     * @dataProvider invalidBodyProvider
     */
    public function test_invalid_body_is_rejected(mixed $body): void
    {
        $admin = User::factory()->admin()->create();
        $enrollment = Enrollment::factory()->create();

        $response = $this->actingAs($admin)->post(route('enrollments.notes.store', $enrollment), [
            'body' => $body,
        ]);

        $response->assertSessionHasErrors('body');
        $this->assertDatabaseCount('enrollment_notes', 0);
    }

    public function test_unassigned_coach_is_forbidden(): void
    {
        $coach = User::factory()->coach()->inProgress()->create();
        $enrollment = Enrollment::factory()->create();

        $response = $this->actingAs($coach)->post(route('enrollments.notes.store', $enrollment), [
            'body' => '担当外',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('enrollment_notes', 0);
    }

    public function test_student_is_forbidden_even_for_own_enrollment(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $enrollment = Enrollment::factory()->for($student)->create();

        $response = $this->actingAs($student)->post(route('enrollments.notes.store', $enrollment), [
            'body' => '自分で書く',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('enrollment_notes', 0);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $enrollment = Enrollment::factory()->create();

        $response = $this->post(route('enrollments.notes.store', $enrollment), ['body' => 'x']);

        $response->assertRedirect(route('login'));
    }
}
