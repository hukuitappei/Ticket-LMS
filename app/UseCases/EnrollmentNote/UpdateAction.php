<?php

declare(strict_types=1);

namespace App\UseCases\EnrollmentNote;

use App\Models\EnrollmentNote;
use Illuminate\Support\Facades\DB;

/**
 * 受講生メモの更新ユースケース。本文だけを更新し、作成者(author_user_id)は変えない。
 * 管理者がコーチのメモを直した場合も、作成者は元のコーチのまま。
 */
final class UpdateAction
{
    /**
     * @param array{body: string} $validated EnrollmentNote/UpdateRequest::rules() で検証済
     */
    public function __invoke(EnrollmentNote $note, array $validated): EnrollmentNote
    {
        return DB::transaction(function () use ($note, $validated) {
            $note->update(['body' => $validated['body']]);

            return $note->fresh();
        });
    }
}
