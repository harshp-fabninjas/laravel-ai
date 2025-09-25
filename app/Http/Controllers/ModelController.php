<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Prism\Prism\Prism;
use Prism\Prism\Enums\Provider;
use Prism\Relay\Facades\Relay;

class ModelController extends Controller
{
    public function textGeneration()
    {
        $llmModel = 'llama3.2:latest';

        return view('chatbot.chat', compact('llmModel'));
    }

    public function summaryGeneration()
    {
        $llmModel = 'llama3.2:latest';

        return view('summarybot.summary', compact('llmModel'));
    }

    public function chatBot(Request $request)
    {
        $userQuery = $request->input('query');
        $llmModel = 'llama3.2:latest';

        $systemPrompt = <<<PROMPT
            You are a helpful chatbot.
            - Use the `latest-info-pull-tool` ONLY when the user asks for any information like news and any current updates.
            - For any questions that is not required the news API, DO NOT call the tool — just respond directly.
        PROMPT;

        $response = Prism::text()
            ->using(Provider::Ollama, $llmModel)
            ->withClientOptions([
                'timeout' => 180,
                'connect_timeout' => 5,
            ])
            ->withClientRetry(2, 500)
            ->withMaxSteps(2)
            // ->withMaxTokens(512)
            ->withSystemPrompt($systemPrompt)
            ->withPrompt($userQuery)
            ->withTools(Relay::tools('latest-info-pull-tool'))
            ->asText();

        return response()->json([
            'response' => $response->text ?? '',
        ]);
    }

    public function summaryBot(Request $request)
    {
        $userQuery = $request->input('query');
        $llmModel = 'llama3.2:latest';

        // with system prompts, we can change the response behavior of the model
        $systemPrompts = [
            'general' => "You are an expert summarizer AI.
                Rules:
                - Always summarize text clearly and concisely.
                - Keep the output between 3 to 5 bullet points.
                - Do not add extra commentary or opinions.
                - Always capture the key facts only.",
        ];

        // Pick system prompt
        $systemPrompt = $systemPrompts['general'];

        $startedAt = microtime(true);
        $prompt = 'Summarize this: '.$userQuery;

        $response = Prism::text()
            ->using(Provider::Ollama, $llmModel)
            ->withClientOptions([
                'timeout' => 180,
                'connect_timeout' => 5,
            ])
            ->withClientRetry(2, 500)
            // ->withMaxTokens(512)
            ->withSystemPrompt($systemPrompt)
            ->withPrompt($prompt)
            ->asText();

        $endedAt = microtime(true);
        $durationSeconds = round(($endedAt - $startedAt), 3);

        $text = $response->text;

        $promptTokens = $this->estimateTokens($prompt);
        $completionTokens = $this->estimateTokens($text);
        $totalTokens = $promptTokens + $completionTokens;

        return response()->json([
            'response' => $text,
            'duration_s' => $durationSeconds,
            'total_tokens' => $totalTokens,
        ]);
    }

    private function estimateTokens(string $text): int
    {
        // Simple heuristic: ~4 characters per token (English average)
        if (function_exists('mb_strlen')) {
            $length = mb_strlen($text, 'UTF-8');
        } else {
            $length = strlen($text);
        }

        return (int) max(1, ceil($length / 4));
    }
}
