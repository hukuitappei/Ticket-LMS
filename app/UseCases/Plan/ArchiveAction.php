<?php

declare(strict_types=1);

namespace App\UseCases\Plan;

use App\Enums\PlanStatus;
use App\Exceptions\Plan\PlanInvalidTransitionException;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * プランをアーカイブ（published → archived）するユースケース。
 * 下書き / アーカイブ済からの遷移は不正で PlanInvalidTransitionException（409）。
 * アーカイブ後は招待 / プラン延長の選択肢（published スコープ）から外れるが、紐づく受講者の plan_id はそのまま残す。
 */
final class ArchiveAction
{
    /**
     * @throws PlanInvalidTransitionException 公開中以外からの呼出
     */
    public function __invoke(Plan $plan, User $admin): Plan
    {
        if ($plan->status !== PlanStatus::Published) {
            throw PlanInvalidTransitionException::forArchive();
        }

        return DB::transaction(function () use ($plan, $admin) {
            $plan->update([
                'status' => PlanStatus::Archived->value,
                'updated_by_user_id' => $admin->id,
            ]);

            return $plan->fresh();
        });
    }
}
