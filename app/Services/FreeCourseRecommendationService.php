<?php

namespace App\Services;

use Illuminate\Support\Collection;

class FreeCourseRecommendationService
{
    public function recommend(string $question, Collection $courses): Collection
    {
        $question = mb_strtolower(trim($question));
        if ($question === '') {
            return collect();
        }

        return $courses->filter(fn ($course) => $course->is_free && $course->status === 'published')
            ->map(function ($course) use ($question) {
                $matches = collect($course->recommendation_keywords)->filter(fn ($word) => mb_stripos($question, $word) !== false);

                return ['course' => $course, 'score' => $matches->count(), 'reason' => $course->final_outcome];
            })->filter(fn ($match) => $match['score'] > 0)->sortByDesc('score')->values()->take(3);
    }
}
