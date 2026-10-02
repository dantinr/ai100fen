<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreQuestionRequest;
use App\Models\CourseSeries;
use App\Models\Question;
use App\Services\FreeCourseRecommendationService;
use App\Services\QuestionSubmissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class QuestionController extends Controller
{
    public function store(StoreQuestionRequest $request, QuestionSubmissionService $submissions): JsonResponse
    {
        abort_unless(Schema::hasTable('questions'), 503, '私人收集暂未准备好，草稿仍保留，请稍后重试。');
        $question = $submissions->collect($request->user(), $request->validated());

        return response()->json(['id' => $question->id, 'url' => route('questions.show', $question), 'message' => '问题已收集。仅你可见。'], $question->wasRecentlyCreated ? 201 : 200)
            ->header('Cache-Control', 'private, no-store');
    }

    public function index(Request $request): Response
    {
        $questions = Schema::hasTable('questions') ? Question::where('user_id', $request->user()->id)->latest('id')->paginate(12) : null;

        return response()->view('frontend.questions.mine', compact('questions'))->header('Cache-Control', 'private, no-store');
    }

    public function show(Request $request, int $question): Response
    {
        abort_unless(Schema::hasTable('questions'), 404);
        $question = Question::where('user_id', $request->user()->id)->findOrFail($question);

        return response()->view('frontend.questions.show', compact('question'))->header('Cache-Control', 'private, no-store');
    }

    public function recommend(Request $request, FreeCourseRecommendationService $recommendations): JsonResponse
    {
        $data = $request->validate(['q' => ['required', 'string', 'max:300'], 'category' => ['nullable', 'string', Rule::in(['solve', 'create', 'explore'])]]);
        $courses = CourseSeries::freeLab()->when($data['category'] ?? null, fn ($query, $category) => $query->where('category', $category))
            ->with(['lessons' => fn ($query) => $query->where('status', 'published')])->displayOrder()->get();
        $links = $recommendations->recommend($data['q'], $courses)->map(fn ($match) => [
            'title' => $match['course']->title, 'reason' => $match['reason'],
            'url' => route('free.lesson', [$match['course'], $match['course']->lessons->first()->slug]),
        ]);

        return response()->json(['recommendations' => $links])->header('Cache-Control', 'private, no-store');
    }
}
