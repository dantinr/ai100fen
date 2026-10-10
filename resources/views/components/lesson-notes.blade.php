@props(['series', 'lesson', 'progress' => null, 'preview' => false, 'learningRoute' => 'lessons.show'])
<section class="free-panel lesson-notes-panel" aria-labelledby="lesson-notes-title" id="lesson-notes">
    <div class="lesson-section-kicker"><i data-lucide="notebook-pen" aria-hidden="true"></i>记录自己的思考</div>
    <h2 id="lesson-notes-title">课堂笔记</h2>
    <p class="free-note">记下关键步骤、遇到的问题和下一步。笔记仅自己可见。</p>
    @if($preview)
        <textarea class="lesson-notes-editor" aria-label="课堂笔记预览" rows="12" disabled placeholder="学习者可在这里维护自己的课堂笔记。"></textarea>
        <p class="free-note">前台预览不读取或保存课堂笔记。</p>
    @elseif(auth()->check())
        <form action="{{ route('lessons.notes', [$series, $lesson->slug]) }}" method="post" data-lesson-notes data-note-dirty="{{ session()->hasOldInput('notes') ? 'true' : 'false' }}">
            @csrf
            @method('PUT')
            <input type="hidden" name="notes_version" value="{{ old('notes_version', $progress?->notes_version ?? 0) }}">
            <label class="typewriter-readable" for="lesson-notes-editor">我的课堂笔记</label>
            <textarea id="lesson-notes-editor" class="lesson-notes-editor" name="notes" rows="12" maxlength="10000" placeholder="这一步做了什么？有哪些问题？下次从哪里继续？" aria-describedby="lesson-notes-status lesson-notes-limit" data-note-editor>{{ old('notes', $progress?->notes ?? '') }}</textarea>
            <div class="lesson-notes-meta"><span id="lesson-notes-limit">最多10000字</span><span data-note-count aria-hidden="true"></span></div>
            <div class="lesson-notes-actions"><button class="button button-primary" type="submit" data-note-save>保存笔记<i data-lucide="check" aria-hidden="true"></i></button></div>
            <p class="free-note lesson-notes-status" id="lesson-notes-status" data-note-status role="status">{{ session('lesson-note-saved') ? '已保存到账号。' : ($progress?->notes_updated_at ? '笔记已保存，可继续编辑后保存。' : '手动保存到账号，可跨设备继续。') }}</p>
            @error('notes')<p class="free-error" role="alert">{{ $message }}</p>@enderror
            @error('notes_version')<p class="free-error" role="alert">{{ $message }}</p>@enderror
        </form>
    @else
        <textarea class="lesson-notes-editor" aria-label="课堂笔记" rows="12" disabled placeholder="登录后，开始记录你的课堂笔记。"></textarea>
        <a class="button button-dark" href="{{ route('login', ['redirect' => route($learningRoute, [$series, $lesson->slug], false)]) }}">登录写笔记<i data-lucide="arrow-right" aria-hidden="true"></i></a>
        <p class="free-note">学习无需登录；保存私人笔记需要登录。</p>
    @endif
</section>
