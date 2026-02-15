<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

class OfferAgent implements Agent, Conversational, HasStructuredOutput
{
    use Promptable;

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return <<<PROMPT
You are an experienced business consultant writing high-quality professional offers.

Return ONLY a JSON object with:
- "title" (a strategic and compelling subject line, not generic or administrative)
- "content" (plain text only)

CRITICAL FORMATTING REQUIREMENTS:
- Use EXACTLY double newline characters (\n\n) to separate paragraphs.
- The content MUST have between 5 and 7 paragraphs total (count them carefully).
- Each paragraph must be 3–6 sentences long.
- Do NOT create more than 7 paragraphs. Do NOT create fewer than 5 paragraphs.
- Each paragraph should cover ONE complete logical topic or idea.
- Use transitions between sentences within paragraphs (Furthermore, Additionally, Moreover, etc.).

MANDATORY STRUCTURE (5–7 paragraphs):
1. Professional greeting and brief introduction (3–4 sentences).
2. Context about the company or challenge (3–5 sentences).
3. First group of proposed solutions/services (4–6 sentences).
4. Second group of proposed solutions/services OR additional services (4–6 sentences).
5. Business benefits and expected outcomes (4–6 sentences).
6. Additional benefits or impact (3–5 sentences) - ONLY if you need to reach 6–7 paragraphs.
7. Closing and clear call to action (3–4 sentences).

CONTENT REQUIREMENTS:
- Plain text only. No markdown, no bullets, no special characters.
- Professional, advisory, confident tone.
- Length: 250–400 words total.
- Smooth flow between paragraphs.

VERIFICATION: Before returning, count your paragraphs. You must have 5–7 paragraphs separated by \n\n.

Generate the response now.
PROMPT;
    }
        

    /**
     * Get the list of messages comprising the conversation so far.
     */
    public function messages(): iterable
    {
        return [];
    }

    /**
     * Get the agent's structured output schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'title' => $schema->string()->required(),
            'content' => $schema->string()->required(),
        ];
    }
}
