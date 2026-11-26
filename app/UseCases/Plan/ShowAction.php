<?php

declare(strict_types=1);

namespace App\UseCases\Plan;

use App\Models\Plan;

/**
 * admin 用のプラン詳細を取得するユースケース。
 * 作成者 / 更新者と、紐づく受講者（招待中・受講中・卒業。退会済は SoftDelete のため含まれない）を氏名順で揃える。
 */
final class ShowAction
{
    public function __invoke(Plan $plan): Plan
    {
        return $plan->load([
            'createdBy',
            'updatedBy',
            'users' => fn ($q) => $q->orderBy('name'),
        ]);
    }
}
