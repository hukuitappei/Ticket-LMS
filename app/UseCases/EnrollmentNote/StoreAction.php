<?php

declare(strict_types=1);

namespace App\UseCases\EnrollmentNote;

use App\Models\Enrollment;
use App\Models\EnrollmentNote;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * 受講生メモの追加ユースケース。ログイン中のユーザー(コーチ / 管理者)を作成者として記録する。
 */
final class StoreAction
{
    /**
     * @param array{body: string} $validated EnrollmentNote/StoreRequest::rules() で検証済
     */
    public function __invoke(Enrollment $enrollment, User $author, array $validated): EnrollmentNote
    {
        return DB::transaction(fn () => EnrollmentNote::create([
            'enrollment_id' => $enrollment->id,
            'author_user_id' => $author->id,
            'body' => $validated['body'],
        ]));
    }
}
