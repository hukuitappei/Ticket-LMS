<?php

declare(strict_types=1);

namespace App\UseCases\MeetingPack;

use App\Models\MeetingPack;

/**
 * admin 用の面談パック詳細を取得するユースケース。作成者 / 更新者を Eager Loading で揃える。
 *
 * 詳細画面は購入履歴（payments）を表示するが、購入記録のテーブル・Model は S-A-03 で追加されるため、
 * それまでは空のコレクションを payments リレーションとして渡す（画面は「購入履歴 0 件」になる）。
 */
final class ShowAction
{
    public function __invoke(MeetingPack $meetingPack): MeetingPack
    {
        return $meetingPack
            ->load(['createdBy', 'updatedBy'])
            ->setRelation('payments', collect());
    }
}
