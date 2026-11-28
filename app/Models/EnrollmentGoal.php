<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\EnrollmentGoalFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 個人目標。受講生本人が受講登録単位で立てる目標で、達成状態は achieved_at(null = 未達成)で管理する。
 *
 * 関連: Enrollment(belongsTo)
 * scope: displayOrder()(受講登録詳細の一覧・受講生ダッシュボードのタイムラインで共通の並び順)
 */
class EnrollmentGoal extends Model
{
    /** @use HasFactory<EnrollmentGoalFactory> */
    use HasFactory, HasUlids;

    protected $fillable = [
        'enrollment_id',
        'title',
        'target_date',
        'description',
        'achieved_at',
    ];

    protected $casts = [
        'target_date' => 'date',
        'achieved_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Enrollment, $this>
     */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function isAchieved(): bool
    {
        return $this->achieved_at !== null;
    }

    /**
     * 未達成を上・達成済みを下に並べる。
     * - 未達成: 目標期日の近い順(期日なしは最後)
     * - 達成済み: 達成日時の新しい順
     * - 同じ条件なら新しく作成した順
     */
    public function scopeDisplayOrder(Builder $query): Builder
    {
        return $query
            // 未達成(achieved_at IS NULL = 0)を先、達成済み(= 1)を後にする
            ->orderByRaw('achieved_at IS NOT NULL')
            // 未達成の中: 期日なしを最後に回してから、期日の近い順
            // (達成済みの行は CASE が NULL になり、ここでは差がつかない = 次の達成日時の順に任せる)
            ->orderByRaw('CASE WHEN achieved_at IS NULL THEN target_date IS NULL END')
            ->orderByRaw('CASE WHEN achieved_at IS NULL THEN target_date END')
            // 達成済みの中: 達成日時の新しい順
            ->orderByDesc('achieved_at')
            ->latest();
    }
}
