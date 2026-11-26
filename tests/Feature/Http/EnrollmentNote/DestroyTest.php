<?php

declare(strict_types=1);

namespace Tests\Feature\Http\EnrollmentNote;

use App\Models\Enrollment;
use App\Models\EnrollmentNote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ContentTestHelpers;
use Tests\TestCase;

class DestroyTest extends TestCase
{
    use ContentTestHelpers, RefreshDatabase;

    public function test_author_coach_can_delete_own_note(): void
    {
        $coach = User::factory()->coach()->inProgress()->create();
        $enrollment = Enrollment::factory()->create();
        $this->assignCoach($coach, $enrollment->certification);
        $note = EnrollmentNote::factory()->forEnrollment($enrollment)->byAuthor($coach)->create();

        $response = $this->actingAs($coach)->delete(route('enrollment-notes.destroy', $note));

        $response->assertRedirect(route('enrollments.show', $enrollment));
        $response->assertSessionHas('success');
        $this->assertModelMissing($note);
    }

    public function test_admin_can_delete_coach_note(): void
    {
        $admin = User::factory()->admin()->create();
        $note = EnrollmentNote::factory()->create();

        $response = $this->actingAs($admin)->delete(route('enrollment-notes.destroy', $note));

        $response->assertRedirect(route('enrollments.show', $note->enrollment_id));
        $this->assertModelMissing($note);
    }

    public function test_other_coach_cannot_delete(): void
    {
        $author = User::factory()->coach()->inProgress()->create();
        $otherCoach = User::factory()->coach()->inProgress()->create();
        $enrollment = Enrollment::factory()->create();
        $this->assignCoach($author, $enrollment->certification);
        $this->assignCoach($otherCoach, $enrollment->certification);
        $note = EnrollmentNote::factory()->forEnrollment($enrollment)->byAuthor($author)->create();

        $response = $this->actingAs($otherCoach)->delete(route('enrollment-notes.destroy', $note));

        $response->assertForbidden();
        $this->assertModelExists($note);
    }

    public function test_student_cannot_delete(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $enrollment = Enrollment::factory()->for($student)->create();
        $note = EnrollmentNote::factory()->forEnrollment($enrollment)->create();

        $response = $this->actingAs($student)->delete(route('enrollment-notes.destroy', $note));

        $response->assertForbidden();
        $this->assertModelExists($note);
    }
}
