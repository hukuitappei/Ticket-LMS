<?php

declare(strict_types=1);

namespace App\UseCases\EnrollmentGoal;

use App\Models\Enrollment;
use App\Models\EnrollmentGoal;
use Illuminate\Support\Facades\DB;

/**
 * 個人目標の追加ユースケース。追加した直後の目標は未達成(achieved_at = null)。
 */
final class StoreAction
{
    /**
     * @param array{title: string, description: ?string, target_date: ?string} $validated EnrollmentGoal/StoreRequest::rules() で検証済
     */
    public function __invoke(Enrollment $enrollment, array $validated): EnrollmentGoal
    {
        return DB::transaction(fn () => EnrollmentGoal::create([
            'enrollment_id' => $enrollment->id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'target_date' => $validated['target_date'] ?? null,
        ]));
    }
}
