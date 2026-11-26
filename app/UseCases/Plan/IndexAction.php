<?php

declare(strict_types=1);

namespace App\UseCases\Plan;

use App\Enums\PlanStatus;
use App\Enums\UserStatus;
use App\Models\Plan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * admin 用のプラン一覧をフィルタ付きで取得するユースケース。
 * 並び順は sort_order 昇順 → 作成日時の降順（Model の ordered scope）。
 * 各行の「契約中の受講者数」（users_count）は、紐づく受講者のうち受講中（in_progress）の人数。
 */
final class IndexAction
{
    public function __invoke(?string $keyword, ?PlanStatus $status, int $perPage = 20): LengthAwarePaginator
    {
        $query = Plan::query()
            ->withCount(['users' => fn ($q) => $q->where('status', UserStatus::InProgress->value)]);

        if ($keyword !== null && $keyword !== '') {
            $query->where('name', 'like', '%'.$keyword.'%');
        }

        if ($status !== null) {
            $query->where('status', $status->value);
        }

        return $query
            ->ordered()
            ->paginate($perPage)
            ->withQueryString();
    }
}
