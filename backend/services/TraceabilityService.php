<?php
declare(strict_types=1);

/**
 * QAFlow — Traceability service.
 *
 * Produces the requirement traceability matrix for a project: for each
 * requirement, the number of linked test cases, how many were executed, and
 * how many passed, failed, or were blocked. Defect counts are included too.
 *
 * Coverage at the requirement level is binary (1 if at least one test case is
 * linked, 0 otherwise). Project coverage is a separate aggregate handled by
 * ReportService/RequirementRepository.
 */

namespace QAFlow\Services;

use QAFlow\Core\AppException;
use QAFlow\Repositories\ProjectRepository;
use QAFlow\Repositories\RequirementRepository;

final class TraceabilityService
{
    private RequirementRepository $requirements;
    private ProjectRepository $projects;

    public function __construct(
        RequirementRepository $requirements,
        ProjectRepository $projects
    ) {
        $this->requirements = $requirements;
        $this->projects     = $projects;
    }

    /**
     * @return array<string,mixed>
     */
    public function matrix(int $projectId, int $userId, string $role): array
    {
        $this->assertCanRead($projectId, $userId, $role);

        $project = $this->projects->findById($projectId);
        if ($project === null) {
            throw AppException::notFound('Project not found.');
        }

        $rows = $this->requirements->traceabilityMatrix($projectId);

        $formatted = [];
        $totals = [
            'requirements'       => 0,
            'with_test_cases'    => 0,
            'executed'           => 0,
            'passed'             => 0,
            'failed'             => 0,
            'blocked'            => 0,
            'defects'            => 0,
        ];

        foreach ($rows as $row) {
            $testCaseCount = (int) $row['test_case_count'];
            $executed      = (int) $row['executed_count'];

            $coverage = $testCaseCount > 0 ? 100.0 : 0.0;

            $formatted[] = [
                'requirement_id'     => (int) $row['requirement_id'],
                'requirement_code'   => (string) $row['requirement_code'],
                'requirement_title'  => (string) $row['requirement_title'],
                'requirement_priority' => (string) $row['requirement_priority'],
                'requirement_status' => (string) $row['requirement_status'],
                'test_case_count'    => $testCaseCount,
                'executed'           => $executed,
                'passed'             => (int) $row['passed_count'],
                'failed'             => (int) $row['failed_count'],
                'blocked'            => (int) $row['blocked_count'],
                'defects'            => (int) $row['defect_count'],
                'coverage'           => $coverage,
            ];

            $totals['requirements']++;
            if ($testCaseCount > 0) {
                $totals['with_test_cases']++;
            }
            $totals['executed'] += $executed;
            $totals['passed']   += (int) $row['passed_count'];
            $totals['failed']   += (int) $row['failed_count'];
            $totals['blocked']  += (int) $row['blocked_count'];
            $totals['defects']  += (int) $row['defect_count'];
        }

        $projectCoverage = $totals['requirements'] > 0
            ? round(($totals['with_test_cases'] / $totals['requirements']) * 100, 2)
            : 0.0;

        return [
            'project' => [
                'id'       => (int) $project['id'],
                'key_code' => (string) $project['key_code'],
                'name'     => (string) $project['name'],
            ],
            'rows'    => $formatted,
            'totals'  => array_merge($totals, [
                'coverage' => $projectCoverage,
            ]),
        ];
    }

    private function assertCanRead(int $projectId, int $userId, string $role): void
    {
        if ($role === 'admin') {
            return;
        }
        if (!$this->projects->isMember($projectId, $userId)) {
            throw AppException::forbidden('You do not have access to this project.');
        }
    }
}