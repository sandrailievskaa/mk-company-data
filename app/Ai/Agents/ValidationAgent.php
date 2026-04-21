<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

class ValidationAgent implements Agent, Conversational, HasStructuredOutput
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return <<<PROMPT
You are a data quality analyst for a business directory.

You will receive a JSON list of companies. Identify which ones are likely:
(a) duplicates of another company in the list (similar name, same city)
(b) inactive (name suggests dissolved, temporary, or test entity)
(c) inconsistent (phone/email format doesn't match a real business)

Return ONLY valid JSON in this exact shape:
{
  "issues_json": "[{\"company_id\":123,\"issue_type\":\"duplicate\",\"reason\":\"...\",\"confidence\":0.85}]"
}

Where "issues_json" is a JSON-encoded ARRAY with objects:
- company_id (number)
- issue_type (duplicate|inactive|inconsistent)
- reason (string)
- confidence (number 0.0-1.0)

Rules:
- Only include companies you believe have issues.
- Confidence must be between 0.0 and 1.0.
- Do not include extra keys. No markdown. No prose outside JSON.
PROMPT;
    }

    public function messages(): iterable
    {
        return [];
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'issues_json' => $schema->string()->required(),
        ];
    }
}

