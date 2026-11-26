<?php

declare(strict_types=1);

namespace App\Exceptions\Plan;

use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * 削除条件を満たさないプランを削除しようとした際の例外（HTTP 409）。
 * `Plan\DestroyAction` が「下書き かつ 受講者・プラン履歴の紐づきなし」のドメインルールから throw する。
 * ブラウザからの送信では Handler により直前の画面へ戻し、Flash error でこのメッセージを表示する。
 */
final class PlanNotDeletableException extends ConflictHttpException
{
    public function __construct(?\Throwable $previous = null)
    {
        parent::__construct('下書き状態で、受講者が紐づいていないプランのみ削除できます。', $previous);
    }
}
