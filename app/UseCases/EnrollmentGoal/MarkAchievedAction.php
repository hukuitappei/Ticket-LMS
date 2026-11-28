<?php

declare(strict_types=1);

namespace App\UseCases\EnrollmentGoal;

use App\Models\EnrollmentGoal;
use Illuminate\Support\Facades\DB;

/**
 * 個人目標の達成マークユースケース。達成日時に今の日時を記録する。
 * すでに達成済みなら何もしない(二重送信で達成日時がずれないように)。
 */
final class MarkAchievedAction
{
    public function __invoke(EnrollmentGoal $goal): EnrollmentGoal
    {
        if ($goal->isAchieved()) {
            return $goal;
        }

        return DB::transaction(function () use ($goal) {
            $goal->update(['achieved_at' => now()]);

            return $goal->fresh();
        });
    }
}
