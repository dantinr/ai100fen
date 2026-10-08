<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Models\LessonProgress;
use App\Services\CourseCatalog;
use App\Services\CourseAccessService;
use App\Services\ProgressService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(Request $request, CourseCatalog $catalog): View
    {
        $records = LessonProgress::where('user_id', $request->user()->id)
            ->whereHas('lesson.series', fn ($query) => $query->where('status', 'published'))
            ->whereHas('lesson', fn ($query) => $query->where('status', 'published'))
            ->with('lesson.series')->orderByDesc('updated_at')->get()
            ->filter(fn ($record) => app(CourseAccessService::class)->canAccess($record->lesson->series, $record->lesson));
        return view('frontend.me', [
            'series' => $catalog->all(), 'user' => $request->user(),
            'freeProgress' => $records,
            'courseProgress' => $records->groupBy('lesson.course_series_id')->map(fn ($items) => [
                'course' => $items->first()->lesson->series,
                'percent' => app(ProgressService::class)->seriesPercent($request->user(), $items->first()->lesson->series),
                'lesson' => $items->first()->lesson,
            ]),
        ]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $request->user()->update($request->validated());

        return redirect()->route('me')->with('status', 'profile-updated');
    }

    public function password(UpdatePasswordRequest $request): RedirectResponse
    {
        Auth::logoutOtherDevices($request->validated('current_password'));
        $request->user()->forceFill([
            'password' => $request->validated('password'),
            'remember_token' => Str::random(60),
        ])->save();
        $request->session()->regenerate();

        return redirect()->route('me')->with('status', 'password-updated');
    }
}
