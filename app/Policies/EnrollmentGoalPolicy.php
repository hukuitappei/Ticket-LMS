<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Enrollment;
use App\Models\EnrollmentGoal;
use App\Models\User;

/**
 * 個人目標の認可ルール。
 *
 * - 追加 / 編集 / 削除 / 達成マーク / 達成解除 は、すべて「受講中の受講生本人」だけが行える。
 *   コーチ / 管理者 / 他の受講生は拒否する(閲覧は受講登録詳細を開ける人なら誰でもでき、ここでは判定しない)。
 * - 卒業した受講生も受講登録詳細は開けるため、ここで弾いて追加フォームや操作ボタンを出さない
 *   (ルート側の active-learning Middleware と同じ条件)。
 * - 親の受講登録が論理削除されている場合は操作させない。
 */
class EnrollmentGoalPolicy
{
    public function create(User $auth, Enrollment $enrollment): bool
    {
        return $auth->role === UserRole::Student
            && $auth->status === UserStatus::InProgress
            && $enrollment->user_id === $auth->id
            && ! $enrollment->trashed();
    }

    public function update(User $auth, EnrollmentGoal $goal): bool
    {
        // 親の受講登録が論理削除されていると、belongsTo は null を返す(SoftDeletes のグローバルスコープ)
        $enrollment = $goal->enrollment;

        return $enrollment !== null && $this->create($auth, $enrollment);
    }

    public function delete(User $auth, EnrollmentGoal $goal): bool
    {
        return $this->update($auth, $goal);
    }

    public function markAchieved(User $auth, EnrollmentGoal $goal): bool
    {
        return $this->update($auth, $goal);
    }

    public function unmarkAchieved(User $auth, EnrollmentGoal $goal): bool
    {
        return $this->update($auth, $goal);
    }
}
