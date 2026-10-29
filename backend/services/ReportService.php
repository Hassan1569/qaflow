<?php
declare(strict_types=1);

/**
 * QAFlow — Report service.
 *
 * Aggregates data for the four required reports: Test Execution, Defect,
 * Coverage, and Project QA Summary. All metrics are derived from the database
 * via the repositories; nothing is hard-coded.
 */

namespace QAFlow\Services;

use QAFlow\Core\AppException;
use QAFlow\Repositories\DefectRepository;
use QAFlow\Repositories\ProjectRepository;
use QAFlow\Repositories\RequirementRepository;
use QAFlow\Repositories\TestResultRepository;

final class ReportService
{
    private ProjectRepository $projects;
    private TestResultRepository $results;
    private DefectRepository $defects;
    private RequirementRepository $requirements;

    public function __construct(
        ProjectRepository $projects,
        TestResultRepository $results,
        DefectRepository $defects,
        RequirementRepository $requirements
    ) {
        $this->projects     = $projects;
        $this->results      = $results;
        $this->defects      = $defects;
        $this->requirements = $requirements;
    }

    /**
     * Test Execution Report for a project.
     *
     * @return array<string,mixed>
     */
    public function execution(int $projectId, int $userId, string $role): array
    {
        $this->assertCanRead($projectId, $userId, $role);

        $counts = $this->results->countsByStatusForProject($projectId);

        $executed = $counts['pass'] + $counts['fail'] + $counts['blocked'];
        $total    = $executed + $counts['not_run'];

        $passRate = $executed > 0
            ? round(($counts['pass'] / $executed) * 100, 2)
            : 0.0;

        $executionRate = $total > 0
            ? round(($executed / $total) * 100, 2)
            : 0.0;

        return [
            'project_id'     => $projectId,
            'total'          => $total,
            'executed'       => $executed,
            'passed'         => $counts['pass'],
            'failed'         => $counts['fail'],
            'blocked'        => $counts['blocked'],
            'not_run'        => $counts['not_run'],
            'pass_rate'      => $passRate,
            'execution_rate' => $executionRate,
        ];
    }

    /**
     * Defect Report for a project.
     *
     * @return array<string,mixed>
     */
    public function defects(int $projectId, int $userId, string $role): array
    {
        $this->assertCanRead($projectId, $userId, $role);

        $bySeverity = $this->defects->countsBySeverityForProject($projectId);
        $byStatus   = $this->defects->countsByStatusForProject($projectId);

        $total = array_sum($bySeverity);

        return [
            'project_id'  => $projectId,
            'total'       => $total,
            'by_severity' => $bySeverity,
            'by_status'   => $byStatus,
            'open'        => $this->defects->countOpenForProject($projectId),
            'critical_open' => $this->defects->countCriticalOpenForProject($projectId),
        ];
    }

    /**
     * Coverage Report for a project.
     *
     * @return array<string,mixed>
     */
    public function coverage(int $projectId, int $userId, string $role): array
    {
        $this->assertCanRead($projectId, $userId, $role);

        $summary = $this->requirements->coverageSummary($projectId);

        return [
            'project_id' => $projectId,
            'total'      => $summary['total'],
            'covered'    => $summary['covered'],
            'uncovered'  => $summary['uncovered'],
            'coverage'   => $summary['coverage'],
        ];
    }

    /**
     * Project QA Summary — combined metrics for stakeholders.
     *
     * @return array<string,mixed>
     */
    public function projectSummary(int $projectId, int $userId, string $role): array
    {
        $this->assertCanRead($projectId, $userId, $role);

        $project = $this->projects->findById($projectId);
        if ($project === null) {
            throw AppException::notFound('Project not found.');
        }

        $execution = $this->execution($projectId, $userId, $role);
        $defects   = $this->defects($projectId, $userId, $role);
        $coverage  = $this->coverage($projectId, $userId, $role);
        $stats     = $this->projects->statistics($projectId);

        return [
            'project' => [
                'id'        => (int) $project['id'],
                'key_code'  => $project['key_code'],
                'name'      => $project['name'],
                'status'    => $project['status'],
                'owner'     => $project['owner_name'],
            ],
            'totals' => [
                'requirements' => (int) ($stats['requirements'] ?? 0),
                'test_cases'   => (int) ($stats['test_cases']   ?? 0),
                'test_plans'   => (int) ($stats['test_plans']   ?? 0),
                'test_runs'    => (int) ($stats['test_runs']    ?? 0),
                'defects'      => (int) ($stats['defects']      ?? 0),
            ],
            'execution' => $execution,
            'defects'   => $defects,
            'coverage'  => $coverage,
            'generated_at' => date('c'),
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