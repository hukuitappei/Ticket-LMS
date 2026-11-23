<?php

declare(strict_types=1);

namespace Tests\Feature\Http\MeetingPack;

use App\Models\MeetingPack;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_meeting_pack_list(): void
    {
        $admin = User::factory()->admin()->create();
        MeetingPack::factory()->count(3)->create();

        $response = $this->actingAs($admin)->get(route('admin.meeting-packs.index'));

        $response->assertOk();
        $response->assertViewIs('meeting-pack.management.index');
        $response->assertViewHas('plans', fn ($plans) => $plans->total() === 3);
    }

    public function test_list_is_ordered_by_sort_order_then_newest(): void
    {
        $admin = User::factory()->admin()->create();
        $second = MeetingPack::factory()->create(['name' => '並び順1・古い', 'sort_order' => 1, 'created_at' => now()->subDay()]);
        $third = MeetingPack::factory()->create(['name' => '並び順2', 'sort_order' => 2]);
        $first = MeetingPack::factory()->create(['name' => '並び順1・新しい', 'sort_order' => 1, 'created_at' => now()]);

        $response = $this->actingAs($admin)->get(route('admin.meeting-packs.index'));

        $response->assertViewHas('plans', fn ($plans) => $plans->pluck('id')->all() === [$first->id, $second->id, $third->id]);
    }

    public function test_keyword_filters_by_partial_name_match(): void
    {
        $admin = User::factory()->admin()->create();
        MeetingPack::factory()->create(['name' => '5 回パック']);
        MeetingPack::factory()->create(['name' => '10 回パック']);

        $response = $this->actingAs($admin)->get(route('admin.meeting-packs.index', ['keyword' => '10']));

        $response->assertViewHas('plans', fn ($plans) => $plans->pluck('name')->all() === ['10 回パック']);
    }

    public function test_status_filters_meeting_packs(): void
    {
        $admin = User::factory()->admin()->create();
        $published = MeetingPack::factory()->published()->create();
        MeetingPack::factory()->draft()->create();
        MeetingPack::factory()->archived()->create();

        $response = $this->actingAs($admin)->get(route('admin.meeting-packs.index', ['status' => 'published']));

        $response->assertViewHas('plans', fn ($plans) => $plans->pluck('id')->all() === [$published->id]);
    }

    public function test_invalid_status_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.meeting-packs.index', ['status' => 'unknown']))
            ->assertSessionHasErrors('status');
    }

    public function test_coach_and_student_cannot_view_list(): void
    {
        $this->actingAs(User::factory()->coach()->create())
            ->get(route('admin.meeting-packs.index'))
            ->assertForbidden();

        $this->actingAs(User::factory()->student()->create())
            ->get(route('admin.meeting-packs.index'))
            ->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.meeting-packs.index'))->assertRedirect(route('login'));
    }
}
