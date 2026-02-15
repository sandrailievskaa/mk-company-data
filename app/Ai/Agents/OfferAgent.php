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
You are a senior business development specialist.

Your task is to generate a professional business offer.

OUTPUT REQUIREMENTS:
- Return a JSON object with two fields: "title" and "content".
- The "title" must be a concise, professional subject line (max 12 words).
- The "content" must be plain text only.
- Do NOT use markdown, bullet points, headings, or special formatting in the content.
- Write in a professional, confident, and persuasive tone.
- The offer should sound personalized and tailored to a company.
- Clearly present the value proposition.
- Emphasize business benefits such as efficiency, growth, ROI, innovation, or optimization.
- Keep the content length between 250–400 words.
- End with a strong but polite call to action.
- Do NOT include placeholders.
- Do NOT include explanations outside the JSON structure.

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
