<?php

declare(strict_types=1);

namespace App\UseCases\Plan;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * プランを更新するユースケース。公開中でも更新できる。
 * 受講期間・初期付与面談回数の変更は以降に招待する受講者にだけ反映され、紐づき済み受講者の期限・面談回数は変えない。
 * `status` は本 Action では更新せず、状態遷移用 Action に責務分離する。
 */
final class UpdateAction
{
    /**
     * @param array{name: string, description?: ?string, duration_days: int, default_meeting_quota: int, sort_order?: ?int} $validated
     */
    public function __invoke(Plan $plan, User $admin, array $validated): Plan
    {
        return DB::transaction(function () use ($plan, $admin, $validated) {
            $plan->update([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'duration_days' => $validated['duration_days'],
                'default_meeting_quota' => $validated['default_meeting_quota'],
                'sort_order' => $validated['sort_order'] ?? 0,
                'updated_by_user_id' => $admin->id,
            ]);

            return $plan->fresh();
        });
    }
}
