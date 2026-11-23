<?php

declare(strict_types=1);

namespace App\UseCases\MeetingPack;

use App\Enums\MeetingPackStatus;
use App\Exceptions\MeetingPack\MeetingPackNotDeletableException;
use App\Models\MeetingPack;
use Illuminate\Support\Facades\DB;

/**
 * 面談パックを削除するユースケース。公開中は削除不可（下書き / アーカイブ済のみ削除可）。
 */
final class DestroyAction
{
    /**
     * @throws MeetingPackNotDeletableException 公開中の面談パックは削除不可
     */
    public function __invoke(MeetingPack $meetingPack): void
    {
        if ($meetingPack->status === MeetingPackStatus::Published) {
            throw new MeetingPackNotDeletableException;
        }

        DB::transaction(fn () => $meetingPack->delete());
    }
}
