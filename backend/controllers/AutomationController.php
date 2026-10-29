<?php
declare(strict_types=1);

/**
 * QAFlow — Automation controller.
 *
 * Accepts external test results (JSON) and records them against a test run.
 * Authentication uses the X-Api-Token header, not the bearer token used by
 * the human UI. The token is validated against `api_tokens` scoped to a
 * project.
 */

namespace QAFlow\Controllers;

use QAFlow\Config\Database;
use QAFlow\Core\AppException;
use QAFlow\Helpers\Request;
use QAFlow\Helpers\Response;
use QAFlow\Services\AutomationService;

final class AutomationController
{
    private AutomationService $automation;

    public function __construct(AutomationService $automation)
    {
        $this->automation = $automation;
    }

    /**
     * POST /api/automation/ingest
     *
     * Headers: X-Api-Token: <token>
     * Body: { test_run_id, source?, results: [...] }
     */
    public function ingest(Request $request): Response
    {
        $projectId = $this->authenticateApiToken($request);

        $payload = $request->json();
        if ($payload === []) {
            throw AppException::validation('Validation failed.', [
                'body' => ['Request body is empty or invalid.'],
            ]);
        }

        $result = $this->automation->ingest($payload, $projectId);

        return Response::json($result);
    }

    /**
     * Validate the X-Api-Token header. Returns the associated project id.
     *
     * Comparison uses hash_equals to avoid timing leaks. Failures return a
     * generic 401 to avoid leaking token existence.
     */
    private function authenticateApiToken(Request $request): int
    {
        $provided = (string) ($request->header('X-Api-Token') ?? '');

        if ($provided === '') {
            throw AppException::unauthorized('API token is required.');
        }

        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT project_id, token FROM api_tokens'
        );
        $stmt->execute();

        $projectId = null;

        foreach ($stmt->fetchAll() as $row) {
            $stored = (string) $row['token'];
            if (hash_equals($stored, $provided)) {
                $projectId = (int) $row['project_id'];
                break;
            }
        }

        if ($projectId === null) {
            throw AppException::unauthorized('Invalid API token.');
        }

        return $projectId;
    }
}