<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactSubmission;
use App\Models\Site;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SubmissionsController extends Controller
{
    public function index(Request $request): Response|RedirectResponse
    {
        $site = Site::where('user_id', auth()->id())->latest()->first();

        if (! $site) {
            return redirect()->route('dashboard');
        }

        $submissions = $site->submissions()
            ->latest()
            ->paginate(25)
            ->through(fn ($s) => [
                'id'         => $s->id,
                'email'      => $s->email,
                'subject'    => $s->subject,
                'message'    => $s->message,
                'preferred_contact_time' => $s->preferred_contact_time,
                'read_at'    => $s->read_at?->toISOString(),
                'created_at' => $s->created_at->toISOString(),
            ]);

        $unreadCount = $site->submissions()->whereNull('read_at')->count();

        return Inertia::render('Submissions', [
            'submissions' => $submissions,
            'unreadCount' => $unreadCount,
        ]);
    }

    public function markRead(Request $request, ContactSubmission $submission): RedirectResponse
    {
        // Ensure the submission belongs to the authenticated user's site
        abort_unless($submission->site->user_id === auth()->id(), 403);

        $submission->update(['read_at' => now()]);

        return back();
    }
}
