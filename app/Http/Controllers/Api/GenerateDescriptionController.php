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
    public function __invoke(Request $request, string $id): JsonResponse
    {
        $site = TemporarySite::where('places_id', $id)->latest()->first();

        if (!$site) {
            return response()->json(['error' => 'Site not found'], 404);
        }

        // Build a context string from individual columns
        $name        = $site->business_name;
        $type        = $site->business_type;
        $address     = $site->formatted_address;
        $googleDesc  = $site->description;
        $phone       = $site->phone;

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
