<?php

declare(strict_types=1);

namespace Tests\Feature\Http\EnrollmentNote;

use App\Models\Enrollment;
use App\Models\EnrollmentNote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ContentTestHelpers;
use Tests\TestCase;

/**
 * 受講登録詳細(enrollments.show)に埋め込まれたメモ欄の表示を確認する。
 */
class ListTest extends TestCase
{
    use ContentTestHelpers, RefreshDatabase;

    public function test_assigned_coach_sees_notes_newest_first_with_edit_only_on_own(): void
    {
        $coach = User::factory()->coach()->inProgress()->create(['name' => '自分コーチ']);
        $otherCoach = User::factory()->coach()->inProgress()->create(['name' => '他のコーチ']);
        $enrollment = Enrollment::factory()->create();
        $this->assignCoach($coach, $enrollment->certification);
        $this->assignCoach($otherCoach, $enrollment->certification);
        $ownNote = EnrollmentNote::factory()->forEnrollment($enrollment)->byAuthor($coach)
            ->create(['body' => '古い自分のメモ', 'created_at' => now()->subDays(2)]);
        $otherNote = EnrollmentNote::factory()->forEnrollment($enrollment)->byAuthor($otherCoach)
            ->create(['body' => '新しい他コーチのメモ', 'created_at' => now()->subDay()]);

        $response = $this->actingAs($coach)->get(route('enrollments.show', $enrollment));

        $response->assertOk();
        $response->assertSeeInOrder(['コーチメモ', '新しい他コーチのメモ', '古い自分のメモ']);
        $response->assertSee(route('enrollment-notes.edit', $ownNote), false);
        $response->assertDontSee(route('enrollment-notes.edit', $otherNote), false);
    }

    public function test_admin_sees_edit_links_for_all_notes(): void
    {
        $admin = User::factory()->admin()->create();
        $enrollment = Enrollment::factory()->create();
        $notes = EnrollmentNote::factory()->forEnrollment($enrollment)->count(2)->create();

        $response = $this->actingAs($admin)->get(route('enrollments.show', $enrollment));

        $response->assertOk();
        foreach ($notes as $note) {
            $response->assertSee(route('enrollment-notes.edit', $note), false);
        }
    }

    public function test_student_does_not_see_note_section_on_own_enrollment(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $enrollment = Enrollment::factory()->for($student)->create();
        EnrollmentNote::factory()->forEnrollment($enrollment)->create(['body' => '受講生には見せないメモ']);

        $response = $this->actingAs($student)->get(route('enrollments.show', $enrollment));

        $response->assertOk();
        $response->assertDontSee('コーチメモ');
        $response->assertDontSee('受講生には見せないメモ');
    }

    public function test_notes_of_soft_deleted_enrollment_are_not_listed(): void
    {
        $admin = User::factory()->admin()->create();
        $enrollment = Enrollment::factory()->create();
        EnrollmentNote::factory()->forEnrollment($enrollment)->create(['body' => '削除済み受講登録のメモ']);
        $enrollment->delete();

        $response = $this->actingAs($admin)->get(route('enrollments.show', $enrollment));

        $response->assertOk();
        $response->assertDontSee('削除済み受講登録のメモ');
    }
}
