<?php

namespace App\Http\Controllers;

use App\Models\LiveSession;
use Illuminate\Http\RedirectResponse;

class LiveEntryController extends Controller
{
    public function enter(LiveSession $session): RedirectResponse
    {
        abort_unless($session->access_type === 'public' && $session->status === 'live' && filled($session->meeting_url), 404);

        return redirect()->away($session->meeting_url)->header('Cache-Control', 'private, no-store');
    }

    public function replay(LiveSession $session): RedirectResponse
    {
        abort_unless($session->access_type === 'public' && $session->status === 'replay' && filled($session->replay_url), 404);

        return redirect()->away($session->replay_url)->header('Cache-Control', 'private, no-store');
    }
}
