<?php
declare(strict_types=1);

/**
 * QAFlow — Dashboard service.
 *
 * Aggregates metrics for the dashboard. Everything is derived from the
 * database; no numbers are hard-coded. The summary is per-project when a
 * project_id is supplied, and covers all accessible projects otherwise.
 */

namespace QAFlow\Services;

use QAFlow\Core\AppException;
use QAFlow\Repositories\DefectRepository;
use QAFlow\Repositories\EnvironmentRepository;
use QAFlow\Repositories\ProjectRepository;
use QAFlow\Repositories\RequirementRepository;
use QAFlow\Repositories\TestCaseRepository;
use QAFlow\Repositories\TestResultRepository;

final class DashboardService
{
    private ProjectRepository $projects;
    private RequirementRepository $requirements;
    private TestCaseRepository $cases;
    private TestResultRepository $results;
    private DefectRepository $defects;
    private EnvironmentRepository $environments;

    public function __construct(
        ProjectRepository $projects,
        RequirementRepository $requirements,
        TestCaseRepository $cases,
        TestResultRepository $results,
        DefectRepository $defects,
        EnvironmentRepository $environments
    ) {
        $this->projects     = $projects;
        $this->requirements = $requirements;
        $this->cases        = $cases;
        $this->results      = $results;
        $this->defects      = $defects;
        $this->environments = $environments;
    }

    /**
     * @return array<string,mixed>
     */
    public function summary(int $userId, string $role, ?int $projectId = null): array
    {
        if ($projectId !== null && $projectId > 0) {
            $this->assertCanRead($projectId, $userId, $role);

            return $this->summaryForProject($projectId);
        }

        return $this->summaryForAllProjects($userId, $role);
    }

    /**
     * @return array<string,mixed>
     */
    private function summaryForProject(int $projectId): array
    {
        $project = $this->projects->findById($projectId);
        if ($project === null) {
            throw AppException::notFound('Project not found.');
        }

        $stats    = $this->projects->statistics($projectId);
        $result   = $this->results->countsByStatusForProject($projectId);
        $coverage = $this->requirements->coverageSummary($projectId);

        $executed = $result['pass'] + $result['fail'] + $result['blocked'];
        $total    = $executed + $result['not_run'];

        $passRate = $executed > 0 ? round(($result['pass'] / $executed) * 100, 2) : 0.0;

        return [
            'scope'             => 'project',
            'project_id'        => $projectId,
            'active_projects'   => $project['status'] === 'active' ? 1 : 0,
            'total_requirements'=> $coverage['total'],
            'total_test_cases'  => (int) ($stats['test_cases'] ?? 0),
            'executed_tests'    => $executed,
            'passed'            => $result['pass'],
            'failed'            => $result['fail'],
            'blocked'           => $result['blocked'],
            'not_run'           => $result['not_run'],
            'total_executions'  => $total,
            'open_defects'      => $this->defects->countOpenForProject($projectId),
            'critical_defects'  => $this->defects->countCriticalOpenForProject($projectId),
            'requirement_coverage' => $coverage['coverage'],
            'test_pass_rate'    => $passRate,
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function summaryForAllProjects(int $userId, string $role): array
    {
        $projects = $this->projects->paginateForUser($userId, $role, [], 1, 1000)['items'];

        $totals = [
            'active_projects'      => 0,
            'total_requirements'   => 0,
            'total_test_cases'     => 0,
            'executed_tests'       => 0,
            'passed'               => 0,
            'failed'               => 0,
            'blocked'              => 0,
            'not_run'              => 0,
            'total_executions'     => 0,
            'open_defects'         => 0,
            'critical_defects'     => 0,
            'covered_requirements' => 0,
        ];

        foreach ($projects as $project) {
            $pid = (int) $project['id'];

            if ($project['status'] === 'active') {
                $totals['active_projects']++;
            }

            $stats    = $this->projects->statistics($pid);
            $result   = $this->results->countsByStatusForProject($pid);
            $coverage = $this->requirements->coverageSummary($pid);

            $executed = $result['pass'] + $result['fail'] + $result['blocked'];
            $total    = $executed + $result['not_run'];

            $totals['total_requirements']   += $coverage['total'];
            $totals['covered_requirements'] += $coverage['covered'];
            $totals['total_test_cases']     += (int) ($stats['test_cases'] ?? 0);
            $totals['executed_tests']       += $executed;
            $totals['passed']               += $result['pass'];
            $totals['failed']               += $result['fail'];
            $totals['blocked']              += $result['blocked'];
            $totals['not_run']              += $result['not_run'];
            $totals['total_executions']     += $total;
            $totals['open_defects']         += $this->defects->countOpenForProject($pid);
            $totals['critical_defects']     += $this->defects->countCriticalOpenForProject($pid);
        }

        $executed = $totals['passed'] + $totals['failed'] + $totals['blocked'];

        $totals['requirement_coverage'] = $totals['total_requirements'] > 0
            ? round(($totals['covered_requirements'] / $totals['total_requirements']) * 100, 2)
            : 0.0;

        $totals['test_pass_rate'] = $executed > 0
            ? round(($totals['passed'] / $executed) * 100, 2)
            : 0.0;

        $totals['scope'] = 'all';

        return $totals;
    }

    /**
     * Chart data for a project.
     *
     * @return array<string,mixed>
     */
    public function charts(int $userId, string $role, int $projectId): array
    {
        $this->assertCanRead($projectId, $userId, $role);

        $project = $this->projects->findById($projectId);
        if ($project === null) {
            throw AppException::notFound('Project not found.');
        }

        $result = $this->results->countsByStatusForProject($projectId);

        $defectBySeverity = $this->defects->countsBySeverityForProject($projectId);
        $defectByStatus   = $this->defects->countsByStatusForProject($projectId);

        $coverage = $this->requirements->coverageSummary($projectId);

        $envBreakdown = $this->environments->resultBreakdownForProject($projectId);

        return [
            'execution_status' => [
                ['name' => 'Pass',    'value' => $result['pass']],
                ['name' => 'Fail',    'value' => $result['fail']],
                ['name' => 'Blocked', 'value' => $result['blocked']],
                ['name' => 'Not Run', 'value' => $result['not_run']],
            ],
            'defect_severity' => [
                ['name' => 'Critical', 'value' => $defectBySeverity['critical']],
                ['name' => 'High',     'value' => $defectBySeverity['high']],
                ['name' => 'Medium',   'value' => $defectBySeverity['medium']],
                ['name' => 'Low',      'value' => $defectBySeverity['low']],
            ],
            'defect_status' => array_map(
                static fn ($k, $v) => ['name' => ucfirst(str_replace('_', ' ', (string) $k)), 'value' => (int) $v],
                array_keys($defectByStatus),
                array_values($defectByStatus)
            ),
            'coverage' => [
                ['name' => 'Covered',   'value' => $coverage['covered']],
                ['name' => 'Uncovered', 'value' => $coverage['uncovered']],
            ],
            'environment_breakdown' => array_map(static function (array $row): array {
                return [
                    'environment' => (string) $row['environment_name'],
                    'passed'      => (int) $row['passed'],
                    'failed'      => (int) $row['failed'],
                    'blocked'     => (int) $row['blocked'],
                    'total'       => (int) $row['total'],
                ];
            }, $envBreakdown),
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