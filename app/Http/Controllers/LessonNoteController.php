<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveLessonNoteRequest;
use App\Models\CourseSeries;
use App\Services\CourseAccessService;
use App\Services\LessonNoteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class LessonNoteController extends Controller
{
    public function __invoke(SaveLessonNoteRequest $request, CourseSeries $series, string $lessonSlug, CourseAccessService $access, LessonNoteService $notes): JsonResponse|RedirectResponse
    {
        $lesson = $series->lessons()->where('slug', $lessonSlug)->firstOrFail();
        abort_unless($access->canAccess($series, $lesson), 404);
        $data = $request->validated();
        try {
            $record = $notes->save($request->user(), $lesson, $data['notes'] ?? '', (int) $data['notes_version']);
        } catch (HttpException $exception) {
            if (! $request->expectsJson() && $exception->getStatusCode() === 409) {
                throw ValidationException::withMessages(['notes_version' => $exception->getMessage()]);
            }
            throw $exception;
        }
        if ($request->expectsJson()) {
            return response()->json([
                'notes_version' => (int) $record->notes_version,
                'saved_at' => $record->notes_updated_at?->toIso8601String(),
            ])->header('Cache-Control', 'private, no-store');
        }

        return redirect()->route('lessons.show', [$series->slug, $lesson->slug])->with('lesson-note-saved', true);
    }
}
