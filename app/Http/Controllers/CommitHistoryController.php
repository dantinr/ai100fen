<?php

namespace App\Http\Controllers;

use App\Support\CommitHistory;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class CommitHistoryController extends Controller
{
    public function __invoke(Request $request, CommitHistory $history): View
    {
        $calendar = $history->calendar($history->read());
        $date = $request->query('date');
        abort_if($date !== null && (! is_string($date) || ! preg_match('/\A\d{4}-\d{2}-\d{2}\z/', $date)
            || $date < $calendar['start'] || $date > $calendar['end']
            || ! in_array($date, array_column(array_merge(...array_column($calendar['weeks'], 'days')), 'date'), true)), 404);
        $commits = $calendar['commits']->filter(fn (array $commit) => $date === null || $commit['date'] === $date)->values();
        $page = min(LengthAwarePaginator::resolveCurrentPage(), max(1, (int) ceil($commits->count() / 20)));
        $paginator = new LengthAwarePaginator($commits->slice(($page - 1) * 20, 20), $commits->count(), 20, $page, [
            'path' => route('commits'), 'query' => $date ? ['date' => $date] : [], 'fragment' => 'commit-list',
        ]);

        return view('frontend.commits', ['calendar' => $calendar, 'commits' => $paginator, 'selectedDate' => $date]);
    }
}
