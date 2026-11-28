<?php

declare(strict_types=1);

namespace App\UseCases\EnrollmentGoal;

use App\Models\EnrollmentGoal;
use Illuminate\Support\Facades\DB;

/**
 * 個人目標の更新ユースケース。タイトル / 詳細 / 目標期日だけを更新し、達成状態(achieved_at)は変えない。
 */
final class UpdateAction
{
    /**
     * @param array{title: string, description: ?string, target_date: ?string} $validated EnrollmentGoal/UpdateRequest::rules() で検証済
     */
    public function __invoke(EnrollmentGoal $goal, array $validated): EnrollmentGoal
    {
        return DB::transaction(function () use ($goal, $validated) {
            $goal->update([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'target_date' => $validated['target_date'] ?? null,
            ]);

            return $goal->fresh();
        });
    }
}
