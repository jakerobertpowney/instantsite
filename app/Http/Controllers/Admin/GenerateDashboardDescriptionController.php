<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Site;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GenerateDashboardDescriptionController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $site = Site::where('user_id', auth()->id())->latest()->first();

        if (!$site) {
            return response()->json(['error' => 'Site not found'], 404);
        }

        $data = $site->data ?? [];

        $name       = $data['displayName']['text'] ?? null;
        $type       = $data['primaryTypeDisplayName']['text'] ?? null;
        $address    = $data['formattedAddress'] ?? null;
        $googleDesc = $data['editorialSummary']['text'] ?? $data['description'] ?? null;
        $phone      = $data['nationalPhoneNumber'] ?? null;

        $contextParts = array_filter([
            $name       ? "Business name: {$name}"            : null,
            $type       ? "Business type: {$type}"            : null,
            $address    ? "Location: {$address}"              : null,
            $phone      ? "Phone: {$phone}"                   : null,
            $googleDesc ? "Google description: {$googleDesc}" : null,
        ]);

        $context = implode("\n", $contextParts);

        $generateType = $request->input('type', 'description');

        $prompt = match ($generateType) {
            'meta_title' => <<<PROMPT
You are helping a small business with their website SEO. Based on the following business information, write a concise, compelling page title for Google search results.

{$context}

Requirements:
- Format: "Business Name — Location" or "Business Name | What They Do"
- Maximum 60 characters
- Include the business name and either location or key service
- Return only the title text — no quotes, no preamble

Title:
PROMPT,
            'meta_description' => <<<PROMPT
You are helping a small business with their website SEO. Based on the following business information, write a short meta description for Google search results.

{$context}

Requirements:
- 1–2 sentences, maximum 155 characters
- Include a clear value proposition and location if available
- Should make someone want to click through to the site
- Return only the description text — no quotes, no preamble

Description:
PROMPT,
            default => <<<PROMPT
You are helping a small business create their website. Based on the following business information, write a friendly and professional 2–3 sentence description that would appear on their website homepage.

{$context}

Requirements:
- Write in third person (e.g. "Joe's Plumbing is…")
- Keep it welcoming and confidence-inspiring for potential customers
- Do not invent specific claims (awards, years in business, etc.) unless they appear in the data
- Maximum 60 words
- Return only the description text — no quotes, no preamble

Description:
PROMPT,
        };

        $maxTokens = $generateType === 'meta_title' ? 50 : 200;

        try {
            $response = Http::withHeaders([
                'x-api-key'         => env('ANTHROPIC_API_KEY'),
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ])->post('https://api.anthropic.com/v1/messages', [
                'model'      => 'claude-haiku-4-5-20251001',
                'max_tokens' => $maxTokens,
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
            Log::error('GenerateDashboardDescription failed: ' . $e->getMessage());
            return response()->json(['error' => 'Could not generate description. Please write your own.'], 500);
        }
    }
}
