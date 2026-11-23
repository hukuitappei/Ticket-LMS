<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\MeetingPack;
use App\Models\User;

/**
 * 面談パック（追加面談購入用 SKU）マスタの認可ルール。全操作 admin のみ。
 *
 * 状態ごとに実行できる操作（公開中は削除不可 / 下書きのみ公開可 など）は Policy では判定しない。
 * ボタンの出し分けは Blade の @if、不正な遷移・削除の拒否は各 Action の例外（409）で行う。
 */
class MeetingPackPolicy
{
    public function viewAny(User $auth): bool
    {
        return $auth->role === UserRole::Admin;
    }

    public function view(User $auth, MeetingPack $meetingPack): bool
    {
        return $auth->role === UserRole::Admin;
    }

    public function create(User $auth): bool
    {
        return $auth->role === UserRole::Admin;
    }

    public function update(User $auth, MeetingPack $meetingPack): bool
    {
        return $auth->role === UserRole::Admin;
    }

    public function delete(User $auth, MeetingPack $meetingPack): bool
    {
        return $auth->role === UserRole::Admin;
    }

    public function publish(User $auth, MeetingPack $meetingPack): bool
    {
        return $auth->role === UserRole::Admin;
    }

    public function archive(User $auth, MeetingPack $meetingPack): bool
    {
        return $auth->role === UserRole::Admin;
    }

    public function unarchive(User $auth, MeetingPack $meetingPack): bool
    {
        return $auth->role === UserRole::Admin;
    }
}
