<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

class RecommendationAgent implements Agent, Conversational, HasStructuredOutput
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return <<<PROMPT
You are a B2B outreach strategist.

You will receive:
- Offer details (title, description, sector)
- A JSON array of candidate companies (top by activity_index for that sector)

Return ONLY valid JSON in this exact shape:
{
  "recommendations_json": "[{\"company_id\":123,\"reason\":\"short reason\"}]"
}

Where "recommendations_json" is a JSON-encoded ARRAY of objects in this shape:
[
  { "company_id": 123, "reason": "short reason (1-2 sentences)" }
]

Rules:
- Recommend 5 to 15 companies maximum.
- Every company_id MUST exist in the provided candidates list.
- Reasons must be concise and specific (mention city/sector/activity_index/email readiness when relevant).
- Do not include any extra keys. No markdown. No prose outside JSON.
PROMPT;
    }

    public function messages(): iterable
    {
        return [];
    }

    public function schema(JsonSchema $schema): array
    {
        // Keep schema simple & robust: return JSON array as an encoded string field.
        // We'll json_decode it in the Filament page code.
        return [
            'recommendations_json' => $schema->string()->required(),
        ];
    }
}

