<?php

declare(strict_types=1);

namespace App\UseCases\Plan;

use App\Enums\PlanStatus;
use App\Enums\UserStatus;
use App\Models\Plan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * admin 用のプラン一覧をフィルタ付きで取得するユースケース。
 * 並び順は 状態（公開中 → 下書き → アーカイブ）→ sort_order 昇順 → 作成日時の降順。
 * 状態の優先は管理一覧だけの並びなので、招待画面などでも使う Model の ordered scope には入れず、ここで先頭に足す。
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
            // 状態の優先順（MySQL の FIELD() と同じ並びを、SQLite でも動く CASE 式で書く）
            ->orderByRaw('CASE status WHEN ? THEN 0 WHEN ? THEN 1 ELSE 2 END', [
                PlanStatus::Published->value,
                PlanStatus::Draft->value,
            ])
            ->ordered()
            ->paginate($perPage)
            ->withQueryString();
    }
}
