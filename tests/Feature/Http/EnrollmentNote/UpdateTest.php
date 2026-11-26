<?php

declare(strict_types=1);

namespace Tests\Feature\Http\EnrollmentNote;

use App\Models\Enrollment;
use App\Models\EnrollmentNote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ContentTestHelpers;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    use ContentTestHelpers, RefreshDatabase;

    public function test_author_coach_can_update_own_note(): void
    {
        $coach = User::factory()->coach()->inProgress()->create();
        $enrollment = Enrollment::factory()->create();
        $this->assignCoach($coach, $enrollment->certification);
        $note = EnrollmentNote::factory()->forEnrollment($enrollment)->byAuthor($coach)->create();

        $response = $this->actingAs($coach)->patch(route('enrollment-notes.update', $note), [
            'body' => '更新後の本文',
        ]);

        $response->assertRedirect(route('enrollments.show', $enrollment));
        $response->assertSessionHas('success');
        $this->assertSame('更新後の本文', $note->fresh()->body);
    }

    public function test_admin_update_keeps_original_author(): void
    {
        $admin = User::factory()->admin()->create();
        $coach = User::factory()->coach()->inProgress()->create();
        $note = EnrollmentNote::factory()->byAuthor($coach)->create();

        $response = $this->actingAs($admin)->patch(route('enrollment-notes.update', $note), [
            'body' => '管理者が是正した本文',
        ]);

        $response->assertRedirect(route('enrollments.show', $note->enrollment_id));
        $note->refresh();
        $this->assertSame('管理者が是正した本文', $note->body);
        $this->assertSame($coach->id, $note->author_user_id);
    }

    public function test_empty_body_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        $note = EnrollmentNote::factory()->create(['body' => '元の本文']);

        $response = $this->actingAs($admin)->patch(route('enrollment-notes.update', $note), [
            'body' => '',
        ]);

        $response->assertSessionHasErrors('body');
        $this->assertSame('元の本文', $note->fresh()->body);
    }

    public function test_other_coach_cannot_update(): void
    {
        $author = User::factory()->coach()->inProgress()->create();
        $otherCoach = User::factory()->coach()->inProgress()->create();
        $enrollment = Enrollment::factory()->create();
        $this->assignCoach($author, $enrollment->certification);
        $this->assignCoach($otherCoach, $enrollment->certification);
        $note = EnrollmentNote::factory()->forEnrollment($enrollment)->byAuthor($author)->create(['body' => '元の本文']);

        $response = $this->actingAs($otherCoach)->patch(route('enrollment-notes.update', $note), [
            'body' => '書き換え',
        ]);

        $response->assertForbidden();
        $this->assertSame('元の本文', $note->fresh()->body);
    }

    public function test_author_coach_removed_from_assignment_cannot_update(): void
    {
        $coach = User::factory()->coach()->inProgress()->create();
        $note = EnrollmentNote::factory()->byAuthor($coach)->create(['body' => '元の本文']);

        $response = $this->actingAs($coach)->patch(route('enrollment-notes.update', $note), [
            'body' => '担当外になった後の書き換え',
        ]);

        $response->assertForbidden();
        $this->assertSame('元の本文', $note->fresh()->body);
    }

    public function test_student_cannot_update(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $enrollment = Enrollment::factory()->for($student)->create();
        $note = EnrollmentNote::factory()->forEnrollment($enrollment)->create(['body' => '元の本文']);

        $response = $this->actingAs($student)->patch(route('enrollment-notes.update', $note), [
            'body' => '書き換え',
        ]);

        $response->assertForbidden();
        $this->assertSame('元の本文', $note->fresh()->body);
    }
}
