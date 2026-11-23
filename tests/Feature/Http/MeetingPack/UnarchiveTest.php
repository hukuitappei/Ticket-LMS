<?php

declare(strict_types=1);

namespace Tests\Feature\Http\MeetingPack;

use App\Models\MeetingPack;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnarchiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_returns_archived_meeting_pack_to_draft(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = MeetingPack::factory()->archived()->create();

        $response = $this->actingAs($admin)->post(route('admin.meeting-packs.unarchive', $plan));

        $response->assertRedirect(route('admin.meeting-packs.show', $plan));
        $this->assertSame('draft', $plan->fresh()->status->value);
    }

    public function test_cannot_unarchive_published(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = MeetingPack::factory()->published()->create();

        $this->actingAs($admin)
            ->postJson(route('admin.meeting-packs.unarchive', $plan))
            ->assertStatus(409);

        $this->assertSame('published', $plan->fresh()->status->value);
    }

    public function test_coach_cannot_unarchive(): void
    {
        $coach = User::factory()->coach()->create();
        $plan = MeetingPack::factory()->archived()->create();

        $this->actingAs($coach)
            ->post(route('admin.meeting-packs.unarchive', $plan))
            ->assertForbidden();
    }
}
