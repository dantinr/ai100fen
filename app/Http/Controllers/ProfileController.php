<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Services\CourseCatalog;
use App\Services\LearningHistoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    public function show(Request $request, CourseCatalog $catalog, LearningHistoryService $history): Response
    {
        $records = $history->records($request->user());

        return response()->view('frontend.me', [
            'series' => $catalog->all(), 'user' => $request->user(),
            'freeProgress' => $records,
            'courseProgress' => $history->courseProgress($request->user(), $records),
        ])->header('Cache-Control', 'no-store, private');
    }

    public function history(Request $request, int $progress, LearningHistoryService $history): Response|RedirectResponse
    {
        $entry = $history->find($request->user(), $progress);
        if ($entry['available']) {
            return redirect()->route('lessons.show', [$entry['course']->slug, $entry['lesson']->slug])
                ->header('Cache-Control', 'no-store, private');
        }

        return response()->view('frontend.learning-unavailable', compact('entry'), $entry['removed'] ? 410 : 403)
            ->header('Cache-Control', 'no-store, private');
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
