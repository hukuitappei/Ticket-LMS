<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Enrollment;
use App\Models\EnrollmentGoal;
use Illuminate\Database\Seeder;

/**
 * 個人目標(S-B-05)のデモデータシーダー。
 *
 * **設計思想**:
 *
 * - **固定受講生**: student@ の受講登録すべてに、達成済み・未達成(期日あり / 期日超過 / 期日なし)を混在させ、
 *   視覚区別・並び順・達成マーク / 解除・編集 / 削除を 1 画面で確認できるようにする。
 * - **デモ受講生**: その他の受講生の受講登録 2 件に 1 件へ 1〜3 件ずつ散らし、
 *   コーチ(担当 / 担当外)・管理者・他の受講生からの閲覧の出し分けを確認できるようにする。
 *
 * 依存: UserSeeder → CertificationSeeder → EnrollmentSeeder の後に走る前提。
 */
final class EnrollmentGoalSeeder extends Seeder
{
    private const FIXED_STUDENT_EMAIL = 'student@certify-lms.test';

    /**
     * 固定受講生の受講登録ごとに入れる目標。
     * target_date / achieved_at は今日からの相対日数(null = 未設定 / 未達成)。
     */
    private const FIXED_GOALS = [
        ['title' => '過去問 5 年分を解き終える', 'description' => '1 日 1 回分のペースで進める。', 'target_in_days' => 14, 'achieved_days_ago' => null],
        ['title' => '苦手分野の演習を 3 周する', 'description' => null, 'target_in_days' => 3, 'achieved_days_ago' => null],
        ['title' => '模試で合格点を超える', 'description' => '本番 1 か月前までに一度は超えておきたい。', 'target_in_days' => -2, 'achieved_days_ago' => null],
        ['title' => '毎朝 30 分の学習を習慣にする', 'description' => null, 'target_in_days' => null, 'achieved_days_ago' => null],
        ['title' => '教材の第 1 部を読み終える', 'description' => '用語を自分のノートにまとめながら読む。', 'target_in_days' => -10, 'achieved_days_ago' => 12],
        ['title' => '学習計画を立てる', 'description' => null, 'target_in_days' => null, 'achieved_days_ago' => 20],
    ];

    public function run(): void
    {
        $enrollments = Enrollment::query()
            ->with('user')
            ->orderBy('created_at')
            ->get();

        foreach ($enrollments as $index => $enrollment) {
            if ($enrollment->user?->email === self::FIXED_STUDENT_EMAIL) {
                $this->seedFixedGoals($enrollment);

                continue;
            }

            // デモ受講生は 2 件に 1 件だけ目標を入れる(目標 0 件の受講登録も残す)
            if ($index % 2 === 0) {
                $this->seedDemoGoals($enrollment, count: $index % 3 + 1);
            }
        }
    }

    private function seedFixedGoals(Enrollment $enrollment): void
    {
        foreach (self::FIXED_GOALS as $goal) {
            EnrollmentGoal::factory()
                ->forEnrollment($enrollment)
                ->create([
                    'title' => $goal['title'],
                    'description' => $goal['description'],
                    'target_date' => $goal['target_in_days'] === null ? null : today()->addDays($goal['target_in_days']),
                    'achieved_at' => $goal['achieved_days_ago'] === null ? null : now()->subDays($goal['achieved_days_ago']),
                ]);
        }
    }

    private function seedDemoGoals(Enrollment $enrollment, int $count): void
    {
        // 1 件目は達成済み、残りは未達成にして、他ロールから見たときも視覚区別を確認できるようにする
        EnrollmentGoal::factory()
            ->forEnrollment($enrollment)
            ->achieved()
            ->create();

        if ($count > 1) {
            EnrollmentGoal::factory()
                ->count($count - 1)
                ->forEnrollment($enrollment)
                ->create();
        }
    }
}
