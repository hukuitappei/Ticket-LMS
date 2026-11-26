<?php

declare(strict_types=1);

namespace App\UseCases\Plan;

use App\Enums\PlanStatus;
use App\Exceptions\Plan\PlanNotDeletableException;
use App\Models\Plan;
use Illuminate\Support\Facades\DB;

/**
 * プランを物理削除するユースケース。下書き かつ 受講者・プラン履歴の紐づきがないプランのみ削除できる。
 *
 * users.plan_id / user_plan_logs.plan_id は restrictOnDelete のため、紐づきが残っていると DB エラーになる。
 * 退会済ユーザーは SoftDelete で `users` リレーションから外れるが plan_id は残るので、withTrashed で含めて判定する。
 */
final class DestroyAction
{
    /**
     * @throws PlanNotDeletableException 下書き以外、または受講者・プラン履歴が紐づいている
     */
    public function __invoke(Plan $plan): void
    {
        $hasReferences = $plan->users()->withTrashed()->exists()
            || $plan->userPlanLogs()->exists();

        if ($plan->status !== PlanStatus::Draft || $hasReferences) {
            throw new PlanNotDeletableException;
        }

        DB::transaction(fn () => $plan->delete());
    }
}
