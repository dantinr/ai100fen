<?php

namespace App\Services;

use App\Models\Question;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class QuestionSubmissionService
{
    public function collect(User $user, array $validated): Question
    {
        $card = collect($validated)->only(['title', 'category', 'goal', 'scope', 'outcome', 'completion_criteria'])->all();
        $card['scope'] = $card['scope'] ?? null;
        ksort($card);
        $hash = hash('sha256', json_encode($card, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));

        return DB::transaction(function () use ($user, $validated, $card, $hash) {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $existing = Question::where('user_id', $user->id)->where('submission_key', $validated['submission_key'])->first();
            if ($existing) {
                abort_unless(hash_equals($existing->content_hash, $hash), 409, '该提交编号已有保存记录，请先查看“我的问题”；原草稿仍保留。');

                return $existing;
            }

            return Question::create($card + ['user_id' => $user->id, 'submission_key' => $validated['submission_key'], 'content_hash' => $hash]);
        }, 3);
    }
}
