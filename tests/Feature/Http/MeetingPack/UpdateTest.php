<?php

declare(strict_types=1);

namespace Tests\Feature\Http\MeetingPack;

use App\Models\MeetingPack;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param array<string, mixed> $override
     *
     * @return array<string, mixed>
     */
    private function payload(array $override = []): array
    {
        return array_merge([
            'name' => '改定後パック',
            'description' => '価格改定',
            'meeting_count' => 5,
            'price' => 15000,
            'stripe_price_id' => 'price_123',
            'sort_order' => 3,
        ], $override);
    }

    public function test_admin_can_update_published_meeting_pack_without_changing_status(): void
    {
        $creator = User::factory()->admin()->create();
        $editor = User::factory()->admin()->create();
        $plan = MeetingPack::factory()->published()->create(['created_by_user_id' => $creator->id]);

        $response = $this->actingAs($editor)->patch(route('admin.meeting-packs.update', $plan), $this->payload());

        $response->assertRedirect(route('admin.meeting-packs.show', $plan));
        $response->assertSessionHas('success');
        $plan->refresh();
        $this->assertSame('改定後パック', $plan->name);
        $this->assertSame(15000, $plan->price);
        $this->assertSame('price_123', $plan->stripe_price_id);
        $this->assertSame('published', $plan->status->value);
        $this->assertSame($creator->id, $plan->created_by_user_id);
        $this->assertSame($editor->id, $plan->updated_by_user_id);
    }

    public function test_status_in_request_is_ignored(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = MeetingPack::factory()->draft()->create();

        $this->actingAs($admin)->patch(route('admin.meeting-packs.update', $plan), $this->payload(['status' => 'published']));

        $this->assertSame('draft', $plan->fresh()->status->value);
    }

    public function test_invalid_input_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = MeetingPack::factory()->create(['price' => 3000]);

        $this->actingAs($admin)
            ->patch(route('admin.meeting-packs.update', $plan), $this->payload(['price' => -1]))
            ->assertSessionHasErrors('price');

        $this->assertSame(3000, $plan->fresh()->price);
    }

    public function test_coach_cannot_update_meeting_pack(): void
    {
        $coach = User::factory()->coach()->create();
        $plan = MeetingPack::factory()->create();

        $this->actingAs($coach)
            ->patch(route('admin.meeting-packs.update', $plan), $this->payload())
            ->assertForbidden();
    }
}
