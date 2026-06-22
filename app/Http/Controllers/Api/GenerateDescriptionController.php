<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TemporarySite;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GenerateDescriptionController extends Controller
{
    public function __invoke(Request $request, ?string $id = null): JsonResponse
    {
        // Standard flow: pull context from the saved temporary site.
        // Blank ("start from scratch") flow: there is no saved record yet, so
        // fall back to the business details the user has typed into the wizard.
        $site = $id ? TemporarySite::where('places_id', $id)->latest()->first() : null;

        // Build a context string from whatever the user has typed into the wizard,
        // falling back to the saved temporary site columns. Preferring the request
        // body means generation uses the latest inputted data and works even when
        // the business was never linked to Google (no saved record / empty columns).
        $name        = $request->input('business_name')     ?: $site?->business_name;
        $type        = $request->input('business_type')     ?: $site?->business_type;
        $address     = $request->input('formatted_address') ?: $site?->formatted_address;
        $googleDesc  = $request->input('description')        ?: $site?->description;
        $phone       = $request->input('phone')             ?: $site?->phone;

        if (!$name && !$type) {
            return response()->json(['error' => 'Add your business name first, then try again.'], 422);
        }

        $contextParts = array_filter([
            $name    ? "Business name: {$name}"    : null,
            $type    ? "Business type: {$type}"    : null,
            $address ? "Location: {$address}"      : null,
            $phone   ? "Phone: {$phone}"           : null,
            $googleDesc ? "Google description: {$googleDesc}" : null,
        ]);

        $context = implode("\n", $contextParts);

        $prompt = <<<PROMPT
You are helping a small business create their website. Based on the following business information, write a friendly and professional 2–3 sentence description that would appear on their website homepage.

{$context}

Requirements:
- Write in third person (e.g. "Joe's Plumbing is…")
- Keep it welcoming and confidence-inspiring for potential customers
- Do not invent specific claims (awards, years in business, etc.) unless they appear in the data
- Maximum 60 words
- Return only the description text — no quotes, no preamble

Description:
PROMPT;

        try {
            $response = Http::withHeaders([
                'x-api-key'         => env('ANTHROPIC_API_KEY'),
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ])->post('https://api.anthropic.com/v1/messages', [
                'model'      => 'claude-haiku-4-5-20251001',
                'max_tokens' => 200,
                'messages'   => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

            $response->throw();

            $description = trim($response->json('content.0.text') ?? '');

            if (empty($description)) {
                return response()->json(['error' => 'Empty response from AI'], 500);
            }

            return response()->json(['description' => $description]);

        } catch (\Throwable $e) {
            Log::error('GenerateDescription failed: ' . $e->getMessage());
            return response()->json(['error' => 'Could not generate description. Please write your own.'], 500);
        }
    }
}
