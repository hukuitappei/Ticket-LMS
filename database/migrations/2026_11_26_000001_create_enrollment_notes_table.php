<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 受講生メモ(コーチ / 管理者が受講登録単位で残す業務記録)テーブル。
 *
 * - 1 受講登録 : N メモ。受講登録の物理削除時はメモも消える(cascade)。
 *   受講登録はソフトデリートのため、論理削除中はメモは残るが受講登録詳細ごと見えなくなる。
 * - 作成者(author_user_id)はメモの編集・削除権限の判定に使う。管理者が編集しても書き換えない。
 *   作成者ユーザーが物理削除された場合に備えて nullable + nullOnDelete(画面は「不明」と表示)。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enrollment_notes', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('enrollment_id')->constrained('enrollments')->cascadeOnDelete();
            $table->foreignUlid('author_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('body');
            $table->timestamps();

            $table->index(['enrollment_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollment_notes');
    }
};
