<?php

namespace App\Http\Controllers;

use App\Support\QuestionTopics;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuestionTopicsController extends Controller
{
    public function __invoke(Request $request): View
    {
        $topics = QuestionTopics::all();
        $requested = $request->query('topic');
        $selectedTopic = is_string($requested) && isset($topics[$requested]) ? $requested : array_key_first($topics);

        return view('frontend.questions', [
            'topics' => $topics,
            'selectedTopic' => $selectedTopic,
            'sources' => QuestionTopics::sources(),
            'reviewedOn' => QuestionTopics::REVIEWED_ON,
        ]);
    }
}
