<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Certification;
use App\Models\Enrollment;
use App\Models\EnrollmentNote;
use App\Models\User;

/**
 * 受講生メモの認可ルール。
 *
 * - viewAny / create: 親の受講登録単位で判定する。admin は全受講登録、coach は担当資格の受講登録のみ。
 * - update / delete: admin は全メモ、coach は「自分が作成したメモ」かつ「今も担当している資格の受講登録」のみ。
 * - student はすべて拒否(受講生本人の受講登録でもメモ欄自体を見せない)。
 * - 親の受講登録が論理削除されている場合は、どのロールでも操作させない。
 */
class EnrollmentNotePolicy
{
    public function viewAny(User $auth, Enrollment $enrollment): bool
    {
        // 受講登録詳細は論理削除済みでも開ける(enrollments.show が withTrashed)ため、ここでメモ欄ごと隠す
        if ($enrollment->trashed()) {
            return false;
        }

        return match ($auth->role) {
            UserRole::Admin => true,
            UserRole::Coach => $this->assignedCoach($auth, $enrollment->certification),
            default => false,
        };
    }

    public function create(User $auth, Enrollment $enrollment): bool
    {
        return $this->viewAny($auth, $enrollment);
    }

    public function update(User $auth, EnrollmentNote $note): bool
    {
        $enrollment = $note->enrollment;

        if ($enrollment === null) {
            return false;
        }

        return match ($auth->role) {
            UserRole::Admin => true,
            UserRole::Coach => $note->author_user_id === $auth->id
                && $this->assignedCoach($auth, $enrollment->certification),
            default => false,
        };
    }

    public function delete(User $auth, EnrollmentNote $note): bool
    {
        return $this->update($auth, $note);
    }

    private function assignedCoach(User $coach, ?Certification $certification): bool
    {
        return $certification !== null
            && $certification->coaches()->where('users.id', $coach->id)->exists();
    }
}
