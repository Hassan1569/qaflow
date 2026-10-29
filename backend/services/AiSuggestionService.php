<?php
declare(strict_types=1);

/**
 * QAFlow — AI suggestion service.
 *
 * Stub implementation for AI-assisted test case authoring. The service is
 * isolated behind this interface so a real provider (OpenAI, Azure, local
 * model) can be swapped in without changing callers.
 *
 * The default implementation returns a deterministic, template-based set of
 * suggestions so the application remains fully functional offline and without
 * any external dependency. AI is opt-in via AI_ENABLED in .env.
 */

namespace QAFlow\Services;

use QAFlow\Config\Config;

final class AiSuggestionService
{
    /**
     * Return a set of suggested test case skeletons for a requirement.
     *
     * Each suggestion is an array shaped like the fields the TestCaseForm
     * expects, so the frontend can offer "insert suggestion" without extra
     * mapping.
     *
     * @return array<int,array<string,mixed>>
     */
    public function suggestForRequirement(string $title, string $description = ''): array
    {
        if (!Config::bool('AI_ENABLED', false)) {
            return $this->templateSuggestions($title, $description);
        }

        // When a real provider is wired in, this branch would call the
        // provider and map its response to the same shape. The provider must
        // be isolated behind a private method to keep this service testable.
        return $this->templateSuggestions($title, $description);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function templateSuggestions(string $title, string $description): array
    {
        $subject = trim($title) !== '' ? trim($title) : 'the feature';
        $lower   = strtolower($subject . ' ' . $description);

        $keywords = [];
        foreach (['login', 'password', 'reset', 'transfer', 'payment', 'search', 'checkout', 'upload', 'export', 'delete'] as $kw) {
            if (str_contains($lower, $kw)) {
                $keywords[] = $kw;
            }
        }

        $suggestions = [];

        $suggestions[] = $this->skeleton(
            "Verify $subject — happy path",
            "Happy path for $subject.",
            'critical',
            'high',
            'smoke',
            [
                'Prepare a valid precondition for ' . $subject . '.',
                'Perform the primary action for ' . $subject . '.',
                'Verify the expected outcome is achieved.',
            ]
        );

        $suggestions[] = $this->skeleton(
            "Verify $subject — empty input",
            "Empty input for $subject.",
            'high',
            'medium',
            'functional',
            [
                'Open the form related to ' . $subject . '.',
                'Submit without filling any field.',
                'Verify validation errors are shown for required fields.',
            ]
        );

        $suggestions[] = $this->skeleton(
            "Verify $subject — invalid input",
            "Invalid input for $subject.",
            'high',
            'medium',
            'negative',
            [
                'Open the form related to ' . $subject . '.',
                'Enter invalid values for required fields.',
                'Submit and verify the input is rejected with a clear message.',
            ]
        );

        $suggestions[] = $this->skeleton(
            "Verify $subject — boundary values",
            "Boundary values for $subject.",
            'medium',
            'low',
            'functional',
            [
                'Identify the minimum and maximum valid values for ' . $subject . '.',
                'Submit each boundary value.',
                'Verify the boundary values are accepted or rejected correctly.',
            ]
        );

        $suggestions[] = $this->skeleton(
            "Verify $subject — permission restriction",
            "Permission restriction for $subject.",
            'high',
            'high',
            'security',
            [
                'Log in as a user without permission to ' . $subject . '.',
                'Attempt to perform the action.',
                'Verify access is denied with an appropriate message.',
            ]
        );

        // Add feature-specific suggestions for common flows.
        foreach ($keywords as $kw) {
            $suggestions[] = $this->skeleton(
                "Verify $subject — $kw specific behavior",
                "Feature-specific behavior for $kw.",
                'medium',
                'medium',
                'functional',
                [
                    "Perform the $kw flow related to " . $subject . '.',
                    "Verify the $kw flow produces the expected result.",
                    "Check that $kw errors are handled gracefully.",
                ]
            );
        }

        return $suggestions;
    }

    /**
     * @param array<int,string> $stepActions
     * @return array<string,mixed>
     */
    private function skeleton(
        string $title,
        string $description,
        string $priority,
        string $severity,
        string $testType,
        array $stepActions
    ): array {
        $steps = [];
        foreach ($stepActions as $action) {
            $steps[] = [
                'action'          => $action,
                'expected_result' => '',
            ];
        }

        return [
            'title'             => $title,
            'description'       => $description,
            'priority'          => $priority,
            'severity'          => $severity,
            'test_type'         => $testType,
            'automation_status' => 'manual',
            'steps'             => $steps,
        ];
    }
}