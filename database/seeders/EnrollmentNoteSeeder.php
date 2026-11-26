<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Enrollment;
use App\Models\EnrollmentNote;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * 受講生メモ(S-B-07)のデモデータシーダー。
 *
 * **設計思想**:
 *
 * - **担当コーチごとに書く**: 各受講登録には、その資格を担当しているコーチだけがメモを書く
 *   (coach@ = 基本情報 / 応用情報 / TOEIC、coach2@ = TOEIC / 日商簿記 / PMP)。
 * - **自分のメモと他コーチのメモの混在**: 両コーチが担当する TOEIC の受講登録に両方のメモを入れ、
 *   編集・削除ボタンの出し分け(自分のメモだけ)を確認できるようにする。
 * - **管理者のメモ**: いくつかの受講登録に admin@ のメモも入れ、管理者の越境編集・削除を確認できるようにする。
 * - **固定受講生**: student@ の受講登録にもメモを入れ、受講生本人にはメモ欄が見えないことを確認できるようにする。
 *
 * 依存: UserSeeder → CertificationSeeder(担当割当)→ EnrollmentSeeder の後に走る前提。
 */
final class EnrollmentNoteSeeder extends Seeder
{
    private const BODIES = [
        '最近 chat の返信が 2〜3 日空くことが増えている。次回面談で学習時間の確保状況を確認する。',
        'Q&A で同じ論点(計算問題の単位換算)で 2 回つまずいている。演習の解説を一緒に読み直す予定。',
        '模試の点数は伸びているが、午後問題の時間配分に不安あり。過去問を時間を計って解くよう提案した。',
        '目標受験日を 1 か月後ろ倒ししたいと相談あり。仕事の繁忙期と重なるため、計画を一緒に見直した。',
        '基礎学習ターム終盤。理解度は十分なので、模試中心の学習に切り替えを提案する。',
        '面談で「独学だと続かない」との声。週 1 回の進捗報告を chat でしてもらうことにした。',
    ];

    public function run(): void
    {
        $admin = User::query()->where('email', 'admin@certify-lms.test')->first();
        $coaches = User::query()
            ->whereIn('email', ['coach@certify-lms.test', 'coach2@certify-lms.test'])
            ->get();

        if ($coaches->isEmpty()) {
            $this->command?->warn('EnrollmentNoteSeeder: 固定コーチアカウントが存在しません。スキップします。');

            return;
        }

        $enrollments = Enrollment::query()
            ->with(['certification.coaches', 'user'])
            ->orderBy('created_at')
            ->get();

        $bodyIndex = 0;

        foreach ($enrollments as $index => $enrollment) {
            $assignedCoaches = $this->assignedFixedCoaches($enrollment, $coaches);

            // 担当コーチ(固定アカウント)全員が 1 件ずつ書く。TOEIC は両コーチ分が並ぶ。
            foreach ($assignedCoaches as $offset => $coach) {
                $this->createNote($enrollment, $coach, $bodyIndex++, daysAgo: 10 - $offset * 3);
            }

            // 3 件に 1 件は管理者のメモも足す
            if ($admin !== null && $assignedCoaches->isNotEmpty() && $index % 3 === 0) {
                $this->createNote($enrollment, $admin, $bodyIndex++, daysAgo: 1);
            }
        }
    }

    /**
     * @param Collection<int, User> $coaches
     *
     * @return Collection<int, User>
     */
    private function assignedFixedCoaches(Enrollment $enrollment, Collection $coaches): Collection
    {
        $assignedIds = $enrollment->certification?->coaches->pluck('id') ?? collect();

        return $coaches->filter(fn (User $coach) => $assignedIds->contains($coach->id))->values();
    }

    private function createNote(Enrollment $enrollment, User $author, int $bodyIndex, int $daysAgo): void
    {
        $createdAt = now()->subDays($daysAgo);

        EnrollmentNote::factory()
            ->forEnrollment($enrollment)
            ->byAuthor($author)
            ->create([
                'body' => self::BODIES[$bodyIndex % count(self::BODIES)],
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
    }
}
