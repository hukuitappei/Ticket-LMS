<?php

declare(strict_types=1);

namespace Tests\Feature\Http\MeetingPack;

use App\Models\MeetingPack;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_meeting_pack_detail_with_empty_purchase_history(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = MeetingPack::factory()->published()->create(['name' => '5 回パック']);

        $response = $this->actingAs($admin)->get(route('admin.meeting-packs.show', $plan));

        $response->assertOk();
        $response->assertViewIs('meeting-pack.management.show');
        $response->assertSee('5 回パック');
        // 購入記録は S-A-03 で追加されるまで常に空
        $response->assertSee('この SKU の購入はまだありません。');
    }

    public function test_coach_cannot_view_meeting_pack_detail(): void
    {
        $coach = User::factory()->coach()->create();
        $plan = MeetingPack::factory()->create();

        $this->actingAs($coach)
            ->get(route('admin.meeting-packs.show', $plan))
            ->assertForbidden();
    }
}
