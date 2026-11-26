<?php

declare(strict_types=1);

namespace Tests\Feature\Http\EnrollmentNote;

use App\Models\Enrollment;
use App\Models\EnrollmentNote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ContentTestHelpers;
use Tests\TestCase;

class EditTest extends TestCase
{
    use ContentTestHelpers, RefreshDatabase;

    public function test_author_coach_can_open_edit_page(): void
    {
        $coach = User::factory()->coach()->inProgress()->create();
        $enrollment = Enrollment::factory()->create();
        $this->assignCoach($coach, $enrollment->certification);
        $note = EnrollmentNote::factory()->forEnrollment($enrollment)->byAuthor($coach)->create();

        $response = $this->actingAs($coach)->get(route('enrollment-notes.edit', $note));

        $response->assertOk();
        $response->assertViewIs('enrollment-note.edit');
        $response->assertViewHas('note', fn ($viewNote) => $viewNote->is($note));
    }

    public function test_admin_can_open_edit_page_of_coach_note(): void
    {
        $admin = User::factory()->admin()->create();
        $note = EnrollmentNote::factory()->create();

        $response = $this->actingAs($admin)->get(route('enrollment-notes.edit', $note));

        $response->assertOk();
    }

    public function test_other_assigned_coach_is_forbidden(): void
    {
        $author = User::factory()->coach()->inProgress()->create();
        $otherCoach = User::factory()->coach()->inProgress()->create();
        $enrollment = Enrollment::factory()->create();
        $this->assignCoach($author, $enrollment->certification);
        $this->assignCoach($otherCoach, $enrollment->certification);
        $note = EnrollmentNote::factory()->forEnrollment($enrollment)->byAuthor($author)->create();

        $response = $this->actingAs($otherCoach)->get(route('enrollment-notes.edit', $note));

        $response->assertForbidden();
    }

    public function test_note_of_soft_deleted_enrollment_is_forbidden(): void
    {
        $admin = User::factory()->admin()->create();
        $enrollment = Enrollment::factory()->create();
        $note = EnrollmentNote::factory()->forEnrollment($enrollment)->create();
        $enrollment->delete();

        $response = $this->actingAs($admin)->get(route('enrollment-notes.edit', $note));

        $response->assertForbidden();
    }
}
