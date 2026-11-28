<?php

declare(strict_types=1);

namespace App\UseCases\EnrollmentGoal;

use App\Models\EnrollmentGoal;
use Illuminate\Support\Facades\DB;

/**
 * 個人目標の削除ユースケース。チケットどおり物理削除し、履歴は残さない。
 */
final class DestroyAction
{
    public function __invoke(EnrollmentGoal $goal): void
    {
        DB::transaction(fn () => $goal->delete());
    }
}
