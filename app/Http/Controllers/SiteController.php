<?php

namespace App\Http\Controllers;

use App\Mail\ContactFormMail;
use App\Models\Site;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

class SiteController extends Controller
{
    public function index(string $domain): Response|RedirectResponse
    {
        $site = Site::where('subdomain', $domain)->latest()->first();

        if(!$site) {
            return redirect()->route('home');
        }

        return Inertia::render('site/Index', [
            'data' => $site->data
        ]);
    }

    public function contact(string $domain, Request $request): JsonResponse
    {
        $site = Site::where('subdomain', $domain)->latest()->first();

        if (!$site) {
            return response()->json(['error' => 'Site not found.'], 404);
        }

        $contactEmail = $site->data['contact'] ?? null;

        if (!$contactEmail) {
            return response()->json(['error' => 'This site does not have a contact email configured.'], 422);
        }

        $validator = Validator::make($request->all(), [
            'email'   => ['required', 'email', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $businessName = $site->data['displayName']['text'] ?? $domain;

        Mail::to($contactEmail)->send(new ContactFormMail(
            senderEmail: $request->input('email'),
            subject: $request->input('subject'),
            messageBody: $request->input('message'),
            businessName: $businessName,
        ));

        return response()->json(['success' => true]);
    }
}
