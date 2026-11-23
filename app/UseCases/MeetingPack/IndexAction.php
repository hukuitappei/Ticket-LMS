<?php

declare(strict_types=1);

namespace App\UseCases\MeetingPack;

use App\Enums\MeetingPackStatus;
use App\Models\MeetingPack;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * admin 用の面談パック一覧をフィルタ付きで取得するユースケース。
 * 並び順は sort_order 昇順 → 作成日時の降順（Model の ordered scope）。
 * 購入数（payments_count）は購入記録が S-A-03 で追加されるまで集計しない（Blade 側で 0 件表示）。
 */
final class IndexAction
{
    public function __invoke(?string $keyword, ?MeetingPackStatus $status, int $perPage = 20): LengthAwarePaginator
    {
        $query = MeetingPack::query();

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
